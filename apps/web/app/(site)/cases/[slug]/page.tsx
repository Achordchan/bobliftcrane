import { notFound } from "next/navigation";

import { CaseDetailContent } from "@/components/site/CaseDetailContent";
import { SiteChrome } from "@/components/site/SiteChrome";
import { casePublicPath, getCaseStudyByLegacyPath, getCaseStudyBySlug } from "@/lib/cms-pages";
import { getCmsRedirectBySourcePath, getPageUiContent } from "@/lib/cms-content";
import { localizedSeoMetadata } from "@/lib/seo-metadata";
import { getRequestLocale } from "@/lib/server-locale";
import { formatSeoTemplate } from "@/lib/site-ui";
import CasesPage from "../page";

type PageProps = { params: Promise<{ slug: string }> };

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: PageProps) {
  const { slug } = await params;
  const [item, pageUi] = await Promise.all([
    getCaseStudyBySlug(slug).then((caseStudy) => caseStudy ?? getCaseStudyByLegacyPath(caseLegacyPath(slug))),
    getPageUiContent(),
  ]);
  const fallbacks = pageUi.seoFallbacks;
  return localizedSeoMetadata({
    title: item?.seo?.metaTitle || (item ? formatSeoTemplate(fallbacks.caseTitleTemplate, { title: item.title }) : fallbacks.caseTitle),
    description: item?.seo?.metaDescription || item?.summary || fallbacks.caseDescription,
    canonicalPath: item?.seo?.canonicalPath || (item ? casePublicPath(item) : `/cases/${slug}`),
  });
}

export default async function CaseDetailPage({ params }: PageProps) {
  const { slug } = await params;
  const legacyPath = caseLegacyPath(slug);
  const [directCase, legacyCase, pageUi, locale] = await Promise.all([getCaseStudyBySlug(slug), getCaseStudyByLegacyPath(legacyPath), getPageUiContent(), getRequestLocale()]);
  const item = directCase ?? legacyCase;

  if (!item) {
    const cmsRedirect = await getCmsRedirectBySourcePath(legacyPath);
    if (cmsRedirect?.targetPath === "/cases") return <CasesPage />;

    const targetSlug = caseSlugFromTarget(cmsRedirect?.targetPath);
    if (targetSlug) {
      const targetCase = await getCaseStudyBySlug(targetSlug);
      if (targetCase) return renderCase(targetCase, pageUi, locale);
    }

    notFound();
  }

  return renderCase(item, pageUi, locale);
}

function renderCase(
  item: NonNullable<Awaited<ReturnType<typeof getCaseStudyBySlug>>>,
  pageUi: Awaited<ReturnType<typeof getPageUiContent>>,
  locale: Awaited<ReturnType<typeof getRequestLocale>>,
) {
  const ui = pageUi.caseDetail;

  return (
    <SiteChrome>
      <CaseDetailContent item={item} ui={ui} locale={locale} />
    </SiteChrome>
  );
}

function caseLegacyPath(slug: string) {
  return `/cases/${slug}`.replace(/\/+/g, "/");
}

function caseSlugFromTarget(targetPath: string | null | undefined) {
  const match = targetPath?.match(/^\/cases\/([^/?#]+)(?:[?#].*)?$/);
  return match?.[1] ?? null;
}
