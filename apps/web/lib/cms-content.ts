import config from "@payload-config";
import { getPayload } from "payload";

import {
  defaultHomeHeroSlides,
  defaultHomeProductSectionContent,
  type AboutSectionContent,
  type ApplicationSectionContent,
  type FinalCtaContent,
  type FooterContent,
  type HomeFeaturedProduct,
  type HeaderTopBarContent,
  type HomeHeroSlide,
  type HomeHeroUiContent,
  type HomeSectionVisibility,
  type HomeProductSectionContent,
  type NewsSectionContent,
  type TrustBarContent,
} from "@/lib/home-defaults";
import { productNavGroups as defaultProductNavGroups, type NavItem, type ProductNavGroup } from "@/lib/home-data";
import { contact as defaultContactSettings, type ContactSettings } from "@/lib/contact";
import { mediaUrl } from "@/lib/cms-media";
import { localizedPath, type SiteLocale } from "@/lib/i18n";
import {
  aboutSectionFromSettings,
  chromeBrandingFromSettings,
  combineCmsSettings,
  applicationSectionFromSettings,
  contactFromSettings,
  chromeUiFromSettings,
  finalCtaFromSettings,
  languageOptionsFromSettings,
  footerFromSettings,
  homeHeroUiFromSettings,
  homeSectionVisibilityFromSettings,
  homeSeoFromSettings,
  navItemsFromSettings,
  newsSectionFromSettings,
  pageUiFromSettings,
  productSectionFromSettings,
  productUiFromSettings,
  topBarFromSettings,
  trustBarFromSettings,
  type ChromeBrandingContent,
  type CmsSiteSetting,
} from "@/lib/cms-site-settings";
import type { CatalogProduct, ProductCategory as CatalogCategory } from "@/lib/product-catalog";
import { getRequestLocale } from "@/lib/server-locale";
import type { ProductDetailUiContent, ProductUiContent } from "@/lib/product-ui";
import { specValue } from "@/lib/product-ui";
import { formatSeoTemplate, type ChromeUiContent, type PageUiContent, type SiteLanguageOption } from "@/lib/site-ui";
import { articles as fallbackArticles, type Article as SiteArticle } from "@/lib/site-content";
import type { Article as PayloadArticle, Media, Page as PayloadPage, Product as PayloadProduct, ProductCategory as PayloadProductCategory } from "@/payload-types";
import type {
  BoomType,
  Product,
  ProductApplication,
  ProductCategory,
  ProductListingCategoryMeta,
  ProductSpecItem,
  ProductStatus,
} from "@/types/product-listing";

type CmsCategory = PayloadProduct["category"] | string;
type CmsBoomType = string;
type CmsApplication = string;
type CmsAvailability = string;
type CmsMenuGroup = string;
type CmsIconName = string;

type CmsProductCategory = {
  id: number;
  title: string;
  slug: string;
  parentCategory?: number | CmsProductCategory | null;
  shortTitle?: string | null;
  legacyPath?: string | null;
  pageType?: "product-listing" | "seo-landing" | "redirect-only" | null;
  description?: string | null;
  image?: number | Media | null;
  iconName?: CmsIconName | null;
  menuGroup?: CmsMenuGroup | null;
  listingGroup?: ProductCategory | null;
  showInMegaMenu?: boolean | null;
  showOnHome?: boolean | null;
  showInProductFilters?: boolean | null;
  sortOrder?: number | null;
  bannerLabel?: string | null;
  bannerTitle?: string | null;
  bannerImage?: number | Media | null;
  bannerParagraphs?: { text?: string | null; id?: string | null }[] | null;
  defaultCategoryFilters?: string[] | null;
  defaultCapacityFilters?: string[] | null;
  defaultBoomTypeFilters?: string[] | null;
  defaultApplicationFilters?: string[] | null;
  defaultAvailabilityFilters?: string[] | null;
};

type CmsProduct = PayloadProduct & {
  categoryRef?: number | CmsProductCategory | null;
  availability?: CmsAvailability | null;
  capacityValue?: number | null;
  boomType?: CmsBoomType | null;
  applications?: CmsApplication[] | null;
  tags?: { label?: string | null; id?: string | null }[] | null;
  showInProductList?: boolean | null;
  showOnHome?: boolean | null;
  isFeatured?: boolean | null;
  popularity?: number | null;
  sortOrder?: number | null;
  keyFeatures?: { title?: string | null; text?: string | null; id?: string | null }[] | null;
  applicationCards?: {
    title?: string | null;
    text?: string | null;
    image?: number | Media | null;
    imagePath?: string | null;
    iconName?: string | null;
    sortOrder?: number | null;
    id?: string | null;
  }[] | null;
  faqs?: { question?: string | null; answer?: string | null; id?: string | null }[] | null;
  gallery?: { image: number | Media; label?: string | null; id?: string | null }[] | null;
  detailHotspots?: { label?: string | null; id?: string | null }[] | null;
  drawingImages?: { image: number | Media; label?: string | null; id?: string | null }[] | null;
  documents?: { title?: string | null; file?: number | Media | null; url?: string | null; id?: string | null }[] | null;
  sourceUrl?: string | null;
  importSource?: "manual" | "legacy-site" | "ai-assisted" | null;
  parameterReviewStatus?: "needs-review" | "in-review" | "verified-from-source" | "changes-required" | null;
  frontendContentStatus?: "ready" | "needs-content" | "needs-parameter-review" | string | null;
};

export type ProductDetailCmsExtras = {
  features: { title: string; text: string }[];
  applicationCards: { title: string; text: string; image: string; iconName: string; sortOrder: number }[];
  gallery: { image: string; label: string }[];
  hotspots: string[];
  faqs: { question: string; answer: string }[];
  documents: { title: string; href: string }[];
};

export type CmsProductSeo = {
  title?: string | null;
  description?: string | null;
  canonicalPath?: string | null;
};

export type CmsProductCategorySeo = {
  title?: string | null;
  description?: string | null;
  canonicalPath?: string | null;
  slug?: string | null;
};

export type CmsProductListingSeo = {
  title: string;
  description: string;
  canonicalPath: string;
};

export type CmsHomeSeo = {
  title: string;
  description: string;
  canonicalPath: string;
};

export type CmsRedirect = {
  targetPath: string;
  redirectType: "permanent" | "temporary";
};

export type HomeContent = {
  heroSlides: HomeHeroSlide[];
  heroUi: HomeHeroUiContent;
  productCatalog: CatalogCategory[];
  featuredProducts: HomeFeaturedProduct[];
  articles: SiteArticle[];
  productSection: HomeProductSectionContent;
  trustBar: TrustBarContent;
  applicationSection: ApplicationSectionContent;
  aboutSection: AboutSectionContent;
  newsSection: NewsSectionContent;
  sectionVisibility: HomeSectionVisibility;
};

export type ProductListingContent = {
  products: Product[];
  categories: ProductListingCategoryMeta[];
  ui: ProductUiContent["listing"];
};

export type ChromeContent = {
  navItems: NavItem[];
  productNavGroups: ProductNavGroup[];
  contact: ContactSettings;
  topBar: HeaderTopBarContent;
  footer: FooterContent;
  finalCta: FinalCtaContent;
  languageOptions: SiteLanguageOption[];
  chromeUi: ChromeUiContent;
  branding: ChromeBrandingContent;
};

const categoryToListing: Record<string, ProductCategory> = {
  "telescopic-boom-truck-mounted-cranes": "Truck Mounted Crane",
  "knuckle-boom-truck-mounted-cranes": "Truck Mounted Crane",
  "knuckle-boom-marine-cranes": "Marine Crane",
  "telescopic-boom-marine-cranes": "Marine Crane",
  "aerial-work-platform": "Aerial Work Platform",
  "pickup-cranes": "Pickup Truck Crane",
  "forklift-fly-jib-cranes": "Forklift Fly Jib Crane",
  "mini-dumpers": "Mini Dumper",
  "crane-accessories": "Crane Accessories",
  "other-cranes": "Crane Accessories",
};

const categoryToCatalogName: Record<string, string> = {
  "telescopic-boom-truck-mounted-cranes": "Telescopic Boom Truck Mounted Cranes",
  "knuckle-boom-truck-mounted-cranes": "Knuckle Boom Truck Mounted Cranes",
  "knuckle-boom-marine-cranes": "Knuckle Boom Marine Cranes",
  "telescopic-boom-marine-cranes": "Telescopic Boom Marine Cranes",
  "aerial-work-platform": "Aerial Work Platform",
  "pickup-cranes": "Pickup Cranes",
  "forklift-fly-jib-cranes": "Forklift Fly Jib Cranes",
  "mini-dumpers": "Mini Dumpers",
  "crane-accessories": "Crane Accessories",
  "other-cranes": "Other Cranes",
};

const categoryFallbackImages: Record<string, string> = {
  "telescopic-boom-truck-mounted-cranes": "/images/oldsite/products/5-ton-telescopic-boom-truck-mounted-crane.png",
  "knuckle-boom-truck-mounted-cranes": "/images/oldsite/products/4-ton-knuckle-boom-truck-mounted-crane.png",
  "knuckle-boom-marine-cranes": "/images/oldsite/products/5-ton-knuckle-boom-marine-crane.png",
  "telescopic-boom-marine-cranes": "/images/oldsite/products/5-ton-telescopic-boom-marine-crane.png",
  "aerial-work-platform": "/images/products/aerial-work-platform.png",
  "pickup-cranes": "/images/oldsite/products/800-kg-pickup-crane-with-outriggers.png",
  "forklift-fly-jib-cranes": "/images/oldsite/products/4-ton-forklift-fly-jib-crane.png",
  "mini-dumpers": "/images/oldsite/products/2-ton-mini-dumper.png",
  "crane-accessories": "/images/products/crane-accessories.png",
  "other-cranes": "/images/oldsite/products/zm75-timber-crane.png",
};

const boomTypeLabels: Record<CmsBoomType, BoomType> = {
  "telescopic-boom": "telescopic-boom",
  "knuckle-boom": "knuckle-boom",
  "straight-boom": "straight-boom",
  "fly-jib": "fly-jib",
  "custom-solution": "custom-solution",
  "Telescopic Boom": "telescopic-boom",
  "Knuckle Boom": "knuckle-boom",
  "Straight Boom": "straight-boom",
  "Fly Jib": "fly-jib",
  "Custom Solution": "custom-solution",
};

const applicationLabels: Record<CmsApplication, ProductApplication> = {
  construction: "construction",
  marine: "marine",
  logistics: "logistics",
  agriculture: "agriculture",
  mining: "mining",
  "municipal-engineering": "municipal-engineering",
  Construction: "construction",
  Marine: "marine",
  Logistics: "logistics",
  Agriculture: "agriculture",
  Mining: "mining",
  "Municipal Engineering": "municipal-engineering",
};

const availabilityLabels: Record<CmsAvailability, ProductStatus> = {
  "in-stock": "in-stock",
  "best-seller": "best-seller",
  customizable: "customizable",
  "pre-order": "pre-order",
  "In Stock": "in-stock",
  "Best Seller": "best-seller",
  Customizable: "customizable",
  "Pre-Order": "pre-order",
};

type CmsLocale = SiteLocale;

async function getActiveLocale(): Promise<CmsLocale> {
  return getRequestLocale();
}

const articleCategoryLabels: Record<string, string> = {
  "company-news": "Company News",
  "product-news": "Product News",
  "industry-insight": "Industry Insight",
};


export async function getChromeContent(): Promise<ChromeContent> {
  const [settings, categories, navigationPages, locale] = await Promise.all([
    getCmsSiteSettings(),
    getPublishedCmsCategories(),
    getPublishedCmsNavigationPages(),
    getActiveLocale(),
  ]);
  const contact = contactFromSettings(settings);

  return {
    navItems: mergeNavigationPages(navItemsFromSettings(settings), navigationPages).map((item) => ({
      ...item,
      href: localizeCmsHref(item.href, locale),
    })),
    productNavGroups: (categories.length > 0 ? cmsCategoriesToProductNavGroups(categories, settings) : defaultProductNavGroups).map((group) => ({
      ...group,
      href: localizeCmsHref(group.href, locale),
      links: group.links.map((link) => ({ ...link, href: localizeCmsHref(link.href, locale) })),
    })),
    contact: {
      ...contact,
      contactPath: localizeCmsHref(contact.contactPath, locale),
      quotePath: localizeCmsHref(contact.quotePath, locale),
    },
    topBar: localizeTopBar(topBarFromSettings(settings), locale),
    footer: localizeFooter(footerFromSettings(settings), locale),
    finalCta: localizeFinalCta(finalCtaFromSettings(settings), locale),
    languageOptions: languageOptionsFromSettings(settings),
    chromeUi: chromeUiFromSettings(settings),
    branding: chromeBrandingFromSettings(settings),
  };
}

export async function getContactContent(): Promise<ContactSettings> {
  return contactFromSettings(await getCmsSiteSettings());
}

export async function getProductListingProducts(): Promise<Product[]> {
  const content = await getProductListingContent();
  return content.products;
}

export async function getProductListingContent(): Promise<ProductListingContent> {
  const [products, categories, settings, locale] = await Promise.all([getPublishedCmsProducts(), getPublishedCmsCategories(), getCmsSiteSettings(), getActiveLocale()]);
  const quotePath = localizeCmsHref(settings?.quotePath || defaultContactSettings.quotePath, locale);
  const ui = productUiFromSettings(settings);

  return {
    products: products
      .filter((product) => product.showInProductList !== false && productHasFrontendContent(product))
      .map((product) => cmsProductToListingProduct(product, quotePath, ui, locale)),
    categories: categories.length > 0 ? categories.map((category) => cmsCategoryToListingMeta(category, categories)) : [],
    ui: ui.listing,
  };
}

export async function getCmsListingProductBySlug(slug: string): Promise<Product | null> {
  const [product, settings, locale] = await Promise.all([getCmsProductBySlug(slug), getCmsSiteSettings(), getActiveLocale()]);
  const ui = productUiFromSettings(settings);
  const quotePath = localizeCmsHref(settings?.quotePath || defaultContactSettings.quotePath, locale);
  return product ? cmsProductToListingProduct(product, quotePath, ui, locale) : null;
}

export async function getCmsListingProductByLegacyPath(legacyPath: string): Promise<Product | null> {
  const [product, settings, locale] = await Promise.all([getCmsProductByLegacyPath(legacyPath), getCmsSiteSettings(), getActiveLocale()]);
  const ui = productUiFromSettings(settings);
  const quotePath = localizeCmsHref(settings?.quotePath || defaultContactSettings.quotePath, locale);
  return product ? cmsProductToListingProduct(product, quotePath, ui, locale) : null;
}


export async function getProductUiContent(): Promise<ProductUiContent> {
  return productUiFromSettings(await getCmsSiteSettings());
}

export async function getPageUiContent(): Promise<PageUiContent> {
  return pageUiFromSettings(await getCmsSiteSettings());
}

export async function getCmsProductDetailExtras(slug: string, ui?: ProductDetailUiContent): Promise<ProductDetailCmsExtras | null> {
  const product = await getCmsProductBySlug(slug);
  return product ? cmsProductToDetailExtras(product, ui) : null;
}

export async function getCmsProductDetailExtrasByLegacyPath(legacyPath: string, ui?: ProductDetailUiContent): Promise<ProductDetailCmsExtras | null> {
  const product = await getCmsProductByLegacyPath(legacyPath);
  return product ? cmsProductToDetailExtras(product, ui) : null;
}

export async function getCmsProductSeoBySlug(slug: string): Promise<CmsProductSeo | null> {
  const [product, settings] = await Promise.all([getCmsProductBySlug(slug), getCmsSiteSettings()]);
  if (!product) return null;
  const fallbacks = pageUiFromSettings(settings).seoFallbacks;
  return {
    title: cleanSeoText(product.seo?.metaTitle) || product.title,
    description: cleanSeoText(product.seo?.metaDescription) || productIntro(product, formatSeoTemplate(fallbacks.productDescriptionTemplate, { product: product.title })),
    canonicalPath: cmsCanonicalPath(product.seo?.canonicalPath, productPublicPath(product)),
  };
}

export async function getCmsProductSeoByLegacyPath(legacyPath: string): Promise<CmsProductSeo | null> {
  const [product, settings] = await Promise.all([getCmsProductByLegacyPath(legacyPath), getCmsSiteSettings()]);
  if (!product) return null;
  const fallbacks = pageUiFromSettings(settings).seoFallbacks;
  const normalizedPath = normalizeLegacyPath(legacyPath);
  return {
    title: cleanSeoText(product.seo?.metaTitle) || product.title,
    description: cleanSeoText(product.seo?.metaDescription) || productIntro(product, formatSeoTemplate(fallbacks.productDescriptionTemplate, { product: product.title })),
    canonicalPath: cmsCanonicalPath(product.seo?.canonicalPath, normalizedPath),
  };
}

export async function getCmsProductCategorySeoBySlug(slug: string): Promise<CmsProductCategorySeo | null> {
  const [category, settings] = await Promise.all([getCmsProductCategoryBySlug(slug), getCmsSiteSettings()]);
  if (!category) return null;
  const fallbacks = pageUiFromSettings(settings).seoFallbacks;
  return {
    title: cleanSeoText(category.seo?.metaTitle) || category.bannerTitle || category.title,
    description: cleanSeoText(category.seo?.metaDescription) || category.description || formatSeoTemplate(fallbacks.productCategoryDescriptionTemplate, { category: category.title }),
    canonicalPath: cmsCanonicalPath(category.seo?.canonicalPath, categoryPublicPath(category as CmsProductCategory)),
    slug: category.slug,
  };
}

export async function getCmsProductCategorySeoByLegacyPath(legacyPath: string): Promise<CmsProductCategorySeo | null> {
  const [category, settings] = await Promise.all([getCmsProductCategoryByLegacyPath(legacyPath), getCmsSiteSettings()]);
  if (!category) return null;
  const fallbacks = pageUiFromSettings(settings).seoFallbacks;
  const normalizedPath = normalizeLegacyPath(legacyPath);
  return {
    title: cleanSeoText(category.seo?.metaTitle) || category.bannerTitle || category.title,
    description: cleanSeoText(category.seo?.metaDescription) || category.description || formatSeoTemplate(fallbacks.productCategoryDescriptionTemplate, { category: category.title }),
    canonicalPath: cmsCanonicalPath(category.seo?.canonicalPath, normalizedPath),
    slug: category.slug,
  };
}

export async function getCmsProductListingSeo(): Promise<CmsProductListingSeo> {
  const settings = await getCmsSiteSettings();
  const listingSettings = settings?.productListingUi;
  const listingUi = productUiFromSettings(settings).listing;
  const fallbacks = pageUiFromSettings(settings).seoFallbacks;
  const fallbackDescription = listingUi.defaultBanner.paragraphs
    .flatMap((paragraph) => paragraph.segments.map((segment) => segment.text))
    .join(" ")
    .trim();

  return {
    title: listingSettings?.metaTitle || listingUi.defaultBanner.title || fallbacks.productsTitle,
    description: listingSettings?.metaDescription || fallbackDescription || fallbacks.productsDescription,
    canonicalPath: cmsCanonicalPath(listingSettings?.canonicalPath, "/products"),
  };
}

export async function getCmsHomeSeo(): Promise<CmsHomeSeo> {
  const seo = homeSeoFromSettings(await getCmsSiteSettings());
  return {
    title: seo.metaTitle,
    description: seo.metaDescription,
    canonicalPath: cmsCanonicalPath(seo.canonicalPath, "/"),
  };
}

export async function getCmsRedirectBySourcePath(sourcePath: string): Promise<CmsRedirect | null> {
  const normalizedPath = normalizeLegacyPath(sourcePath);

  try {
    const payload = await getPayload({ config });
    const result = await payload.find({
      collection: "redirects",
      depth: 0,
      limit: 1,
      where: { and: [{ sourcePath: { equals: normalizedPath } }, { status: { equals: "published" } }] },
    });
    const redirect = result.docs[0];
    const targetPath = typeof redirect?.targetPath === "string" ? redirect.targetPath.trim() : "";
    if (!targetPath || targetPath === normalizedPath) return null;

    return {
      targetPath,
      redirectType: redirect.redirectType === "temporary" ? "temporary" : "permanent",
    };
  } catch {
    return null;
  }
}

export async function getHomeContent(): Promise<HomeContent> {
  const [settings, products, articles, locale] = await Promise.all([
    getCmsSiteSettings(),
    getPublishedCmsProducts(),
    getPublishedCmsArticles(),
    getActiveLocale(),
  ]);

  const applicationSection = applicationSectionFromSettings(settings);
  const aboutSection = aboutSectionFromSettings(settings);
  const newsSection = newsSectionFromSettings(settings);
  const productSection = productSectionFromSettings(settings);

  return {
    heroSlides: heroSlidesFromSettings(settings, locale),
    heroUi: homeHeroUiFromSettings(settings),
    productCatalog: cmsProductsToCatalog(products, settings?.homeFeaturedProducts, locale, productSection),
    featuredProducts: cmsProductsToFeaturedProducts(products, settings?.homeFeaturedProducts, locale, productSection),
    articles: homeArticlesFromSettings(settings, articles.length > 0 ? articles : fallbackArticles, newsSection.maxArticles),
    productSection: { ...productSection, featuredCtaHref: localizeCmsHref(productSection.featuredCtaHref, locale) },
    trustBar: trustBarFromSettings(settings),
    applicationSection: { ...applicationSection, ctaHref: localizeCmsHref(applicationSection.ctaHref, locale) },
    aboutSection: { ...aboutSection, ctaHref: localizeCmsHref(aboutSection.ctaHref, locale) },
    newsSection: { ...newsSection, ctaHref: localizeCmsHref(newsSection.ctaHref, locale) },
    sectionVisibility: homeSectionVisibilityFromSettings(settings),
  };
}

function homeArticlesFromSettings(settings: CmsSiteSetting | null, latestArticles: SiteArticle[], maxArticles = 4): SiteArticle[] {
  const pageUi = pageUiFromSettings(settings);
  const limit = Math.min(4, Math.max(1, maxArticles));
  const selectedArticles = settings?.homeFeaturedArticles?.flatMap((article) => {
    if (!article || typeof article === "number") return [];
    if (article.status !== "published") return [];
    return [cmsArticleToSiteArticle(article as PayloadArticle, pageUi)];
  }) ?? [];

  return (selectedArticles.length > 0 ? selectedArticles : latestArticles).slice(0, limit);
}

async function getPublishedCmsProducts(): Promise<CmsProduct[]> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "products",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 300,
      sort: "sortOrder",
      where: { status: { equals: "published" } },
    });
    return result.docs as CmsProduct[];
  } catch {
    return [];
  }
}


async function getPublishedCmsCategories(): Promise<CmsProductCategory[]> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "product-categories",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 100,
      sort: "sortOrder",
      where: { status: { equals: "published" } },
    });
    return (result.docs as CmsProductCategory[]).filter(isPublicCategory);
  } catch {
    return [];
  }
}

async function getPublishedCmsNavigationPages(): Promise<NavItem[]> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "pages",
      fallbackLocale: "en",
      locale,
      depth: 0,
      limit: 50,
      sort: "sortOrder",
      where: {
        and: [
          { status: { equals: "published" } },
          { showInNavigation: { equals: true } },
        ],
      },
    });

    return (result.docs as PayloadPage[]).flatMap((page) => {
      const href = page.legacyPath && page.legacyPath.startsWith("/") ? page.legacyPath : `/${page.slug}`;
      return page.title && href ? [{ label: page.title, href, hasMenu: false, sortOrder: Number(page.sortOrder ?? 100) }] : [];
    });
  } catch {
    return [];
  }
}

async function getCmsProductBySlug(slug: string): Promise<CmsProduct | null> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "products",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 1,
      where: { and: [{ slug: { equals: slug } }, { status: { equals: "published" } }] },
    });
    return (result.docs[0] as CmsProduct | undefined) ?? null;
  } catch {
    return null;
  }
}

async function getCmsProductByLegacyPath(legacyPath: string): Promise<CmsProduct | null> {
  const normalizedPath = normalizeLegacyPath(legacyPath);

  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "products",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 1,
      where: {
        and: [
          {
            or: [
              { legacyPath: { equals: normalizedPath } },
              { "legacyAliases.path": { equals: normalizedPath } },
            ],
          },
          { status: { equals: "published" } },
        ],
      },
    });
    const product = result.docs[0] as CmsProduct | undefined;
    if (product) return product;

    const slug = slugFromLegacyProductPath(normalizedPath);
    if (!slug) return null;

    const slugResult = await payload.find({
      collection: "products",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 1,
      where: { and: [{ slug: { equals: slug } }, { status: { equals: "published" } }] },
    });
    return (slugResult.docs[0] as CmsProduct | undefined) ?? null;
  } catch {
    return null;
  }
}

async function getCmsProductCategoryBySlug(slug: string): Promise<PayloadProductCategory | null> {
  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "product-categories",
      fallbackLocale: "en",
      locale,
      depth: 1,
      limit: 1,
      where: { and: [{ slug: { equals: slug } }, { status: { equals: "published" } }] },
    });
    const category = result.docs[0] as CmsProductCategory | undefined;
    return category && isPublicCategory(category) ? (category as PayloadProductCategory) : null;
  } catch {
    return null;
  }
}

async function getCmsProductCategoryByLegacyPath(legacyPath: string): Promise<PayloadProductCategory | null> {
  const normalizedPath = normalizeLegacyPath(legacyPath);

  try {
    const [payload, locale] = await Promise.all([getPayload({ config }), getActiveLocale()]);
    const result = await payload.find({
      collection: "product-categories",
      fallbackLocale: "en",
      locale,
      depth: 1,
      limit: 1,
      where: {
        and: [
          {
            or: [
              { legacyPath: { equals: normalizedPath } },
              { slug: { equals: normalizedPath.replace(/^\//, "") } },
              { "legacyAliases.path": { equals: normalizedPath } },
            ],
          },
          { status: { equals: "published" } },
        ],
      },
    });
    const category = result.docs[0] as CmsProductCategory | undefined;
    return category && isPublicCategory(category) ? (category as PayloadProductCategory) : null;
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

async function getPublishedCmsArticles(): Promise<SiteArticle[]> {
  try {
    const [payload, locale, settings] = await Promise.all([getPayload({ config }), getActiveLocale(), getCmsSiteSettings()]);
    const result = await payload.find({
      collection: "articles",
      fallbackLocale: "en",
      locale,
      depth: 2,
      limit: 4,
      sort: "-publishedAt",
      where: { status: { equals: "published" } },
    });
    const pageUi = pageUiFromSettings(settings);
    return (result.docs as PayloadArticle[]).map((article) => cmsArticleToSiteArticle(article, pageUi));
  } catch {
    return [];
  }
}

function cmsCategoriesToProductNavGroups(categories: CmsProductCategory[], settings: CmsSiteSetting | null): ProductNavGroup[] {
  const visibleCategories = categories.filter((category) => isPublicCategory(category) && category.showInMegaMenu !== false);
  const fallbackDescription = pageUiFromSettings(settings).seoFallbacks.productNavGroupDescription;
  const defaultGroupMeta: Record<string, { title: string; description: string; href: string; iconName: string; sortOrder: number }> = {
    "truck-mounted-crane": { title: "Truck Mounted Crane", description: "Road transport lifting families", href: "/products", iconName: "truck-crane", sortOrder: 10 },
    "marine-crane": { title: "Marine Crane", description: "Deck and port lifting choices", href: "/products", iconName: "marine-crane", sortOrder: 20 },
    "compact-attachments": { title: "Compact & Attachments", description: "Smaller equipment and add-ons", href: "/products", iconName: "attachments", sortOrder: 30 },
  };
  const groupMeta = { ...defaultGroupMeta };
  settings?.productMenuGroups?.forEach((group) => {
    const groupKey = group?.groupKey?.trim();
    if (!groupKey) return;
    groupMeta[groupKey] = {
      ...(groupMeta[groupKey] ?? defaultGroupMeta["compact-attachments"]),
      title: group.title || groupMeta[groupKey]?.title || humanizeKey(groupKey),
      description: group.description || groupMeta[groupKey]?.description || fallbackDescription,
      href: group.href || groupMeta[groupKey]?.href || "/products",
      iconName: group.iconName || groupMeta[groupKey]?.iconName || "attachments",
      sortOrder: group.sortOrder ?? groupMeta[groupKey]?.sortOrder ?? 100,
    };
  });

  visibleCategories.forEach((category) => {
    const groupKey = category.menuGroup?.trim();
    if (!groupKey || groupMeta[groupKey]) return;
    groupMeta[groupKey] = {
      title: humanizeKey(groupKey),
      description: category.description || fallbackDescription,
      href: "/products",
      iconName: category.iconName || "attachments",
      sortOrder: 100,
    };
  });

  return Object.keys(groupMeta)
    .sort((a, b) => groupMeta[a].sortOrder - groupMeta[b].sortOrder)
    .map((groupKey) => {
      const links = categories
        .filter((category) => isPublicCategory(category) && category.showInMegaMenu !== false && (category.menuGroup || "compact-attachments") === groupKey)
        .sort((a, b) => (a.sortOrder ?? 100) - (b.sortOrder ?? 100))
        .map((category) => ({
          label: category.shortTitle || category.title,
          href: categoryPublicPath(category),
          iconName: category.iconName || undefined,
        }));
      const meta = groupMeta[groupKey];

      return { ...meta, links };
    })
    .filter((group) => group.links.length > 0);
}

function mergeNavigationPages(baseItems: NavItem[], pageItems: NavItem[]): NavItem[] {
  const merged = new Map<string, NavItem>();

  baseItems.forEach((item, index) => {
    merged.set(navigationKey(item.label), { ...item, sortOrder: item.sortOrder ?? (index + 1) * 10 });
  });

  pageItems.forEach((pageItem) => {
    const key = navigationKey(pageItem.label);
    const current = merged.get(key);
    if (current) {
      merged.set(key, {
        ...current,
        href: pageItem.href,
        hasMenu: current.href.startsWith("/products") ? current.hasMenu : false,
        sortOrder: pageItem.sortOrder ?? current.sortOrder,
      });
      return;
    }

    merged.set(key, { ...pageItem, sortOrder: pageItem.sortOrder ?? 100 });
  });

  return Array.from(merged.values())
    .sort((a, b) => (a.sortOrder ?? 100) - (b.sortOrder ?? 100))
    .map(({ label, href, hasMenu }) => ({ label, href, hasMenu }));
}

function navigationKey(label: string) {
  return label.trim().toLowerCase().replace(/\s+/g, " ");
}

function cmsCategoryToListingMeta(category: CmsProductCategory, categories: CmsProductCategory[] = []): ProductListingCategoryMeta {
  const bannerParagraphs = category.bannerParagraphs?.map((item) => item.text).filter((item): item is string => Boolean(item)) ?? [];
  const includedCategorySlugs = categories
    .filter((item) => categoryParentId(item) === category.id)
    .map((item) => item.slug);
  const defaultFilters = categoryDefaultFilters(category);

  return {
    label: category.title,
    slug: category.slug,
    legacyPath: category.legacyPath || undefined,
    includedCategorySlugs: includedCategorySlugs.length > 0 ? includedCategorySlugs : undefined,
    showInProductFilters: category.showInProductFilters !== false,
    defaultFilters,
    bannerLabel: category.bannerLabel || undefined,
    bannerTitle: category.bannerTitle || category.title,
    bannerImage: mediaUrl(category.bannerImage, "card") ?? categoryImage(category, "card") ?? undefined,
    bannerImageAlt: category.title,
    bannerParagraphs: bannerParagraphs.length > 0 ? bannerParagraphs : (category.description ? [category.description] : undefined),
  };
}

function categoryDefaultFilters(category: CmsProductCategory): ProductListingCategoryMeta["defaultFilters"] {
  const filters = {
    categories: cleanStringArray(category.defaultCategoryFilters),
    capacities: cleanStringArray(category.defaultCapacityFilters),
    boomTypes: cleanStringArray(category.defaultBoomTypeFilters),
    applications: cleanStringArray(category.defaultApplicationFilters),
    availability: cleanStringArray(category.defaultAvailabilityFilters),
  };
  const hasFilters = Object.values(filters).some((items) => items.length > 0);

  return hasFilters ? filters : undefined;
}

function categoryParentId(category: CmsProductCategory) {
  if (!category.parentCategory) return null;
  if (typeof category.parentCategory === "number") return category.parentCategory;
  return category.parentCategory.id;
}

function cleanStringArray(values?: string[] | null) {
  return values?.map((value) => value.trim()).filter(Boolean) ?? [];
}

function heroSlidesFromSettings(settings: CmsSiteSetting | null, locale?: CmsLocale): HomeHeroSlide[] {
  const slides = settings?.homeHeroSlides
    ?.map((slide) => ({
      title: slide.title?.trim() ?? "",
      eyebrow: slide.eyebrow?.trim() || undefined,
      description: slide.description?.trim() || undefined,
      image: mediaUrl(slide.image, "hero") ?? slide.imagePath?.trim() ?? "",
      mobileImage: mediaUrl(slide.mobileImage, "hero") ?? slide.mobileImagePath?.trim() ?? undefined,
      primaryCtaLabel: slide.primaryCtaLabel?.trim() || undefined,
      primaryCtaHref: slide.primaryCtaHref ? localizeCmsHref(slide.primaryCtaHref, locale) : undefined,
      secondaryCtaLabel: slide.secondaryCtaLabel?.trim() || undefined,
      secondaryCtaHref: slide.secondaryCtaHref ? localizeCmsHref(slide.secondaryCtaHref, locale) : undefined,
    }))
    .filter((slide) => slide.title && slide.image);

  if (slides && slides.length > 0) return slides;
  return defaultHomeHeroSlides.map((slide) => ({
    ...slide,
    primaryCtaHref: slide.primaryCtaHref ? localizeCmsHref(slide.primaryCtaHref, locale) : undefined,
    secondaryCtaHref: slide.secondaryCtaHref ? localizeCmsHref(slide.secondaryCtaHref, locale) : undefined,
  }));
}

function cmsProductsToCatalog(
  products: CmsProduct[],
  featuredProducts?: (number | CmsProduct)[] | null,
  locale?: CmsLocale,
  productSection: HomeProductSectionContent = defaultHomeProductSectionContent,
): CatalogCategory[] {
  const featuredRanks = relationRanks(featuredProducts);
  const orderedProducts = [...products].sort((a, b) => {
    const selectedRankDiff = relationRank(a.id, featuredRanks) - relationRank(b.id, featuredRanks);
    const featuredDiff = Number(featuredRanks.has(b.id) || b.isFeatured) - Number(featuredRanks.has(a.id) || a.isFeatured);
    return selectedRankDiff || featuredDiff || categorySortOrder(a) - categorySortOrder(b) || (a.sortOrder ?? 100) - (b.sortOrder ?? 100) || (b.popularity ?? 50) - (a.popularity ?? 50);
  });
  const groups = new Map<string, CatalogCategory>();

  orderedProducts.forEach((product) => {
    if (product.showOnHome === false || !productHasFrontendContent(product)) return;

    const category = productCategoryDoc(product);
    if (category && (!isPublicCategory(category) || category.showOnHome === false)) return;

    const key = category ? `cms-${category.id}` : productCategoryKey(product);
    const name = category?.title ?? categoryToCatalogName[key] ?? categoryToCatalogName[String(product.category)] ?? "Other Cranes";
    const image = categoryImage(category, "card") ?? productImage(product, "card") ?? categoryFallbackImages[productCategoryKey(product)] ?? "/images/products/crane-accessories.png";
    const href = localizeCmsHref(category ? categoryPublicPath(category) : `/products#${productCategoryKey(product)}`, locale);
    const group = groups.get(key) ?? { name, image, href, products: [] };
    group.image = group.image || image;
    group.products.push(cmsProductToCatalogProduct(product, locale));
    groups.set(key, group);
  });

  return Array.from(groups.values())
    .map((group) => ({ ...group, products: group.products.slice(0, productSection.maxCategoryProducts) }))
    .filter((group) => group.products.length > 0);
}

function cmsProductToCatalogProduct(product: CmsProduct, locale?: CmsLocale): CatalogProduct {
  const fallbackModel = product.model || product.title.split(" ")[0] || "BOB-LIFT";
  return {
    name: product.title,
    model: fallbackModel,
    image: productImage(product, "card") ?? categoryImage(productCategoryDoc(product), "card") ?? categoryFallbackImages[productCategoryKey(product)] ?? "/images/products/crane-accessories.png",
    capacity: product.capacity || capacityLabel(product),
    type: product.productType || boomTypeLabel(product),
    intro: productIntro(product, `${product.title} is ready for project-specific lifting requirements.`),
    href: localizeCmsHref(productPublicPath(product), locale),
    detailPath: localizeCmsHref(productPublicPath(product), locale),
  };
}

function cmsProductsToFeaturedProducts(
  products: CmsProduct[],
  featuredProducts?: (number | CmsProduct)[] | null,
  locale?: CmsLocale,
  productSection: HomeProductSectionContent = defaultHomeProductSectionContent,
): HomeFeaturedProduct[] {
  const featuredRanks = relationRanks(featuredProducts);
  const hasMarkedFeatured = products.some((product) => product.isFeatured);
  const orderedProducts = [...products].sort((a, b) => {
    const selectedDiff = relationRank(a.id, featuredRanks) - relationRank(b.id, featuredRanks);
    const featuredDiff = Number(Boolean(b.isFeatured)) - Number(Boolean(a.isFeatured));
    return selectedDiff || featuredDiff || (b.popularity ?? 50) - (a.popularity ?? 50) || (a.sortOrder ?? 100) - (b.sortOrder ?? 100);
  });

  return orderedProducts
    .filter((product) => {
      if (product.showOnHome === false || !productHasFrontendContent(product)) return false;
      if (featuredRanks.size > 0) return featuredRanks.has(product.id);
      return hasMarkedFeatured ? Boolean(product.isFeatured) : true;
    })
    .slice(0, productSection.maxFeaturedProducts)
    .map((product) => {
      const catalogProduct = cmsProductToCatalogProduct(product, locale);
      return {
        name: catalogProduct.name,
        model: catalogProduct.model,
        image: catalogProduct.image,
        href: catalogProduct.detailPath ?? catalogProduct.href,
        capacity: catalogProduct.capacity,
        type: catalogProduct.type,
      };
    });
}

export function cmsProductToListingProduct(product: CmsProduct, quotePath = defaultContactSettings.quotePath, ui?: ProductUiContent, locale?: CmsLocale): Product {
  const category = listingCategory(product);
  const capacityValue = typeof product.capacityValue === "number" ? product.capacityValue : parseCapacityValue(product.capacity);
  const specs = productSpecs(product, capacityValue, category, ui);

  return {
    id: `cms-${product.id}`,
    slug: product.slug,
    name: product.title,
    model: product.model || product.title.split(" ")[0],
    category,
    categorySlug: productCategoryKey(product),
    capacity: product.capacity || capacityLabel(product),
    capacityValue,
    boomType: boomTypeLabel(product),
    applications: productApplications(product),
    status: productAvailability(product),
    image: productImage(product, "card") ?? categoryImage(productCategoryDoc(product), "card") ?? categoryFallbackImages[productCategoryKey(product)] ?? "/images/products/crane-accessories.png",
    shortDescription: productIntro(product, `${product.title} can be configured for different working conditions and export requirements.`),
    tags: productTags(product),
    specs,
    sourceUrl: product.sourceUrl || undefined,
    parameterReviewStatus: product.parameterReviewStatus || undefined,
    inquiryUrl: productInquiryUrl(quotePath, product.title, product.slug),
    detailUrl: localizeCmsHref(productPublicPath(product), locale),
    isFeatured: Boolean(product.isFeatured),
    createdAt: product.publishedAt ?? product.createdAt,
    popularity: product.popularity ?? 50,
  };
}


function productHasFrontendContent(product: CmsProduct) {
  return product.frontendContentStatus === "ready";
}

function productIntro(product: CmsProduct, fallback: string) {
  const text = product.intro || product.seo?.metaDescription || fallback;
  return isLegacySeoNoise(text) ? fallback : text;
}

function isLegacySeoNoise(text: string) {
  return /Multi Field Search|Product Keyword|Product Summary|Product Description Multi Field Search|HOME ABOUT US/i.test(text);
}

function cleanSeoText(text: string | null | undefined) {
  if (!text || isLegacySeoNoise(text)) return "";
  return text;
}

function cmsProductToDetailExtras(product: CmsProduct, ui?: ProductDetailUiContent): ProductDetailCmsExtras {
  return {
    features: product.keyFeatures?.flatMap((item) => item.title && item.text ? [{ title: item.title, text: item.text }] : []) ?? [],
    applicationCards: productApplicationCards(product),
    gallery: productGallery(product, ui),
    hotspots: product.detailHotspots?.map((item) => item.label).filter((item): item is string => Boolean(item)) ?? [],
    faqs: product.faqs?.flatMap((item) => item.question && item.answer ? [{ question: item.question, answer: item.answer }] : []) ?? [],
    documents: productDocuments(product),
  };
}

function cmsArticleToSiteArticle(article: PayloadArticle, pageUi: PageUiContent): SiteArticle {
  return {
    slug: article.slug,
    legacyPath: article.legacyPath ?? `/${article.slug}.html`,
    title: article.title,
    date: (article.publishedAt ?? article.createdAt).slice(0, 10),
    category: articleCategoryLabels[article.category] ?? article.category,
    image: mediaUrl(article.coverImage, "card") ?? "/images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg",
    excerpt: article.excerpt ?? article.seo?.metaDescription ?? pageUi.seoFallbacks.articleExcerpt,
    sections: [],
  };
}

function productCategoryDoc(product: CmsProduct) {
  return product.categoryRef && typeof product.categoryRef === "object" ? product.categoryRef : null;
}

function productCategoryKey(product: CmsProduct) {
  return productCategoryDoc(product)?.slug ?? String(product.category);
}

function listingCategory(product: CmsProduct): ProductCategory {
  const category = productCategoryDoc(product);
  return category?.title || categoryToListing[String(product.category)] || "Crane Accessories";
}

function categorySortOrder(product: CmsProduct) {
  return productCategoryDoc(product)?.sortOrder ?? 100;
}

function categoryImage(category: CmsProductCategory | null, size: "thumbnail" | "card" | "hero") {
  return mediaUrl(category?.image, size);
}

function isPublicCategory(category: Pick<CmsProductCategory, "pageType"> | null | undefined) {
  return category?.pageType !== "redirect-only";
}

function parameterReviewLabel(status: CmsProduct["parameterReviewStatus"], ui?: ProductUiContent) {
  if (status === "verified-from-source") return ui?.detail.reviewStatusLabels.verifiedFromSource ?? "Verified from source";
  if (status === "in-review") return ui?.detail.reviewStatusLabels.inReview ?? "In review";
  if (status === "changes-required") return ui?.detail.reviewStatusLabels.changesRequired ?? "Changes required";
  return ui?.detail.reviewStatusLabels.needsReview ?? "Needs review";
}

function productSpecs(product: CmsProduct, capacityValue: number, category: ProductCategory, ui?: ProductUiContent): ProductSpecItem[] {
  const enteredSpecs = product.specs?.flatMap((spec) => spec.label && spec.value ? [{ label: spec.label, value: ui ? specValue(spec.value, ui.listing) : spec.value }] : []) ?? [];
  if (enteredSpecs.length > 0) return enteredSpecs;

  if (product.importSource === "legacy-site" || product.parameterReviewStatus) {
    return [
      { label: ui?.listing.specLabels["Specification Status"] ?? "Specification Status", value: parameterReviewLabel(product.parameterReviewStatus, ui) },
    ];
  }

  const lift = product.capacity || (capacityValue > 0 ? `${formatSpecNumber(capacityValue)} Ton` : "Custom");
  return [
    { label: "Max Lift", value: lift },
    { label: "Boom Length", value: estimateBoomLength(capacityValue, category) },
    { label: "Rotation", value: category === "Crane Accessories" ? "Custom" : "360°" },
  ];
}

function productApplications(product: CmsProduct): ProductApplication[] {
  const applications = product.applications?.map((item) => applicationLabels[item] ?? item).filter(Boolean) ?? [];
  return applications;
}

function productAvailability(product: CmsProduct): ProductStatus {
  const availability = product.availability ?? "customizable";
  return availabilityLabels[availability] ?? availability;
}

function productTags(product: CmsProduct) {
  const tags = product.tags?.map((item) => item.label).filter((item): item is string => Boolean(item)) ?? [];
  return tags;
}

function productApplicationCards(product: CmsProduct) {
  return product.applicationCards
    ?.flatMap((item) => {
      if (!item.title || !item.text) return [];
      const image = mediaUrl(item.image, "card") ?? item.imagePath;
      if (!image) return [];

      return [{
        title: item.title,
        text: item.text,
        image,
        iconName: item.iconName || "construction",
        sortOrder: item.sortOrder ?? 100,
      }];
    })
    .sort((a, b) => a.sortOrder - b.sortOrder) ?? [];
}

function productGallery(product: CmsProduct, ui?: ProductDetailUiContent) {
  const cover = productImage(product, "hero");
  const gallery = product.gallery?.flatMap((item) => {
    const image = mediaUrl(item.image, "card");
    return image ? [{ image, label: item.label || ui?.galleryDetailImageLabel || "Product detail image" }] : [];
  }) ?? [];
  const drawings = product.drawingImages?.flatMap((item) => {
    const image = mediaUrl(item.image, "card") ?? mediaUrl(item.image);
    return image ? [{ image, label: item.label || ui?.galleryDrawingLabel || "Specification drawing" }] : [];
  }) ?? [];
  return cover ? [{ image: cover, label: ui?.galleryMainImageLabel || "Main product view" }, ...gallery, ...drawings] : [...gallery, ...drawings];
}

function productDocuments(product: CmsProduct) {
  return product.documents?.flatMap((document) => {
    const href = mediaUrl(document.file) ?? document.url;
    return document.title && href ? [{ title: document.title, href }] : [];
  }) ?? [];
}

function productImage(product: CmsProduct, size: "thumbnail" | "card" | "hero") {
  return mediaUrl(product.coverImage, size);
}

function productPublicPath(product: CmsProduct) {
  return product.legacyPath && product.legacyPath.startsWith("/") ? product.legacyPath : `/products/${product.slug}`;
}

function categoryPublicPath(category: CmsProductCategory) {
  return category.legacyPath && category.legacyPath.startsWith("/") ? category.legacyPath : `/products/category/${category.slug}`;
}

function boomTypeLabel(product: CmsProduct): BoomType {
  if (product.boomType) return boomTypeLabels[product.boomType] ?? product.boomType;
  const category = productCategoryKey(product);
  if (category.includes("telescopic")) return "telescopic-boom";
  if (category.includes("knuckle")) return "knuckle-boom";
  if (category.includes("forklift")) return "fly-jib";
  return "custom-solution";
}

function capacityLabel(product: CmsProduct) {
  return typeof product.capacityValue === "number" && product.capacityValue > 0 ? `${formatSpecNumber(product.capacityValue)} tons` : "Custom";
}

function parseCapacityValue(value?: string | null) {
  const number = Number.parseFloat(String(value ?? "").replace(/[^0-9.]/g, ""));
  return Number.isFinite(number) ? number : 0;
}

function estimateBoomLength(capacityValue: number, category: ProductCategory) {
  if (category === "Crane Accessories" || category === "Mini Dumper") return "Custom";
  if (category === "Aerial Work Platform") return capacityValue > 0 ? `${formatSpecNumber(capacityValue)} M` : "Custom";
  if (category === "Forklift Fly Jib Crane") return `${formatSpecNumber(Math.max(2.5, capacityValue + 1.5))} M`;
  if (category === "Pickup Truck Crane") return `${formatSpecNumber(Math.max(3.2, capacityValue * 4.2))} M`;
  return `${formatSpecNumber(Math.max(6.3, capacityValue * 2.2 + 4.5))} M`;
}

function formatSpecNumber(value: number) {
  return Number.isInteger(value) ? `${value}` : value.toFixed(1).replace(/\.0$/, "");
}

function productInquiryUrl(quotePath: string, title: string, slug: string) {
  const separator = quotePath.includes("?") ? "&" : "?";
  return `${quotePath}${separator}product=${encodeURIComponent(title)}&productSlug=${encodeURIComponent(slug)}`;
}

function relationRanks(items: ((number | { id: number }) | null | undefined)[] | null | undefined) {
  const ranks = new Map<number, number>();
  items?.forEach((item, index) => {
    if (!item) return;
    const id = typeof item === "number" ? item : item.id;
    if (typeof id === "number" && !ranks.has(id)) ranks.set(id, index);
  });
  return ranks;
}

function relationRank(id: number, ranks: Map<number, number>) {
  return ranks.get(id) ?? Number.MAX_SAFE_INTEGER;
}

function humanizeKey(value: string) {
  return value
    .replace(/[-_]+/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function cmsCanonicalPath(value: string | null | undefined, fallback: string) {
  const path = value?.trim();
  return path?.startsWith("/") ? path : fallback;
}

function normalizeLegacyPath(legacyPath: string) {
  const normalized = legacyPath.startsWith("/") ? legacyPath : `/${legacyPath}`;
  return normalized.replace(/\/+/g, "/");
}

function slugFromLegacyProductPath(legacyPath: string) {
  const normalized = normalizeLegacyPath(legacyPath).replace(/^\//, "");
  if (!normalized || normalized.includes("/")) return null;
  return normalized.replace(/\.html?$/i, "");
}

function localizeCmsHref(href: string, locale?: CmsLocale) {
  if (!href.startsWith("/") || href.startsWith("//") || href.startsWith("/api/") || href.startsWith("/admin")) return href;
  return localizedPath(href, locale);
}

function localizeTopBar(topBar: HeaderTopBarContent, locale: CmsLocale): HeaderTopBarContent {
  return { ...topBar, linkHref: localizeCmsHref(topBar.linkHref, locale) };
}

function localizeFinalCta(finalCta: FinalCtaContent, locale: CmsLocale): FinalCtaContent {
  return { ...finalCta, buttonHref: localizeCmsHref(finalCta.buttonHref, locale) };
}

function localizeFooter(footer: FooterContent, locale: CmsLocale): FooterContent {
  return {
    ...footer,
    socialLinks: footer.socialLinks.map((link) => ({ ...link, href: localizeCmsHref(link.href, locale) })),
    groups: footer.groups.map((group) => ({
      ...group,
      links: group.links.map((link) => ({ ...link, href: localizeCmsHref(link.href, locale) })),
    })),
    legalLinks: footer.legalLinks.map((link) => ({ ...link, href: localizeCmsHref(link.href, locale) })),
  };
}
