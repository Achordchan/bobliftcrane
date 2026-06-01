import { notFound } from "next/navigation";

import { ArticleDetailContent } from "@/components/site/ArticleDetailContent";
import { SiteChrome } from "@/components/site/SiteChrome";
import { articlePublicPath, getNewsArticleByLegacyPath, getNewsArticleBySlug, getNewsIndexContent } from "@/lib/cms-pages";
import { getCmsRedirectBySourcePath, getPageUiContent } from "@/lib/cms-content";
import { localizedSeoMetadata } from "@/lib/seo-metadata";
import { getRequestLocale } from "@/lib/server-locale";
import { formatSeoTemplate } from "@/lib/site-ui";
import NewsPage from "../page";

type PageProps = { params: Promise<{ slug: string }> };

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: PageProps) {
  const { slug } = await params;
  const [article, pageUi] = await Promise.all([
    getNewsArticleBySlug(slug).then((item) => item ?? getNewsArticleByLegacyPath(newsLegacyPath(slug))),
    getPageUiContent(),
  ]);
  const fallbacks = pageUi.seoFallbacks;
  return localizedSeoMetadata({
    title: article?.seo?.metaTitle || (article ? formatSeoTemplate(fallbacks.articleTitleTemplate, { title: article.title }) : fallbacks.articleTitle),
    description: article?.seo?.metaDescription || article?.excerpt || fallbacks.articleDescription,
    canonicalPath: article?.seo?.canonicalPath || (article ? articlePublicPath(article) : `/news/${slug}`),
  });
}

export default async function ArticlePage({ params }: PageProps) {
  const { slug } = await params;
  const legacyPath = newsLegacyPath(slug);
  const [directArticle, legacyArticle, newsContent, pageUi, locale] = await Promise.all([getNewsArticleBySlug(slug), getNewsArticleByLegacyPath(legacyPath), getNewsIndexContent(), getPageUiContent(), getRequestLocale()]);
  const article = directArticle ?? legacyArticle;

  if (!article) {
    const cmsRedirect = await getCmsRedirectBySourcePath(legacyPath);
    if (cmsRedirect?.targetPath === "/news") return <NewsPage />;

    const targetSlug = newsSlugFromTarget(cmsRedirect?.targetPath);
    if (targetSlug) {
      const targetArticle = await getNewsArticleBySlug(targetSlug);
      if (targetArticle) return renderArticle(targetArticle, newsContent, pageUi, locale);
    }

    notFound();
  }

  return renderArticle(article, newsContent, pageUi, locale);
}

function renderArticle(
  article: NonNullable<Awaited<ReturnType<typeof getNewsArticleBySlug>>>,
  newsContent: Awaited<ReturnType<typeof getNewsIndexContent>>,
  pageUi: Awaited<ReturnType<typeof getPageUiContent>>,
  locale: Awaited<ReturnType<typeof getRequestLocale>>,
) {
  const ui = pageUi.newsDetail;
  const related = newsContent.articles.filter((item) => item.slug !== article.slug).slice(0, ui.relatedArticleCount);

  return (
    <SiteChrome>
      <ArticleDetailContent article={article} related={related} ui={ui} locale={locale} />
    </SiteChrome>
  );
}

function newsLegacyPath(slug: string) {
  return `/news/${slug}`.replace(/\/+/g, "/");
}

function newsSlugFromTarget(targetPath: string | null | undefined) {
  const match = targetPath?.match(/^\/news\/([^/?#]+)(?:[?#].*)?$/);
  return match?.[1] ?? null;
}
