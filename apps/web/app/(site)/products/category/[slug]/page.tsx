import { notFound } from "next/navigation";

import { ProductListingPage } from "@/components/products/ProductListingPage";
import { SiteChrome } from "@/components/site/SiteChrome";
import { getCmsProductCategorySeoBySlug, getPageUiContent, getProductListingContent } from "@/lib/cms-content";
import { localizedPath } from "@/lib/i18n";
import { readyLanguageAlternates } from "@/lib/seo-language-alternates";
import { getRequestLocale } from "@/lib/server-locale";
import { formatSeoTemplate } from "@/lib/site-ui";

type PageProps = { params: Promise<{ slug: string }> };

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: PageProps) {
  const { slug } = await params;
  const [seo, pageUi, locale] = await Promise.all([getCmsProductCategorySeoBySlug(slug), getPageUiContent(), getRequestLocale()]);
  const canonicalPath = seo?.canonicalPath ?? `/products/category/${slug}`;
  const fallbacks = pageUi.seoFallbacks;

  if (!seo) {
    return {
      title: fallbacks.productsTitle,
      description: fallbacks.productsDescription,
      alternates: {
        canonical: localizedPath(canonicalPath, locale),
        languages: await readyLanguageAlternates(canonicalPath),
      },
    };
  }

  return {
    title: seo.title ? formatSeoTemplate(fallbacks.productsTitleTemplate, { title: seo.title }) : fallbacks.productsTitle,
    description: seo.description ?? fallbacks.productsDescription,
    alternates: {
      canonical: localizedPath(canonicalPath, locale),
      languages: await readyLanguageAlternates(canonicalPath),
    },
  };
}

export default async function ProductCategoryPage({ params }: PageProps) {
  const { slug } = await params;
  const { products, categories, ui } = await getProductListingContent();
  const category = categories.find((item) => item.slug === slug);

  if (!category) notFound();

  return (
    <SiteChrome showFinalCta={false}>
      <ProductListingPage categories={categories} initialCategorySlug={slug} products={products} ui={ui} />
    </SiteChrome>
  );
}
