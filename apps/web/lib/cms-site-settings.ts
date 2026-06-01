import { contact as defaultContactSettings, phoneToHref, type ContactSettings, whatsappToHref } from "@/lib/contact";
import {
  defaultAboutSectionContent,
  defaultFinalCtaContent,
  defaultFooterContent,
  defaultHeaderTopBarContent,
  defaultHomeHeroUiContent,
  defaultHomeSectionVisibility,
  defaultHomeSeoContent,
  defaultHomeProductSectionContent,
  defaultNewsSectionContent,
  defaultTrustBarContent,
  type AboutSectionContent,
  type FinalCtaContent,
  type FooterContent,
  type HeaderTopBarContent,
  type HomeHeroUiContent,
  type HomeSeoContent,
  type HomeSectionVisibility,
  type HomeProductSectionContent,
  type NewsSectionContent,
  type TrustBarContent,
} from "@/lib/home-defaults";
import { navItems as defaultNavItems, type NavItem } from "@/lib/home-data";
import { mediaUrl } from "@/lib/cms-media";
import type { ProductDetailUiSettings, ProductListingUiSettings } from "@/lib/cms-product-ui-settings";
import { defaultChromeUiContent, defaultLanguageOptions, defaultPageUiContent, type ChromeUiContent, type PageUiContent, type QuoteFormUiContent, type QuoteWorkflowItem, type SiteLanguageOption } from "@/lib/site-ui";
import type { Article as PayloadArticle, HomeSetting, Media, PageTextSetting, ProductDetailUi, ProductListingUi, SiteSetting } from "@/payload-types";

export type CmsSiteSetting = Partial<SiteSetting> & Partial<HomeSetting> & Partial<ProductListingUi> & Partial<ProductDetailUi> & Partial<PageTextSetting> & {
  homeHeroSlides?: { image?: number | Media | null; imagePath?: string | null; mobileImage?: number | Media | null; mobileImagePath?: string | null; id?: string | null }[] | null;
  homeFeaturedArticles?: (number | PayloadArticle)[] | null;
  logoImage?: number | Media | null;
  logoPath?: string | null;
  logoWidth?: number | null;
  logoHeight?: number | null;
  productMenuBackgroundImage?: number | Media | null;
  productMenuBackgroundImagePath?: string | null;
  wechatId?: string | null;
  wechatQr?: number | Media | null;
  quotePath?: string | null;
  inquirySubject?: string | null;
  defaultInquiryText?: string | null;
  headerTopBar?: Partial<HeaderTopBarContent> | null;
  navigationItems?: ({ label?: string | null; href?: string | null; hasMenu?: boolean | null; showInHeader?: boolean | null; sortOrder?: number | null } | null)[] | null;
  productMenuGroups?: ({ groupKey?: string | null; title?: string | null; description?: string | null; href?: string | null; iconName?: string | null; sortOrder?: number | null } | null)[] | null;
  homeSectionVisibility?: Partial<HomeSectionVisibility> | null;
  languageOptions?: ({
    locale?: string | null;
    label?: string | null;
    market?: string | null;
    flag?: string | null;
    direction?: "ltr" | "rtl" | null;
    translationStatus?: "not-started" | "in-progress" | "ready" | null;
    completionPercent?: number | null;
    enabled?: boolean | null;
    sortOrder?: number | null;
  } | null)[] | null;
  chromeUi?: Partial<ChromeUiContent> | null;
  homeHeroUi?: Partial<HomeHeroUiContent> | null;
  homeProductSection?: Partial<HomeProductSectionContent> | null;
  trustBar?: {
    label?: string | null;
    title?: string | null;
    description?: string | null;
    showIntroCard?: boolean | null;
    showMarkNames?: boolean | null;
    marks?: ({ name?: string | null; logoPath?: string | null; logoImage?: number | Media | null } | null)[] | null;
  } | null;
  aboutSection?: {
    label?: string | null;
    title?: string | null;
    text?: string | null;
    ctaLabel?: string | null;
    ctaHref?: string | null;
    showGallery?: boolean | null;
    showStats?: boolean | null;
    showTimeline?: boolean | null;
    showCta?: boolean | null;
    galleryThumbnailLabelTemplate?: string | null;
    videoLabel?: string | null;
    gallery?: ({ image?: number | Media | null; imagePath?: string | null; alt?: string | null; video?: boolean | null; videoUrl?: string | null } | null)[] | null;
    stats?: ({ value?: string | null; label?: string | null; iconName?: string | null } | null)[] | null;
    timeline?: ({ text?: string | null } | null)[] | null;
  } | null;
  newsSection?: Partial<NewsSectionContent> | null;
  finalCta?: Partial<FinalCtaContent> & { image?: number | Media | null; imagePath?: string | null } | null;
  footerIntro?: string | null;
  socialLinks?: ({ label?: string | null; href?: string | null } | null)[] | null;
  footerGroups?: ({ title?: string | null; links?: ({ label?: string | null; href?: string | null } | null)[] | null } | null)[] | null;
  footerContactTitle?: string | null;
  copyright?: string | null;
  legalLinks?: ({ label?: string | null; href?: string | null } | null)[] | null;
  productListingUi?: ProductListingUiSettings | null;
  productDetailUi?: ProductDetailUiSettings | null;
  homeSeo?: Partial<HomeSeoContent> | null;
  seoFallbacksUi?: DeepPartial<PageUiContent["seoFallbacks"]> | null;
  contactPageUi?: Partial<PageUiContent["contactPage"]> | null;
  quotePageUi?: Partial<Omit<PageUiContent["quotePage"], "workflowItems">> & { workflowItems?: (Partial<QuoteWorkflowItem> | null)[] | null } | null;
  quoteFormUi?: DeepPartial<PageUiContent["quoteForm"]> | null;
  newsPageUi?: DeepPartial<PageUiContent["newsPage"]> | null;
  newsDetailUi?: DeepPartial<PageUiContent["newsDetail"]> | null;
  casesPageUi?: DeepPartial<PageUiContent["casesPage"]> | null;
  caseDetailUi?: DeepPartial<PageUiContent["caseDetail"]> | null;
  aboutPageUi?: DeepPartial<PageUiContent["aboutPage"]> | null;
  contentLabelsUi?: DeepPartial<PageUiContent["contentLabels"]> | null;
};

type DeepPartial<T> = { [K in keyof T]?: T[K] extends object ? DeepPartial<T[K]> | null : T[K] | null };

export { productUiFromSettings } from "@/lib/cms-product-ui-settings";

export type ChromeBrandingContent = {
  logoSrc: string;
  logoWidth: number;
  logoHeight: number;
  productMenuBackgroundImage: string;
};

export function combineCmsSettings(...settings: (Record<string, unknown> | null | undefined)[]): CmsSiteSetting | null {
  const validSettings = settings.filter((item): item is Record<string, unknown> => Boolean(item));
  return validSettings.length > 0 ? Object.assign({}, ...validSettings) as CmsSiteSetting : null;
}


export function navItemsFromSettings(settings: CmsSiteSetting | null): NavItem[] {
  const items = settings?.navigationItems
    ?.filter((item): item is NonNullable<typeof item> => Boolean(item?.label && item.href && item.showInHeader !== false))
    .sort((a, b) => (a.sortOrder ?? 100) - (b.sortOrder ?? 100))
    .map((item) => ({
      label: item.label as string,
      href: item.href as string,
      hasMenu: Boolean(item.hasMenu),
      sortOrder: item.sortOrder ?? 100,
    }));

  return items && items.length > 0 ? items : defaultNavItems;
}

export function languageOptionsFromSettings(settings: CmsSiteSetting | null): SiteLanguageOption[] {
  const items = settings?.languageOptions
    ?.flatMap((item) => {
      if (!item?.locale || !item.label || !item.flag || item.enabled === false) return [];
      const completionPercent = clampPercent(item.completionPercent);
      const isDefaultEnglish = item.locale === "en";
      const isReadyTranslation = item.translationStatus === "ready" && completionPercent >= 95;
      if (!isDefaultEnglish && !isReadyTranslation) return [];

      return [{
        locale: item.locale,
        label: item.label,
        flag: item.flag,
        market: item.market || "",
        direction: item.direction || (item.locale === "ar" ? "rtl" : "ltr"),
        translationStatus: item.translationStatus || "in-progress",
        completionPercent,
        enabled: true,
        sortOrder: item.sortOrder ?? 100,
      }];
    })
    .sort((a, b) => a.sortOrder - b.sortOrder);

  return items && items.length > 0 ? items : defaultLanguageOptions;
}

export function chromeUiFromSettings(settings: CmsSiteSetting | null): ChromeUiContent {
  return {
    ...defaultChromeUiContent,
    ...cleanRecord(settings?.chromeUi),
  };
}

export function chromeBrandingFromSettings(settings: CmsSiteSetting | null): ChromeBrandingContent {
  return {
    logoSrc: mediaUrl(settings?.logoImage, "thumbnail") ?? settings?.logoPath ?? "/images/bob-lift-logo.png",
    logoWidth: positiveNumber(settings?.logoWidth, 188),
    logoHeight: positiveNumber(settings?.logoHeight, 91),
    productMenuBackgroundImage: mediaUrl(settings?.productMenuBackgroundImage, "hero") ?? settings?.productMenuBackgroundImagePath ?? "/images/telescopic-banner.jpg",
  };
}

export function homeSeoFromSettings(settings: CmsSiteSetting | null): HomeSeoContent {
  return {
    ...defaultHomeSeoContent,
    ...cleanRecord(settings?.homeSeo),
  };
}

export function homeSectionVisibilityFromSettings(settings: CmsSiteSetting | null): HomeSectionVisibility {
  return {
    ...defaultHomeSectionVisibility,
    ...cleanBooleanRecord(settings?.homeSectionVisibility),
  };
}

export function homeHeroUiFromSettings(settings: CmsSiteSetting | null): HomeHeroUiContent {
  return {
    ...defaultHomeHeroUiContent,
    ...cleanRecord(settings?.homeHeroUi),
    autoplay: settings?.homeHeroUi?.autoplay ?? defaultHomeHeroUiContent.autoplay,
    showControls: settings?.homeHeroUi?.showControls ?? defaultHomeHeroUiContent.showControls,
    showIndicators: settings?.homeHeroUi?.showIndicators ?? defaultHomeHeroUiContent.showIndicators,
    autoplayIntervalMs: positiveNumber(settings?.homeHeroUi?.autoplayIntervalMs, defaultHomeHeroUiContent.autoplayIntervalMs),
  };
}

export function pageUiFromSettings(settings: CmsSiteSetting | null): PageUiContent {
  const workflowItems = settings?.quotePageUi?.workflowItems
    ?.flatMap((item) => item?.title && item.text ? [{ iconName: item.iconName || "file-text", title: item.title, text: item.text } as QuoteWorkflowItem] : []);

  return {
    seoFallbacks: { ...defaultPageUiContent.seoFallbacks, ...cleanRecord(settings?.seoFallbacksUi) },
    contactPage: {
      ...defaultPageUiContent.contactPage,
      ...cleanRecord(settings?.contactPageUi),
      cardLabels: {
        ...defaultPageUiContent.contactPage.cardLabels,
        ...cleanRecord(settings?.contactPageUi?.cardLabels),
      },
    },
    quotePage: {
      ...defaultPageUiContent.quotePage,
      ...cleanRecord(settings?.quotePageUi),
      workflowItems: workflowItems && workflowItems.length > 0 ? workflowItems : defaultPageUiContent.quotePage.workflowItems,
    },
    quoteForm: {
      ...defaultPageUiContent.quoteForm,
      ...cleanRecord(settings?.quoteFormUi),
      productOptionLimit: nonNegativeInteger(settings?.quoteFormUi?.productOptionLimit, defaultPageUiContent.quoteForm.productOptionLimit),
      fields: { ...defaultPageUiContent.quoteForm.fields, ...cleanRecord(settings?.quoteFormUi?.fields) },
      buttons: { ...defaultPageUiContent.quoteForm.buttons, ...cleanRecord(settings?.quoteFormUi?.buttons) },
      fieldControls: quoteFormFieldControlsFromSettings(settings?.quoteFormUi?.fieldControls),
      buttonChannels: {
        ...defaultPageUiContent.quoteForm.buttonChannels,
        ...cleanBooleanRecord(settings?.quoteFormUi?.buttonChannels),
      },
      messages: { ...defaultPageUiContent.quoteForm.messages, ...cleanRecord(settings?.quoteFormUi?.messages) },
      inquiryLineLabels: { ...defaultPageUiContent.quoteForm.inquiryLineLabels, ...cleanRecord(settings?.quoteFormUi?.inquiryLineLabels) },
    },
    newsPage: { ...defaultPageUiContent.newsPage, ...cleanRecord(settings?.newsPageUi) },
    newsDetail: {
      ...defaultPageUiContent.newsDetail,
      ...cleanRecord(settings?.newsDetailUi),
      relatedArticleCount: nonNegativeInteger(settings?.newsDetailUi?.relatedArticleCount, defaultPageUiContent.newsDetail.relatedArticleCount),
    },
    casesPage: { ...defaultPageUiContent.casesPage, ...cleanRecord(settings?.casesPageUi) },
    caseDetail: { ...defaultPageUiContent.caseDetail, ...cleanRecord(settings?.caseDetailUi) },
    aboutPage: {
      ...defaultPageUiContent.aboutPage,
      ...cleanRecord(settings?.aboutPageUi),
      galleryImageCount: positiveInteger(settings?.aboutPageUi?.galleryImageCount, defaultPageUiContent.aboutPage.galleryImageCount),
    },
    contentLabels: {
      articleCategories: {
        ...defaultPageUiContent.contentLabels.articleCategories,
        ...cleanRecord(settings?.contentLabelsUi?.articleCategories),
      },
      caseFacts: {
        ...defaultPageUiContent.contentLabels.caseFacts,
        ...cleanRecord(settings?.contentLabelsUi?.caseFacts),
      },
    },
  };
}

export function contactFromSettings(settings: CmsSiteSetting | null): ContactSettings {
  const phone = settings?.phone || defaultContactSettings.phone;
  const whatsapp = settings?.whatsapp || defaultContactSettings.whatsapp;
  const defaultInquiryText = settings?.defaultInquiryText || defaultContactSettings.defaultInquiryText;

  return {
    email: settings?.email || defaultContactSettings.email,
    phone,
    phoneHref: phoneToHref(phone),
    whatsapp,
    whatsappHref: whatsappToHref(whatsapp, defaultInquiryText),
    wechatId: settings?.wechatId || defaultContactSettings.wechatId,
    wechatQr: mediaUrl(settings?.wechatQr, "card") ?? defaultContactSettings.wechatQr,
    address: settings?.address || defaultContactSettings.address,
    contactPath: defaultContactSettings.contactPath,
    quotePath: settings?.quotePath || defaultContactSettings.quotePath,
    inquirySubject: settings?.inquirySubject || defaultContactSettings.inquirySubject,
    defaultInquiryText,
  };
}

export function topBarFromSettings(settings: CmsSiteSetting | null): HeaderTopBarContent {
  return {
    highlight: settings?.headerTopBar?.highlight || defaultHeaderTopBarContent.highlight,
    text: settings?.headerTopBar?.text || defaultHeaderTopBarContent.text,
    linkLabel: settings?.headerTopBar?.linkLabel || defaultHeaderTopBarContent.linkLabel,
    linkHref: settings?.headerTopBar?.linkHref || defaultHeaderTopBarContent.linkHref,
  };
}

export function productSectionFromSettings(settings: CmsSiteSetting | null): HomeProductSectionContent {
  return {
    label: settings?.homeProductSection?.label || defaultHomeProductSectionContent.label,
    title: settings?.homeProductSection?.title || defaultHomeProductSectionContent.title,
    maxCategoryProducts: positiveNumber(settings?.homeProductSection?.maxCategoryProducts, defaultHomeProductSectionContent.maxCategoryProducts),
    showCategoryScrollButtons: settings?.homeProductSection?.showCategoryScrollButtons ?? defaultHomeProductSectionContent.showCategoryScrollButtons,
    showInlineProductDetail: settings?.homeProductSection?.showInlineProductDetail ?? defaultHomeProductSectionContent.showInlineProductDetail,
    showProductSpecs: settings?.homeProductSection?.showProductSpecs ?? defaultHomeProductSectionContent.showProductSpecs,
    showProductVisualWatermark: settings?.homeProductSection?.showProductVisualWatermark ?? defaultHomeProductSectionContent.showProductVisualWatermark,
    findModelTitle: settings?.homeProductSection?.findModelTitle || defaultHomeProductSectionContent.findModelTitle,
    detailsShortLabel: settings?.homeProductSection?.detailsShortLabel || defaultHomeProductSectionContent.detailsShortLabel,
    viewDetailsLabel: settings?.homeProductSection?.viewDetailsLabel || defaultHomeProductSectionContent.viewDetailsLabel,
    backToModelsLabel: settings?.homeProductSection?.backToModelsLabel || defaultHomeProductSectionContent.backToModelsLabel,
    previousCategoriesLabel: settings?.homeProductSection?.previousCategoriesLabel || defaultHomeProductSectionContent.previousCategoriesLabel,
    nextCategoriesLabel: settings?.homeProductSection?.nextCategoriesLabel || defaultHomeProductSectionContent.nextCategoriesLabel,
    productNameLabel: settings?.homeProductSection?.productNameLabel || defaultHomeProductSectionContent.productNameLabel,
    modelLabel: settings?.homeProductSection?.modelLabel || defaultHomeProductSectionContent.modelLabel,
    modelSpecLabel: settings?.homeProductSection?.modelSpecLabel || defaultHomeProductSectionContent.modelSpecLabel,
    capacitySpecLabel: settings?.homeProductSection?.capacitySpecLabel || defaultHomeProductSectionContent.capacitySpecLabel,
    typeSpecLabel: settings?.homeProductSection?.typeSpecLabel || defaultHomeProductSectionContent.typeSpecLabel,
    systemSpecLabel: settings?.homeProductSection?.systemSpecLabel || defaultHomeProductSectionContent.systemSpecLabel,
    systemValue: settings?.homeProductSection?.systemValue || defaultHomeProductSectionContent.systemValue,
    visualWatermarkLabel: settings?.homeProductSection?.visualWatermarkLabel || defaultHomeProductSectionContent.visualWatermarkLabel,
  };
}

export function trustBarFromSettings(settings: CmsSiteSetting | null): TrustBarContent {
  const marks = settings?.trustBar?.marks?.flatMap((item) => {
    const logo = mediaUrl(item?.logoImage, "thumbnail") ?? item?.logoPath;
    return item?.name && logo ? [{ name: item.name, logo }] : [];
  });

  return {
    label: settings?.trustBar?.label || defaultTrustBarContent.label,
    title: settings?.trustBar?.title || defaultTrustBarContent.title,
    description: settings?.trustBar?.description || defaultTrustBarContent.description,
    showIntroCard: settings?.trustBar?.showIntroCard ?? defaultTrustBarContent.showIntroCard,
    showMarkNames: settings?.trustBar?.showMarkNames ?? defaultTrustBarContent.showMarkNames,
    marks: marks && marks.length > 0 ? marks : defaultTrustBarContent.marks,
  };
}

export function aboutSectionFromSettings(settings: CmsSiteSetting | null): AboutSectionContent {
  const gallery = settings?.aboutSection?.gallery?.flatMap((item) => {
    const src = mediaUrl(item?.image, "card") ?? item?.imagePath;
    const videoUrl = item?.videoUrl?.trim();
    return src && item?.alt ? [{ src, alt: item.alt, video: Boolean(item.video), videoUrl: videoUrl || undefined }] : [];
  });
  const stats = settings?.aboutSection?.stats?.flatMap((item) =>
    item?.value && item.label ? [{ value: item.value, label: item.label, iconName: item.iconName || undefined }] : [],
  );
  const timeline = settings?.aboutSection?.timeline?.map((item) => item?.text).filter((item): item is string => Boolean(item));

  return {
    label: settings?.aboutSection?.label || defaultAboutSectionContent.label,
    title: settings?.aboutSection?.title || defaultAboutSectionContent.title,
    text: settings?.aboutSection?.text || defaultAboutSectionContent.text,
    ctaLabel: settings?.aboutSection?.ctaLabel || defaultAboutSectionContent.ctaLabel,
    ctaHref: settings?.aboutSection?.ctaHref || defaultAboutSectionContent.ctaHref,
    showGallery: settings?.aboutSection?.showGallery ?? defaultAboutSectionContent.showGallery,
    showStats: settings?.aboutSection?.showStats ?? defaultAboutSectionContent.showStats,
    showTimeline: settings?.aboutSection?.showTimeline ?? defaultAboutSectionContent.showTimeline,
    showCta: settings?.aboutSection?.showCta ?? defaultAboutSectionContent.showCta,
    galleryThumbnailLabelTemplate: settings?.aboutSection?.galleryThumbnailLabelTemplate || defaultAboutSectionContent.galleryThumbnailLabelTemplate,
    videoLabel: settings?.aboutSection?.videoLabel || defaultAboutSectionContent.videoLabel,
    gallery: gallery && gallery.length > 0 ? gallery : defaultAboutSectionContent.gallery,
    stats: stats && stats.length > 0 ? stats : defaultAboutSectionContent.stats,
    timeline: timeline && timeline.length > 0 ? timeline : defaultAboutSectionContent.timeline,
  };
}

export function newsSectionFromSettings(settings: CmsSiteSetting | null): NewsSectionContent {
  return {
    label: settings?.newsSection?.label || defaultNewsSectionContent.label,
    title: settings?.newsSection?.title || defaultNewsSectionContent.title,
    ctaLabel: settings?.newsSection?.ctaLabel || defaultNewsSectionContent.ctaLabel,
    ctaHref: settings?.newsSection?.ctaHref || defaultNewsSectionContent.ctaHref,
    showCta: settings?.newsSection?.showCta ?? defaultNewsSectionContent.showCta,
    showExcerpt: settings?.newsSection?.showExcerpt ?? defaultNewsSectionContent.showExcerpt,
    showDate: settings?.newsSection?.showDate ?? defaultNewsSectionContent.showDate,
    showCategory: settings?.newsSection?.showCategory ?? defaultNewsSectionContent.showCategory,
    maxArticles: positiveNumber(settings?.newsSection?.maxArticles, defaultNewsSectionContent.maxArticles),
    readMoreLabel: settings?.newsSection?.readMoreLabel || settings?.newsPageUi?.readMoreLabel || defaultNewsSectionContent.readMoreLabel,
    pinnedLabel: settings?.newsSection?.pinnedLabel || settings?.newsPageUi?.featuredLabel || defaultNewsSectionContent.pinnedLabel,
    backgroundWord: settings?.newsSection?.backgroundWord || defaultNewsSectionContent.backgroundWord,
  };
}

export function finalCtaFromSettings(settings: CmsSiteSetting | null): FinalCtaContent {
  return {
    label: settings?.finalCta?.label || defaultFinalCtaContent.label,
    title: settings?.finalCta?.title || defaultFinalCtaContent.title,
    buttonLabel: settings?.finalCta?.buttonLabel || defaultFinalCtaContent.buttonLabel,
    buttonHref: settings?.finalCta?.buttonHref || defaultFinalCtaContent.buttonHref,
    image: mediaUrl(settings?.finalCta?.image, "hero") ?? settings?.finalCta?.imagePath ?? defaultFinalCtaContent.image,
  };
}

export function footerFromSettings(settings: CmsSiteSetting | null): FooterContent {
  const socialLinks = settings?.socialLinks?.flatMap((link) => (link?.label && link.href ? [{ label: link.label, href: link.href }] : []));
  const groups = settings?.footerGroups?.flatMap((group) => {
    const links = group?.links?.flatMap((link) => (link?.label && link.href ? [{ label: link.label, href: link.href }] : [])) ?? [];
    return group?.title && links.length > 0 ? [{ title: group.title, links }] : [];
  });
  const legalLinks = settings?.legalLinks?.flatMap((link) => (link?.label && link.href ? [{ label: link.label, href: link.href }] : []));

  return {
    intro: settings?.footerIntro || defaultFooterContent.intro,
    socialLinks: socialLinks && socialLinks.length > 0 ? socialLinks : defaultFooterContent.socialLinks,
    groups: groups && groups.length > 0 ? groups : defaultFooterContent.groups,
    contactTitle: settings?.footerContactTitle || defaultFooterContent.contactTitle,
    copyright: settings?.copyright || defaultFooterContent.copyright,
    legalLinks: legalLinks && legalLinks.length > 0 ? legalLinks : defaultFooterContent.legalLinks,
  };
}


function cleanRecord<T extends Record<string, unknown>>(record: T | null | undefined): Partial<Record<keyof T, string>> {
  if (!record) return {};
  return Object.fromEntries(Object.entries(record).filter(([, value]) => typeof value === "string" && Boolean(value))) as Partial<Record<keyof T, string>>;
}

function cleanBooleanRecord<T extends Record<string, unknown>>(record: T | null | undefined): Partial<Record<keyof T, boolean>> {
  if (!record) return {};
  return Object.fromEntries(Object.entries(record).filter(([, value]) => typeof value === "boolean")) as Partial<Record<keyof T, boolean>>;
}

function quoteFormFieldControlsFromSettings(record: unknown): QuoteFormUiContent["fieldControls"] {
  const defaults = defaultPageUiContent.quoteForm.fieldControls;
  if (!record || typeof record !== "object") return defaults;

  return Object.fromEntries(
    Object.entries(defaults).map(([key, value]) => {
      const raw = (record as Record<string, unknown>)[key];
      return [
        key,
        {
          ...value,
          ...(raw && typeof raw === "object" ? cleanBooleanRecord(raw as Record<string, unknown>) : {}),
        },
      ];
    }),
  ) as QuoteFormUiContent["fieldControls"];
}

function clampPercent(value?: number | null) {
  if (typeof value !== "number" || !Number.isFinite(value)) return 0;
  return Math.min(100, Math.max(0, value));
}

function positiveNumber(value: unknown, fallback: number) {
  return typeof value === "number" && Number.isFinite(value) && value > 0 ? value : fallback;
}

function nonNegativeInteger(value: unknown, fallback: number) {
  return typeof value === "number" && Number.isInteger(value) && value >= 0 ? value : fallback;
}

function positiveInteger(value: unknown, fallback: number) {
  return typeof value === "number" && Number.isInteger(value) && value > 0 ? value : fallback;
}
