import { notFound } from "next/navigation";

import { ProductDetailExperience } from "@/components/products/ProductDetailExperience";
import { ProductListingPage } from "@/components/products/ProductListingPage";
import { SiteChrome } from "@/components/site/SiteChrome";
import {
  getCmsListingProductByLegacyPath,
  getCmsListingProductBySlug,
  getCmsProductCategorySeoByLegacyPath,
  getCmsProductCategorySeoBySlug,
  getCmsProductListingSeo,
  getCmsProductDetailExtras,
  getCmsProductDetailExtrasByLegacyPath,
  getCmsProductSeoByLegacyPath,
  getCmsProductSeoBySlug,
  getCmsRedirectBySourcePath,
  getPageUiContent,
  getProductListingContent,
  getProductUiContent,
} from "@/lib/cms-content";
import { localizedPath } from "@/lib/i18n";
import { applicationCardFromCms, productDetailFromListing } from "@/lib/product-detail-page-data";
import { readyLanguageAlternates } from "@/lib/seo-language-alternates";
import { getRequestLocale } from "@/lib/server-locale";
import { formatSeoTemplate } from "@/lib/site-ui";

const productsPrefix = "/products";

type PageProps = { params: Promise<{ slug: string }> };

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: PageProps) {
  const { slug } = await params;
  const legacyPath = productLegacyPath(slug);
  const redirectTarget = await getCmsRedirectBySourcePath(legacyPath);
  const targetCategorySlug = redirectTarget ? categorySlugFromTarget(redirectTarget.targetPath) : null;
  const shouldUseListingSeo = redirectTarget?.targetPath === "/products";
  const [cmsSeo, legacySeo, categorySeo, redirectedCategorySeo, listingSeo, pageUi, locale] = await Promise.all([
    getCmsProductSeoBySlug(slug),
    getCmsProductSeoByLegacyPath(legacyPath),
    getCmsProductCategorySeoByLegacyPath(legacyPath),
    targetCategorySlug ? getCmsProductCategorySeoBySlug(targetCategorySlug) : Promise.resolve(null),
    shouldUseListingSeo ? getCmsProductListingSeo() : Promise.resolve(null),
    getPageUiContent(),
    getRequestLocale(),
  ]);
  const seo = cmsSeo ?? legacySeo ?? categorySeo ?? redirectedCategorySeo ?? listingSeo;
  const canonicalPath = seo?.canonicalPath ?? `/products/${slug}`;
  const fallbacks = pageUi.seoFallbacks;

  return {
    title: seo?.title ? formatSeoTemplate(fallbacks.productTitleTemplate, { title: seo.title }) : fallbacks.productTitle,
    description: seo?.description ?? fallbacks.productDescription,
    alternates: {
      canonical: localizedPath(canonicalPath, locale),
      languages: await readyLanguageAlternates(canonicalPath),
    },
  };
}

export default async function ProductDetailPage({ params }: PageProps) {
  const { slug } = await params;
  const legacyPath = productLegacyPath(slug);
  const [cmsProduct, legacyProduct, ui, locale] = await Promise.all([
    getCmsListingProductBySlug(slug),
    getCmsListingProductByLegacyPath(legacyPath),
    getProductUiContent(),
    getRequestLocale(),
  ]);
  const productSource = cmsProduct ? "slug" : legacyProduct ? "legacy" : null;
  const productRecord = cmsProduct ?? legacyProduct;

  if (productRecord && productSource) {
    const product = productDetailFromListing(productRecord, ui.detail, locale);
    const extras = productSource === "slug"
      ? await getCmsProductDetailExtras(slug, ui.detail)
      : await getCmsProductDetailExtrasByLegacyPath(legacyPath, ui.detail);

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

  const cmsRedirect = await getCmsRedirectBySourcePath(legacyPath);
  if (cmsRedirect) {
    const targetProductSlug = productSlugFromTarget(cmsRedirect.targetPath);
    if (targetProductSlug && targetProductSlug !== slug) {
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

    notFound();
  }

  notFound();
}

function productLegacyPath(slug: string) {
  return `${productsPrefix}/${slug}`.replace(/\/+/g, "/");
}


function productSlugFromTarget(targetPath: string) {
  const match = targetPath.match(/^\/products\/([^/?#]+)(?:[?#].*)?$/);
  return match?.[1] ?? null;
}

function categorySlugFromTarget(targetPath: string) {
  const match = targetPath.match(/^\/products\/category\/([^/?#]+)(?:[?#].*)?$/);
  return match?.[1] ?? null;
}
