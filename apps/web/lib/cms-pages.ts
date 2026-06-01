import config from "@payload-config";
import { getPayload } from "payload";

import { mediaUrl } from "@/lib/cms-media";
import { aboutSectionFromSettings, combineCmsSettings, pageUiFromSettings, type CmsSiteSetting } from "@/lib/cms-site-settings";
import { defaultAboutSectionContent, type AboutSectionContent } from "@/lib/home-defaults";
import {
  articleCategories as fallbackArticleCategories,
  articles as fallbackArticles,
  caseStudies as fallbackCases,
  type Article as FallbackArticle,
  type CaseStudy as FallbackCaseStudy,
} from "@/lib/site-content";
import type { Article as PayloadArticle, Case as PayloadCase, Page as PayloadPage } from "@/payload-types";
import type { PageUiContent } from "@/lib/site-ui";
import type { SiteLocale } from "@/lib/i18n";
import { getRequestLocale } from "@/lib/server-locale";

type CmsLocale = SiteLocale;

export type NewsArticle = FallbackArticle & {
  contentHtml?: string | null;
  seo?: {
    metaTitle?: string | null;
    metaDescription?: string | null;
    canonicalPath?: string | null;
  } | null;
};

export type CaseStudy = FallbackCaseStudy & {
  legacyPath?: string | null;
  contentHtml?: string | null;
  seo?: {
    metaTitle?: string | null;
    metaDescription?: string | null;
    canonicalPath?: string | null;
  } | null;
};

export type AboutPageContent = AboutSectionContent;

export type GenericPageBlock = {
  label?: string | null;
  title: string;
  text: string;
  image?: string | null;
  ctaLabel?: string | null;
  ctaHref?: string | null;
};

export type GenericPageContent = {
  slug: string;
  legacyPath?: string | null;
  title: string;
  heroLabel?: string | null;
  heroTitle: string;
  heroDescription?: string | null;
  heroImage?: string | null;
  contentBlocks: GenericPageBlock[];
  seo?: {
    metaTitle?: string | null;
    metaDescription?: string | null;
    canonicalPath?: string | null;
  } | null;
};

type CmsCase = PayloadCase & {
  coverImagePath?: string | null;
  caseType?: string | null;
  facts?: { label?: string | null; value?: string | null; id?: string | null }[] | null;
  sections?: { heading?: string | null; body?: string | null; id?: string | null }[] | null;
  contentHtml?: string | null;
};

type CmsArticle = PayloadArticle & {
  coverImagePath?: string | null;
};

type CmsPage = PayloadPage & {
  heroImagePath?: string | null;
  contentBlocks?: {
    label?: string | null;
    title?: string | null;
    text?: string | null;
    image?: number | import("@/payload-types").Media | null;
    imagePath?: string | null;
    ctaLabel?: string | null;
    ctaHref?: string | null;
    id?: string | null;
  }[] | null;
};

export async function getNewsIndexContent() {
  const settings = await getCmsSiteSettings();
  const pageUi = pageUiFromSettings(settings);
  const labels = pageUi.contentLabels;
  const articles = await getPublishedCmsArticles(100, pageUi);
  const items = articles.length > 0 ? articles : fallbackArticles;
  const defaultCategories = Object.values(labels.articleCategories);
  const categories = Array.from(new Set([...defaultCategories, ...fallbackArticleCategories, ...items.map((item) => item.category).filter(Boolean)]));

  return { articles: items, categories };
}

export async function getNewsArticleBySlug(slug: string): Promise<NewsArticle | null> {
  const settings = await getCmsSiteSettings();
  const pageUi = pageUiFromSettings(settings);
  return (await getCmsArticleBySlug(slug, pageUi)) ?? fallbackArticles.find((item) => item.slug === slug) ?? null;
}

export async function getNewsArticleByLegacyPath(path: string): Promise<NewsArticle | null> {
  const legacyPath = normalizePublicPath(path);
  const settings = await getCmsSiteSettings();
  const pageUi = pageUiFromSettings(settings);
  return (await getCmsArticleByLegacyPath(legacyPath, pageUi)) ?? fallbackArticles.find((item) => item.legacyPath === legacyPath) ?? null;
}

export async function getCaseIndexContent() {
  const settings = await getCmsSiteSettings();
  const cases = await getPublishedCmsCases(100, pageUiFromSettings(settings));
  return cases.length > 0 ? cases : fallbackCases;
}

export async function getCaseStudyBySlug(slug: string): Promise<CaseStudy | null> {
  const settings = await getCmsSiteSettings();
  const pageUi = pageUiFromSettings(settings);
  return (await getCmsCaseBySlug(slug, pageUi)) ?? fallbackCases.find((item) => item.slug === slug) ?? null;
}

export async function getCaseStudyByLegacyPath(path: string): Promise<CaseStudy | null> {
  const legacyPath = normalizePublicPath(path);
  const settings = await getCmsSiteSettings();
  return (await getCmsCaseByLegacyPath(legacyPath, pageUiFromSettings(settings))) ?? null;
}


export async function getGenericPageByLegacyPath(path: string): Promise<GenericPageContent | null> {
  const legacyPath = normalizePublicPath(path);
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const slug = legacyPath.replace(/^\//, "").replace(/\.html?$/i, "");
    const result = await payload.find({
      collection: "pages",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 1,
      where: {
        and: [
          {
            or: [
              { legacyPath: { equals: legacyPath } },
              { "legacyAliases.path": { equals: legacyPath } },
              { slug: { equals: slug } },
            ],
          },
          { status: { equals: "published" } },
        ],
      },
    });
    const page = result.docs[0] as CmsPage | undefined;
    return page ? cmsPageToGenericPage(page) : null;
  } catch {
    return null;
  }
}

export function genericPagePublicPath(page: Pick<GenericPageContent, "legacyPath" | "slug">) {
  return page.legacyPath && page.legacyPath.startsWith("/") ? page.legacyPath : `/${page.slug}`;
}

export async function getAboutPageContent(): Promise<AboutPageContent> {
  const settings = await getCmsSiteSettings();
  return settings ? aboutSectionFromSettings(settings) : defaultAboutSectionContent;
}

export function articlePublicPath(article: Pick<NewsArticle, "legacyPath" | "slug">) {
  return article.legacyPath && article.legacyPath.startsWith("/") ? article.legacyPath : `/news/${article.slug}`;
}

export function casePublicPath(item: Pick<CaseStudy, "legacyPath" | "slug">) {
  return item.legacyPath && item.legacyPath.startsWith("/") ? item.legacyPath : `/cases/${item.slug}`;
}

async function getPublishedCmsArticles(limit: number, pageUi: PageUiContent): Promise<NewsArticle[]> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "articles",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit,
      sort: "-publishedAt",
      where: { status: { equals: "published" } },
    });
    return (result.docs as PayloadArticle[]).map((article) => cmsArticleToNewsArticle(article, pageUi));
  } catch {
    return [];
  }
}

async function getCmsArticleBySlug(slug: string, pageUi: PageUiContent): Promise<NewsArticle | null> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "articles",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 1,
      where: { and: [{ slug: { equals: slug } }, { status: { equals: "published" } }] },
    });
    const article = result.docs[0] as PayloadArticle | undefined;
    return article ? cmsArticleToNewsArticle(article, pageUi) : null;
  } catch {
    return null;
  }
}

async function getCmsArticleByLegacyPath(legacyPath: string, pageUi: PageUiContent): Promise<NewsArticle | null> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "articles",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 1,
      where: {
        and: [
          {
            or: [
              { legacyPath: { equals: legacyPath } },
              { "legacyAliases.path": { equals: legacyPath } },
            ],
          },
          { status: { equals: "published" } },
        ],
      },
    });
    const article = result.docs[0] as PayloadArticle | undefined;
    return article ? cmsArticleToNewsArticle(article, pageUi) : null;
  } catch {
    return null;
  }
}

async function getPublishedCmsCases(limit: number, pageUi: PageUiContent): Promise<CaseStudy[]> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "cases",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit,
      sort: "-publishedAt",
      where: { status: { equals: "published" } },
    });
    return (result.docs as CmsCase[]).map((item) => cmsCaseToCaseStudy(item, pageUi));
  } catch {
    return [];
  }
}

async function getCmsCaseBySlug(slug: string, pageUi: PageUiContent): Promise<CaseStudy | null> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "cases",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 1,
      where: { and: [{ slug: { equals: slug } }, { status: { equals: "published" } }] },
    });
    const item = result.docs[0] as CmsCase | undefined;
    return item ? cmsCaseToCaseStudy(item, pageUi) : null;
  } catch {
    return null;
  }
}

async function getCmsCaseByLegacyPath(legacyPath: string, pageUi: PageUiContent): Promise<CaseStudy | null> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "cases",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 1,
      where: {
        and: [
          {
            or: [
              { legacyPath: { equals: legacyPath } },
              { "legacyAliases.path": { equals: legacyPath } },
            ],
          },
          { status: { equals: "published" } },
        ],
      },
    });
    const item = result.docs[0] as CmsCase | undefined;
    return item ? cmsCaseToCaseStudy(item, pageUi) : null;
  } catch {
    return null;
  }
}

async function getCmsSiteSettings(): Promise<CmsSiteSetting | null> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const [site, home, productListing, productDetail, pageText] = await Promise.all([
      findCmsGlobal(payload, "site-settings", locale),
      findCmsGlobal(payload, "home-settings", locale),
      findCmsGlobal(payload, "product-listing-ui", locale),
      findCmsGlobal(payload, "product-detail-ui", locale),
      findCmsGlobal(payload, "page-text-settings", locale),
    ]);

    return combineCmsSettings(site, home, productListing, productDetail, pageText);
  } catch {
    return null;
  }
}

async function findCmsGlobal(payload: Awaited<ReturnType<typeof getPayload>>, slug: string, locale: CmsLocale) {
  try {
    return await payload.findGlobal({ slug: slug as any, depth: 2, fallbackLocale: "en", locale });
  } catch {
    return null;
  }
}

async function getActiveLocale(): Promise<CmsLocale> {
  return getRequestLocale();
}


function cmsPageToGenericPage(page: CmsPage): GenericPageContent {
  const blocks = page.contentBlocks?.flatMap((block) => {
    if (!block?.title || !block.text) return [];
    return [{
      label: block.label || null,
      title: block.title,
      text: block.text,
      image: mediaUrl(block.image, "card") ?? block.imagePath ?? null,
      ctaLabel: block.ctaLabel || null,
      ctaHref: block.ctaHref || null,
    }];
  }) ?? [];

  return {
    slug: page.slug,
    legacyPath: page.legacyPath,
    title: page.title,
    heroLabel: page.heroLabel || null,
    heroTitle: page.heroTitle || page.title,
    heroDescription: page.heroDescription || page.seo?.metaDescription || null,
    heroImage: mediaUrl(page.heroImage, "hero") ?? page.heroImagePath ?? null,
    contentBlocks: blocks.length > 0 ? blocks : [{ title: page.title, text: page.heroDescription || page.seo?.metaDescription || "BOB-LIFT page content is ready to be managed from the admin." }],
    seo: page.seo,
  };
}

function cmsArticleToNewsArticle(article: CmsArticle, pageUi: PageUiContent): NewsArticle {
  const labels = pageUi.contentLabels;
  const articleCategoryLabels: Record<string, string> = {
    "company-news": labels.articleCategories.companyNews,
    "product-news": labels.articleCategories.productNews,
    "industry-insight": labels.articleCategories.industryInsight,
  };

  return {
    slug: article.slug,
    legacyPath: article.legacyPath ?? `/${article.slug}.html`,
    title: article.title,
    date: (article.publishedAt ?? article.createdAt).slice(0, 10),
    category: articleCategoryLabels[article.category] ?? article.category,
    image: mediaUrl(article.coverImage, "card") ?? article.coverImagePath ?? "/images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg",
    excerpt: article.excerpt ?? article.seo?.metaDescription ?? pageUi.seoFallbacks.articleExcerpt,
    sections: [],
    contentHtml: article.contentHtml,
    seo: article.seo,
  };
}

function cmsCaseToCaseStudy(item: CmsCase, pageUi: PageUiContent): CaseStudy {
  const labels = pageUi.contentLabels;
  const caseType = item.caseType || item.industry || labels.caseFacts.defaultCaseType;
  const fallbackFacts = [
    item.country ? { label: labels.caseFacts.region, value: item.country } : null,
    item.industry ? { label: labels.caseFacts.industry, value: item.industry } : null,
    { label: labels.caseFacts.priority, value: labels.caseFacts.defaultPriority },
  ].filter((fact): fact is { label: string; value: string } => Boolean(fact));

  return {
    slug: item.slug,
    legacyPath: item.legacyPath,
    title: item.title,
    type: caseType,
    image: mediaUrl(item.coverImage, "card") ?? item.coverImagePath ?? "/images/oldsite/cases/bob-lift-provides-reliable-support-for-water-operations.jpg",
    summary: item.summary ?? item.seo?.metaDescription ?? pageUi.seoFallbacks.caseSummary,
    facts: cleanFacts(item.facts) || fallbackFacts,
    sections: cleanSections(item.sections),
    contentHtml: item.contentHtml,
    seo: item.seo,
  };
}

function normalizePublicPath(path: string) {
  return path.startsWith("/") ? path : `/${path}`;
}

function cleanFacts(facts?: CmsCase["facts"]) {
  const items = facts?.flatMap((fact) => (fact?.label && fact.value ? [{ label: fact.label, value: fact.value }] : []));
  return items && items.length > 0 ? items : null;
}

function cleanSections(sections?: CmsCase["sections"]) {
  return sections?.flatMap((section) => (section?.heading && section.body ? [{ heading: section.heading, body: section.body }] : [])) ?? [];
}
