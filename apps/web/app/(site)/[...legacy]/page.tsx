import { notFound } from "next/navigation";

import { ProductDetailExperience } from "@/components/products/ProductDetailExperience";
import { ProductListingPage } from "@/components/products/ProductListingPage";
import { ArticleDetailContent } from "@/components/site/ArticleDetailContent";
import { GenericPageContent } from "@/components/site/GenericPageContent";
import { CaseDetailContent } from "@/components/site/CaseDetailContent";
import { ContactPageContent } from "@/components/site/ContactPageContent";
import { QuotePageContent } from "@/components/site/QuotePageContent";
import { SiteChrome } from "@/components/site/SiteChrome";
import {
  getCmsProductCategorySeoByLegacyPath,
  getCmsProductCategorySeoBySlug,
  getCmsRedirectBySourcePath,
  getCmsListingProductByLegacyPath,
  getCmsListingProductBySlug,
  getCmsProductDetailExtras,
  getCmsProductDetailExtrasByLegacyPath,
  getCmsProductSeoByLegacyPath,
  getCmsProductSeoBySlug,
  getCmsProductListingSeo,
  getPageUiContent,
  getProductListingContent,
  getProductUiContent,
} from "@/lib/cms-content";
import {
  articlePublicPath,
  casePublicPath,
  getCaseStudyByLegacyPath,
  getCaseStudyBySlug,
  getNewsArticleByLegacyPath,
  getNewsArticleBySlug,
  getNewsIndexContent,
  getGenericPageByLegacyPath,
  genericPagePublicPath,
} from "@/lib/cms-pages";
import { applicationCardFromCms, productDetailFromListing } from "@/lib/product-detail-page-data";
import { localizedSeoMetadata } from "@/lib/seo-metadata";
import { getRequestLocale } from "@/lib/server-locale";
import { formatSeoTemplate, type PageUiContent } from "@/lib/site-ui";
import AboutPage from "../about/page";
import CasesPage from "../cases/page";
import NewsPage from "../news/page";

type PageProps = { params: Promise<{ legacy: string[] }> };

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: PageProps) {
  const { legacy } = await params;
  const legacyPath = normalizeLegacyPath(legacy);
  const pageUi = await getPageUiContent();
  const fallbacks = pageUi.seoFallbacks;

  if (legacyPath === "/message.html") {
    const ui = pageUi.quotePage;
    return localizedSeoMetadata({ title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath });
  }

  if (legacyPath === "/contact-us" || legacyPath === "/contact-us.html") {
    const ui = pageUi.contactPage;
    return localizedSeoMetadata({ title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath });
  }

  const genericPage = await getGenericPageByLegacyPath(legacyPath);
  if (genericPage) {
    return localizedSeoMetadata({
      title: genericPage.seo?.metaTitle || genericPage.title,
      description: genericPage.seo?.metaDescription || genericPage.heroDescription || pageUi.seoFallbacks.genericDescription,
      canonicalPath: genericPage.seo?.canonicalPath || genericPagePublicPath(genericPage),
    });
  }

  const directSeo = await metadataFromDirectLegacyPage(legacyPath, pageUi);
  if (directSeo) return localizedSeoMetadata(directSeo);

  const [productSeo, categorySeo] = await Promise.all([
    getCmsProductSeoByLegacyPath(legacyPath),
    getCmsProductCategorySeoByLegacyPath(legacyPath),
  ]);

  if (productSeo) {
    return localizedSeoMetadata({
      title: productSeo.title ? formatSeoTemplate(fallbacks.productTitleTemplate, { title: productSeo.title }) : fallbacks.productTitle,
      description: productSeo.description ?? fallbacks.productDescription,
      canonicalPath: productSeo.canonicalPath || legacyPath,
    });
  }

  if (categorySeo) {
    return localizedSeoMetadata({
      title: categorySeo.title ? formatSeoTemplate(fallbacks.productsTitleTemplate, { title: categorySeo.title }) : fallbacks.productsTitle,
      description: categorySeo.description ?? fallbacks.productsDescription,
      canonicalPath: categorySeo.canonicalPath || legacyPath,
    });
  }

  const redirectSeo = await metadataFromRedirectTarget(legacyPath, pageUi);
  if (redirectSeo) return localizedSeoMetadata(redirectSeo);

  const [article, caseStudy] = await Promise.all([
    getNewsArticleByLegacyPath(legacyPath),
    getCaseStudyByLegacyPath(legacyPath),
  ]);

  if (article) {
    return localizedSeoMetadata({
      title: article.seo?.metaTitle || formatSeoTemplate(fallbacks.articleTitleTemplate, { title: article.title }),
      description: article.seo?.metaDescription || article.excerpt || fallbacks.articleDescription,
      canonicalPath: article.seo?.canonicalPath || articlePublicPath(article),
    });
  }

  if (caseStudy) {
    return localizedSeoMetadata({
      title: caseStudy.seo?.metaTitle || formatSeoTemplate(fallbacks.caseTitleTemplate, { title: caseStudy.title }),
      description: caseStudy.seo?.metaDescription || caseStudy.summary || fallbacks.caseDescription,
      canonicalPath: caseStudy.seo?.canonicalPath || casePublicPath(caseStudy),
    });
  }

  return localizedSeoMetadata({
    title: fallbacks.genericTitle,
    description: fallbacks.genericDescription,
    canonicalPath: legacyPath,
  });
}

async function metadataFromRedirectTarget(legacyPath: string, pageUi: PageUiContent) {
  const cmsRedirect = await getCmsRedirectBySourcePath(legacyPath);
  const targetPath = cmsRedirect?.targetPath;
  if (!targetPath) return null;
  const fallbacks = pageUi.seoFallbacks;

  const targetProductSlug = productSlugFromTarget(targetPath);
  if (targetProductSlug) {
    const seo = await getCmsProductSeoBySlug(targetProductSlug);
    if (!seo) return null;
    return {
      title: seo.title ? formatSeoTemplate(fallbacks.productTitleTemplate, { title: seo.title }) : fallbacks.productTitle,
      description: seo.description ?? fallbacks.productDescription,
      canonicalPath: legacyPath,
    };
  }

  const targetCategorySlug = categorySlugFromTarget(targetPath);
  if (targetCategorySlug) {
    const seo = await getCmsProductCategorySeoBySlug(targetCategorySlug);
    if (!seo) return null;
    return {
      title: seo.title ? formatSeoTemplate(fallbacks.productsTitleTemplate, { title: seo.title }) : fallbacks.productsTitle,
      description: seo.description ?? fallbacks.productsDescription,
      canonicalPath: legacyPath,
    };
  }

  if (targetPath === "/products") {
    const seo = await getCmsProductListingSeo();
    return { title: formatSeoTemplate(fallbacks.productsTitleTemplate, { title: seo.title }), description: seo.description, canonicalPath: legacyPath };
  }

  const targetArticleSlug = newsSlugFromTarget(targetPath);
  if (targetArticleSlug) {
    const article = await getNewsArticleBySlug(targetArticleSlug);
    if (article) {
      return {
        title: article.seo?.metaTitle || formatSeoTemplate(fallbacks.articleTitleTemplate, { title: article.title }),
        description: article.seo?.metaDescription || article.excerpt || fallbacks.articleDescription,
        canonicalPath: legacyPath,
      };
    }
  }

  const targetCaseSlug = caseSlugFromTarget(targetPath);
  if (targetCaseSlug) {
    const caseStudy = await getCaseStudyBySlug(targetCaseSlug);
    if (caseStudy) {
      return {
        title: caseStudy.seo?.metaTitle || formatSeoTemplate(fallbacks.caseTitleTemplate, { title: caseStudy.title }),
        description: caseStudy.seo?.metaDescription || caseStudy.summary || fallbacks.caseDescription,
        canonicalPath: legacyPath,
      };
    }
  }

  if (targetPath === "/news") {
    const ui = pageUi.newsPage;
    return { title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath };
  }

  if (targetPath === "/cases") {
    const ui = pageUi.casesPage;
    return { title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath };
  }

  if (targetPath === "/about") {
    const ui = pageUi.aboutPage;
    return { title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath };
  }

  if (targetPath === "/contact") {
    const ui = pageUi.contactPage;
    return { title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath };
  }

  if (targetPath === "/quote") {
    const ui = pageUi.quotePage;
    return { title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath };
  }

  return null;
}

export default async function LegacyRoutePage({ params }: PageProps) {
  const { legacy } = await params;
  const legacyPath = normalizeLegacyPath(legacy);
  const locale = await getRequestLocale();

  if (legacyPath === "/message.html") {
    return <QuotePageContent />;
  }

  if (legacyPath === "/contact-us" || legacyPath === "/contact-us.html") {
    return <ContactPageContent />;
  }

  const genericPage = await getGenericPageByLegacyPath(legacyPath);
  if (genericPage) {
    return (
      <SiteChrome>
        <GenericPageContent page={genericPage} />
      </SiteChrome>
    );
  }

  if (isQuoteLegacyPath(legacyPath)) return <QuotePageContent />;
  if (isContactLegacyPath(legacyPath)) return <ContactPageContent />;
  if (isAboutLegacyPath(legacyPath)) return <AboutPage />;
  if (isNewsIndexLegacyPath(legacyPath)) return <NewsPage />;
  if (isProductIndexLegacyPath(legacyPath)) {
    const { products, categories, ui: listingUi } = await getProductListingContent();
    return (
      <SiteChrome showFinalCta={false}>
        <ProductListingPage categories={categories} products={products} ui={listingUi} />
      </SiteChrome>
    );
  }

  const [cmsProduct, ui] = await Promise.all([getCmsListingProductByLegacyPath(legacyPath), getProductUiContent()]);
  if (cmsProduct) {
    const product = productDetailFromListing(cmsProduct, ui.detail, locale);
    const extras = await getCmsProductDetailExtrasByLegacyPath(legacyPath, ui.detail);
    if (extras) {
      Object.assign(product, extras);
      if (extras.applicationCards.length > 0) {
        product.applications = extras.applicationCards.map(applicationCardFromCms);
      }
    }

    return (
      <SiteChrome>
        <ProductDetailExperience product={product} ui={ui.detail} />
      </SiteChrome>
    );
  }

  const categorySeo = await getCmsProductCategorySeoByLegacyPath(legacyPath);
  if (categorySeo?.slug) {
    const { products, categories, ui: listingUi } = await getProductListingContent();
    return (
      <SiteChrome showFinalCta={false}>
        <ProductListingPage categories={categories} initialCategorySlug={categorySeo.slug} products={products} ui={listingUi} />
      </SiteChrome>
    );
  }

  const [article, caseStudy, pageUi] = await Promise.all([
    getNewsArticleByLegacyPath(legacyPath),
    getCaseStudyByLegacyPath(legacyPath),
    getPageUiContent(),
  ]);

  if (article) {
    const newsContent = await getNewsIndexContent();
    const related = newsContent.articles.filter((item) => item.slug !== article.slug).slice(0, pageUi.newsDetail.relatedArticleCount);
    return (
      <SiteChrome>
        <ArticleDetailContent article={article} related={related} ui={pageUi.newsDetail} locale={locale} />
      </SiteChrome>
    );
  }

  if (caseStudy) {
    return (
      <SiteChrome>
        <CaseDetailContent item={caseStudy} ui={pageUi.caseDetail} locale={locale} />
      </SiteChrome>
    );
  }

  const cmsRedirect = await getCmsRedirectBySourcePath(legacyPath);
  if (cmsRedirect) {
    const targetProductSlug = productSlugFromTarget(cmsRedirect.targetPath);
    if (targetProductSlug) {
      const targetProduct = await getCmsListingProductBySlug(targetProductSlug);
      if (targetProduct) {
        const product = productDetailFromListing(targetProduct, ui.detail, locale);
        const extras = await getCmsProductDetailExtras(targetProductSlug, ui.detail);
        if (extras) {
          Object.assign(product, extras);
          if (extras.applicationCards.length > 0) {
            product.applications = extras.applicationCards.map(applicationCardFromCms);
          }
        }

        return (
          <SiteChrome>
            <ProductDetailExperience product={product} ui={ui.detail} />
          </SiteChrome>
        );
      }
    }

    const targetCategorySlug = categorySlugFromTarget(cmsRedirect.targetPath);
    if (targetCategorySlug) {
      const { products, categories, ui: listingUi } = await getProductListingContent();
      if (categories.some((item) => item.slug === targetCategorySlug)) {
        return (
          <SiteChrome showFinalCta={false}>
            <ProductListingPage categories={categories} initialCategorySlug={targetCategorySlug} products={products} ui={listingUi} />
          </SiteChrome>
        );
      }
    }

    if (cmsRedirect.targetPath === "/products") {
      const { products, categories, ui: listingUi } = await getProductListingContent();
      return (
        <SiteChrome showFinalCta={false}>
          <ProductListingPage categories={categories} products={products} ui={listingUi} />
        </SiteChrome>
      );
    }

    const targetArticleSlug = newsSlugFromTarget(cmsRedirect.targetPath);
    if (targetArticleSlug) {
      const targetArticle = await getNewsArticleBySlug(targetArticleSlug);
      if (targetArticle) {
        const newsContent = await getNewsIndexContent();
        const pageUi = await getPageUiContent();
        const related = newsContent.articles.filter((item) => item.slug !== targetArticle.slug).slice(0, pageUi.newsDetail.relatedArticleCount);
        return (
          <SiteChrome>
            <ArticleDetailContent article={targetArticle} related={related} ui={pageUi.newsDetail} locale={locale} />
          </SiteChrome>
        );
      }
    }

    const targetCaseSlug = caseSlugFromTarget(cmsRedirect.targetPath);
    if (targetCaseSlug) {
      const targetCase = await getCaseStudyBySlug(targetCaseSlug);
      if (targetCase) {
        const pageUi = await getPageUiContent();
        return (
          <SiteChrome>
            <CaseDetailContent item={targetCase} ui={pageUi.caseDetail} locale={locale} />
          </SiteChrome>
        );
      }
    }

    if (cmsRedirect.targetPath === "/news") return <NewsPage />;
    if (cmsRedirect.targetPath === "/cases") return <CasesPage />;
    if (cmsRedirect.targetPath === "/about") return <AboutPage />;
    if (cmsRedirect.targetPath === "/contact") return <ContactPageContent />;
    if (cmsRedirect.targetPath === "/quote") return <QuotePageContent />;

    notFound();
  }

  notFound();
}

function normalizeLegacyPath(legacy: string | string[]) {
  const path = Array.isArray(legacy) ? legacy.join("/") : legacy;
  return path.startsWith("/") ? path : `/${path}`;
}

async function metadataFromDirectLegacyPage(legacyPath: string, pageUi: PageUiContent) {
  if (isQuoteLegacyPath(legacyPath)) {
    const ui = pageUi.quotePage;
    return { title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath };
  }

  if (isContactLegacyPath(legacyPath)) {
    const ui = pageUi.contactPage;
    return { title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath };
  }

  if (isAboutLegacyPath(legacyPath)) {
    const ui = pageUi.aboutPage;
    return { title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath };
  }

  if (isNewsIndexLegacyPath(legacyPath)) {
    const ui = pageUi.newsPage;
    return { title: ui.metaTitle, description: ui.metaDescription, canonicalPath: legacyPath };
  }

  if (isProductIndexLegacyPath(legacyPath)) {
    const seo = await getCmsProductListingSeo();
    return { title: seo.title, description: seo.description, canonicalPath: legacyPath };
  }

  return null;
}

function isQuoteLegacyPath(path: string) {
  return new Set(["/message.html", "/faqlist.html", "/article-inquire.html", "/contact-us", "/contact-us/"]).has(path);
}

function isContactLegacyPath(path: string) {
  return new Set(["/contactus.html", "/contact.html", "/contact-us.html"]).has(path);
}

function isAboutLegacyPath(path: string) {
  return new Set(["/about.html", "/about-371153.html", "/authority.html"]).has(path);
}

function isNewsIndexLegacyPath(path: string) {
  return new Set(["/news.html", "/Company-News.html", "/Industry-News.html", "/Company-News-ic236357.html", "/Industry-News-ic226357.html"]).has(path);
}

function isProductIndexLegacyPath(path: string) {
  return new Set(["/products.html", "/productcategory.html", "/keywords.html", "/keyword-index.html", "/gallery.html", "/videos.html", "/video-detail.html"]).has(path);
}

function productSlugFromTarget(targetPath: string) {
  const match = targetPath.match(/^\/products\/([^/?#]+)(?:[?#].*)?$/);
  return match?.[1] ?? null;
}

function categorySlugFromTarget(targetPath: string) {
  const match = targetPath.match(/^\/products\/category\/([^/?#]+)(?:[?#].*)?$/);
  return match?.[1] ?? null;
}

function newsSlugFromTarget(targetPath: string) {
  const match = targetPath.match(/^\/news\/([^/?#]+)(?:[?#].*)?$/);
  return match?.[1] ?? null;
}

function caseSlugFromTarget(targetPath: string) {
  const match = targetPath.match(/^\/cases\/([^/?#]+)(?:[?#].*)?$/);
  return match?.[1] ?? null;
}
