import { ProductListingPage } from "@/components/products/ProductListingPage";
import { SiteChrome } from "@/components/site/SiteChrome";
import { getCmsProductListingSeo, getPageUiContent, getProductListingContent } from "@/lib/cms-content";
import { localizedPath } from "@/lib/i18n";
import { readyLanguageAlternates } from "@/lib/seo-language-alternates";
import { getRequestLocale } from "@/lib/server-locale";
import { formatSeoTemplate } from "@/lib/site-ui";

export const dynamic = "force-dynamic";

export async function generateMetadata() {
  const [seo, pageUi, locale] = await Promise.all([getCmsProductListingSeo(), getPageUiContent(), getRequestLocale()]);

  return {
    title: formatSeoTemplate(pageUi.seoFallbacks.productsTitleTemplate, { title: seo.title }),
    description: seo.description,
    alternates: {
      canonical: localizedPath(seo.canonicalPath, locale),
      languages: await readyLanguageAlternates(seo.canonicalPath),
    },
  };
}

export default async function ProductsPage() {
  const { products, categories, ui } = await getProductListingContent();

  return (
    <SiteChrome showFinalCta={false}>
      <ProductListingPage categories={categories} products={products} ui={ui} />
    </SiteChrome>
  );
}
