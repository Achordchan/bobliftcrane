import { mediaUrl } from "@/lib/cms-media";
import {
  defaultApplicationOptions,
  defaultAvailabilityOptions,
  defaultBoomTypeOptions,
  defaultCapacityRanges,
  defaultProductUiContent,
  type ProductDetailUiContent,
  type ProductListingUiContent,
  type ProductUiContent,
} from "@/lib/product-ui";
import type { Media } from "@/payload-types";
import type {
  BoomType,
  LiftingCapacityRange,
  ProductApplication,
  ProductApplicationOption,
  ProductCapacityRangeOption,
  ProductFilterDictionaryOption,
  ProductStatus,
  ProductSortOption,
  ProductViewMode,
} from "@/types/product-listing";

export type ProductUiSettingsSource = {
  productListingUi?: ProductListingUiSettings | null;
  productDetailUi?: ProductDetailUiSettings | null;
};

type DictionaryOptionSettings<T extends string> = {
  key?: T | null;
  label?: string | null;
  enabled?: boolean | null;
  sortOrder?: number | null;
};

type CapacityRangeSettings = DictionaryOptionSettings<LiftingCapacityRange> & {
  min?: number | null;
  max?: number | null;
};

type ApplicationSettings = DictionaryOptionSettings<ProductApplication> & {
  title?: string | null;
  text?: string | null;
  image?: number | Media | null;
  imagePath?: string | null;
  iconName?: string | null;
};

type ProductFilterDictionariesSettings = Partial<{
  capacityRanges: (CapacityRangeSettings | null)[];
  boomTypes: (DictionaryOptionSettings<BoomType> | null)[];
  applications: (ApplicationSettings | null)[];
  availability: (DictionaryOptionSettings<ProductStatus> | null)[];
}>;

export type ProductListingUiSettings = Partial<{
  metaTitle: string;
  metaDescription: string;
  canonicalPath: string;
  bannerLabel: string;
  bannerTitle: string;
  bannerImage: number | Media | null;
  bannerImageAlt: string;
  bannerWatermarkLabel: string;
  bannerParagraphs: ({ text?: string | null } | null)[];
  resultSummaryTemplate: string;
  filterButtonLabel: string;
  filtersTitle: string;
  clearAllLabel: string;
  closeFiltersLabel: string;
  filterGroups: Partial<ProductListingUiContent["filterGroups"]>;
  sortLabel: string;
  sortOptions: Partial<ProductListingUiContent["sortOptions"]>;
  sortVisibility: Partial<ProductListingUiContent["sortVisibility"]>;
  viewModeLabel: string;
  gridViewLabel: string;
  listViewLabel: string;
  favoriteAddLabel: string;
  favoriteRemoveLabel: string;
  quickViewLabelTemplate: string;
  quickViewCloseLabel: string;
  requestQuoteLabel: string;
  viewDetailsLabel: string;
  emptyTitle: string;
  emptyText: string;
  clearFiltersButtonLabel: string;
  previousPageLabel: string;
  nextPageLabel: string;
  paginationLabel: string;
  rowsPerPage: number;
  maxCardSpecs: number;
  maxListItemSpecs: number;
  maxQuickViewSpecs: number;
  defaultSortOption: ProductSortOption;
  defaultViewMode: ProductViewMode;
  showFilters: boolean;
  showSortDropdown: boolean;
  showViewModeToggle: boolean;
  showFavoriteButton: boolean;
  showQuickViewButton: boolean;
  showPagination: boolean;
  showBanner: boolean;
  showResultSummary: boolean;
  showFilterCounts: boolean;
  filterVisibility: Partial<Record<"categories" | "capacities" | "boomTypes" | "applications" | "availability", boolean>>;
  cardVisibility: Partial<ProductListingUiContent["cardVisibility"]>;
  listItemVisibility: Partial<ProductListingUiContent["listItemVisibility"]>;
  quickViewVisibility: Partial<ProductListingUiContent["quickViewVisibility"]>;
  statusLabels: Partial<Record<"inStock" | "bestSeller" | "customizable" | "preOrder", string>>;
  capacityRanges: ProductFilterDictionariesSettings["capacityRanges"];
  boomTypes: ProductFilterDictionariesSettings["boomTypes"];
  applications: ProductFilterDictionariesSettings["applications"];
  availability: ProductFilterDictionariesSettings["availability"];
  specLabels: Partial<Record<"maxLift" | "boomLength" | "rotation" | "boomType" | "specificationStatus" | "parameterReview", string>>;
}>;

export type ProductDetailUiSettings = Partial<{
  requestQuoteLabel: string;
  downloadCatalogLabel: string;
  viewTechnicalSpecsLabel: string;
  anchorLabels: Partial<ProductDetailUiContent["anchorLabels"]>;
  ariaLabels: Partial<ProductDetailUiContent["ariaLabels"]>;
  hotspots: ({ label?: string | null } | null)[];
  overviewTitle: string;
  overviewTextTemplate: string;
  defaultFeatures: ({ title?: string | null; text?: string | null } | null)[];
  technicalTitle: string;
  catalogBrandLabel: string;
  catalogCoverTitle: string;
  catalogTitleTemplate: string;
  catalogCoverAltTemplate: string;
  downloadCenterLabel: string;
  downloadCatalogTitle: string;
  catalogDescriptionTemplate: string;
  requestPdfLabel: string;
  galleryTitle: string;
  galleryEmptyLabel: string;
  galleryEmptyTitle: string;
  galleryEmptyText: string;
  galleryMainImageLabel: string;
  galleryDetailImageLabel: string;
  galleryDrawingLabel: string;
  defaultGalleryLabels: ({ label?: string | null } | null)[];
  applicationsTitle: string;
  reviewStatusLabels: Partial<ProductDetailUiContent["reviewStatusLabels"]>;
  customizationLabels: Partial<ProductDetailUiContent["customizationLabels"]>;
  specLabels: Partial<ProductDetailUiContent["specLabels"]>;
  faqTitle: string;
  defaultFaqs: ({ question?: string | null; answer?: string | null } | null)[];
  quoteTitle: string;
  quoteText: string;
  contactEngineerLabel: string;
  maxHeroSpecs: number;
  maxApplicationCards: number;
  maxHotspots: number;
  heroVisibility: Partial<ProductDetailUiContent["heroVisibility"]>;
  catalogVisibility: Partial<ProductDetailUiContent["catalogVisibility"]>;
  quoteVisibility: Partial<ProductDetailUiContent["quoteVisibility"]>;
  sectionVisibility: Partial<ProductDetailUiContent["sectionVisibility"]>;
}>;

export function productUiFromSettings(settings: ProductUiSettingsSource | null): ProductUiContent {
  const listingSettings = settings?.productListingUi;
  const detailSettings = settings?.productDetailUi;
  const listingDefaults = defaultProductUiContent.listing;
  const detailDefaults = defaultProductUiContent.detail;
  const bannerParagraphs = listingSettings?.bannerParagraphs
    ?.map((item) => item?.text)
    .filter((item): item is string => Boolean(item));
  const hotspots = detailSettings?.hotspots
    ?.map((item) => item?.label)
    .filter((item): item is string => Boolean(item));
  const defaultFeatures = detailSettings?.defaultFeatures
    ?.flatMap((item) => (item?.title && item.text ? [{ title: item.title, text: item.text }] : []));
  const defaultGalleryLabels = detailSettings?.defaultGalleryLabels
    ?.map((item) => item?.label)
    .filter((item): item is string => Boolean(item));
  const defaultFaqs = detailSettings?.defaultFaqs
    ?.flatMap((item) => (item?.question && item.answer ? [{ question: item.question, answer: item.answer }] : []));
  const capacityRanges = capacityRangesFromSettings(listingSettings?.capacityRanges, defaultCapacityRanges);
  const boomTypeOptions = dictionaryOptionsFromSettings(listingSettings?.boomTypes, defaultBoomTypeOptions);
  const applicationOptions = applicationOptionsFromSettings(listingSettings?.applications, defaultApplicationOptions);
  const availabilityOptions = dictionaryOptionsFromSettings(listingSettings?.availability, defaultAvailabilityOptions);
  const availabilityLabels = labelsFromDictionary(availabilityOptions);
  const sortVisibility = {
    ...listingDefaults.sortVisibility,
    ...cleanBooleanRecord(listingSettings?.sortVisibility),
  };

  return {
    listing: {
      ...listingDefaults,
      defaultBanner: {
        ...listingDefaults.defaultBanner,
        label: listingSettings?.bannerLabel || listingDefaults.defaultBanner.label,
        title: listingSettings?.bannerTitle || listingDefaults.defaultBanner.title,
        image: mediaUrl(listingSettings?.bannerImage, "hero") ?? listingDefaults.defaultBanner.image,
        imageAlt: listingSettings?.bannerImageAlt || listingDefaults.defaultBanner.imageAlt,
        watermarkLabel: listingSettings?.bannerWatermarkLabel || listingDefaults.defaultBanner.watermarkLabel,
        paragraphs: bannerParagraphs && bannerParagraphs.length > 0
          ? bannerParagraphs.map((text) => ({ segments: [{ text }] }))
          : listingDefaults.defaultBanner.paragraphs,
      },
      resultSummaryTemplate: listingSettings?.resultSummaryTemplate || listingDefaults.resultSummaryTemplate,
      filterButtonLabel: listingSettings?.filterButtonLabel || listingDefaults.filterButtonLabel,
      filtersTitle: listingSettings?.filtersTitle || listingDefaults.filtersTitle,
      clearAllLabel: listingSettings?.clearAllLabel || listingDefaults.clearAllLabel,
      closeFiltersLabel: listingSettings?.closeFiltersLabel || listingDefaults.closeFiltersLabel,
      filterGroups: { ...listingDefaults.filterGroups, ...cleanRecord(listingSettings?.filterGroups) },
      sortLabel: listingSettings?.sortLabel || listingDefaults.sortLabel,
      sortOptions: { ...listingDefaults.sortOptions, ...cleanRecord(listingSettings?.sortOptions) },
      sortVisibility,
      viewModeLabel: listingSettings?.viewModeLabel || listingDefaults.viewModeLabel,
      gridViewLabel: listingSettings?.gridViewLabel || listingDefaults.gridViewLabel,
      listViewLabel: listingSettings?.listViewLabel || listingDefaults.listViewLabel,
      favoriteAddLabel: listingSettings?.favoriteAddLabel || listingDefaults.favoriteAddLabel,
      favoriteRemoveLabel: listingSettings?.favoriteRemoveLabel || listingDefaults.favoriteRemoveLabel,
      quickViewLabelTemplate: listingSettings?.quickViewLabelTemplate || listingDefaults.quickViewLabelTemplate,
      quickViewCloseLabel: listingSettings?.quickViewCloseLabel || listingDefaults.quickViewCloseLabel,
      requestQuoteLabel: listingSettings?.requestQuoteLabel || listingDefaults.requestQuoteLabel,
      viewDetailsLabel: listingSettings?.viewDetailsLabel || listingDefaults.viewDetailsLabel,
      emptyTitle: listingSettings?.emptyTitle || listingDefaults.emptyTitle,
      emptyText: listingSettings?.emptyText || listingDefaults.emptyText,
      clearFiltersButtonLabel: listingSettings?.clearFiltersButtonLabel || listingDefaults.clearFiltersButtonLabel,
      previousPageLabel: listingSettings?.previousPageLabel || listingDefaults.previousPageLabel,
      nextPageLabel: listingSettings?.nextPageLabel || listingDefaults.nextPageLabel,
      paginationLabel: listingSettings?.paginationLabel || listingDefaults.paginationLabel,
      rowsPerPage: validPositiveInteger(listingSettings?.rowsPerPage, listingDefaults.rowsPerPage),
      maxCardSpecs: validBoundedInteger(listingSettings?.maxCardSpecs, listingDefaults.maxCardSpecs, 1, 6),
      maxListItemSpecs: validBoundedInteger(listingSettings?.maxListItemSpecs, listingDefaults.maxListItemSpecs, 1, 6),
      maxQuickViewSpecs: validBoundedInteger(listingSettings?.maxQuickViewSpecs, listingDefaults.maxQuickViewSpecs, 1, 6),
      defaultSortOption: validSortOption(listingSettings?.defaultSortOption, firstVisibleSortOption(sortVisibility, listingDefaults.defaultSortOption)),
      defaultViewMode: validViewMode(listingSettings?.defaultViewMode, listingDefaults.defaultViewMode),
      showFilters: listingSettings?.showFilters ?? listingDefaults.showFilters,
      showSortDropdown: listingSettings?.showSortDropdown ?? listingDefaults.showSortDropdown,
      showViewModeToggle: listingSettings?.showViewModeToggle ?? listingDefaults.showViewModeToggle,
      showFavoriteButton: listingSettings?.showFavoriteButton ?? listingDefaults.showFavoriteButton,
      showQuickViewButton: listingSettings?.showQuickViewButton ?? listingDefaults.showQuickViewButton,
      showPagination: listingSettings?.showPagination ?? listingDefaults.showPagination,
      showBanner: listingSettings?.showBanner ?? listingDefaults.showBanner,
      showResultSummary: listingSettings?.showResultSummary ?? listingDefaults.showResultSummary,
      showFilterCounts: listingSettings?.showFilterCounts ?? listingDefaults.showFilterCounts,
      filterVisibility: {
        ...listingDefaults.filterVisibility,
        ...cleanBooleanRecord(listingSettings?.filterVisibility),
      },
      cardVisibility: {
        ...listingDefaults.cardVisibility,
        ...cleanBooleanRecord(listingSettings?.cardVisibility),
      },
      listItemVisibility: {
        ...listingDefaults.listItemVisibility,
        ...cleanBooleanRecord(listingSettings?.listItemVisibility),
      },
      quickViewVisibility: {
        ...listingDefaults.quickViewVisibility,
        ...cleanBooleanRecord(listingSettings?.quickViewVisibility),
      },
      statusLabels: {
        ...listingDefaults.statusLabels,
        ...availabilityLabels,
        "in-stock": listingSettings?.statusLabels?.inStock || availabilityLabels["in-stock"] || listingDefaults.statusLabels["in-stock"],
        "best-seller": listingSettings?.statusLabels?.bestSeller || availabilityLabels["best-seller"] || listingDefaults.statusLabels["best-seller"],
        customizable: listingSettings?.statusLabels?.customizable || availabilityLabels.customizable || listingDefaults.statusLabels.customizable,
        "pre-order": listingSettings?.statusLabels?.preOrder || availabilityLabels["pre-order"] || listingDefaults.statusLabels["pre-order"],
      },
      boomTypeLabels: { ...listingDefaults.boomTypeLabels, ...labelsFromDictionary(boomTypeOptions) },
      applicationLabels: { ...listingDefaults.applicationLabels, ...labelsFromDictionary(applicationOptions) },
      capacityLabels: { ...listingDefaults.capacityLabels, ...labelsFromDictionary(capacityRanges) },
      capacityRanges,
      boomTypeOptions,
      applicationOptions,
      availabilityOptions,
      specLabels: {
        ...listingDefaults.specLabels,
        "Max Lift": listingSettings?.specLabels?.maxLift || listingDefaults.specLabels["Max Lift"],
        "Boom Length": listingSettings?.specLabels?.boomLength || listingDefaults.specLabels["Boom Length"],
        Rotation: listingSettings?.specLabels?.rotation || listingDefaults.specLabels.Rotation,
        "Boom Type": listingSettings?.specLabels?.boomType || listingDefaults.specLabels["Boom Type"],
        "Specification Status": listingSettings?.specLabels?.specificationStatus || listingDefaults.specLabels["Specification Status"],
        "Parameter Review": listingSettings?.specLabels?.parameterReview || listingDefaults.specLabels["Parameter Review"],
      },
    },
    detail: {
      ...detailDefaults,
      requestQuoteLabel: detailSettings?.requestQuoteLabel || detailDefaults.requestQuoteLabel,
      downloadCatalogLabel: detailSettings?.downloadCatalogLabel || detailDefaults.downloadCatalogLabel,
      viewTechnicalSpecsLabel: detailSettings?.viewTechnicalSpecsLabel || detailDefaults.viewTechnicalSpecsLabel,
      anchorLabels: { ...detailDefaults.anchorLabels, ...cleanRecord(detailSettings?.anchorLabels) },
      ariaLabels: { ...detailDefaults.ariaLabels, ...cleanRecord(detailSettings?.ariaLabels) },
      hotspots: hotspots && hotspots.length > 0 ? hotspots : detailDefaults.hotspots,
      overviewTitle: detailSettings?.overviewTitle || detailDefaults.overviewTitle,
      overviewTextTemplate: detailSettings?.overviewTextTemplate || detailDefaults.overviewTextTemplate,
      defaultFeatures: defaultFeatures && defaultFeatures.length > 0 ? defaultFeatures : detailDefaults.defaultFeatures,
      technicalTitle: detailSettings?.technicalTitle || detailDefaults.technicalTitle,
      catalogBrandLabel: detailSettings?.catalogBrandLabel || detailDefaults.catalogBrandLabel,
      catalogCoverTitle: detailSettings?.catalogCoverTitle || detailDefaults.catalogCoverTitle,
      catalogTitleTemplate: detailSettings?.catalogTitleTemplate || detailDefaults.catalogTitleTemplate,
      catalogCoverAltTemplate: detailSettings?.catalogCoverAltTemplate || detailDefaults.catalogCoverAltTemplate,
      downloadCenterLabel: detailSettings?.downloadCenterLabel || detailDefaults.downloadCenterLabel,
      downloadCatalogTitle: detailSettings?.downloadCatalogTitle || detailDefaults.downloadCatalogTitle,
      catalogDescriptionTemplate: detailSettings?.catalogDescriptionTemplate || detailDefaults.catalogDescriptionTemplate,
      requestPdfLabel: detailSettings?.requestPdfLabel || detailDefaults.requestPdfLabel,
      galleryTitle: detailSettings?.galleryTitle || detailDefaults.galleryTitle,
      galleryEmptyLabel: detailSettings?.galleryEmptyLabel || detailDefaults.galleryEmptyLabel,
      galleryEmptyTitle: detailSettings?.galleryEmptyTitle || detailDefaults.galleryEmptyTitle,
      galleryEmptyText: detailSettings?.galleryEmptyText || detailDefaults.galleryEmptyText,
      galleryMainImageLabel: detailSettings?.galleryMainImageLabel || detailDefaults.galleryMainImageLabel,
      galleryDetailImageLabel: detailSettings?.galleryDetailImageLabel || detailDefaults.galleryDetailImageLabel,
      galleryDrawingLabel: detailSettings?.galleryDrawingLabel || detailDefaults.galleryDrawingLabel,
      defaultGalleryLabels: defaultGalleryLabels && defaultGalleryLabels.length > 0 ? defaultGalleryLabels : detailDefaults.defaultGalleryLabels,
      applicationsTitle: detailSettings?.applicationsTitle || detailDefaults.applicationsTitle,
      applicationCards: applicationOptions.filter((option) => option.enabled),
      reviewStatusLabels: { ...detailDefaults.reviewStatusLabels, ...cleanRecord(detailSettings?.reviewStatusLabels) },
      customizationLabels: { ...detailDefaults.customizationLabels, ...cleanRecord(detailSettings?.customizationLabels) },
      specLabels: { ...detailDefaults.specLabels, ...cleanRecord(detailSettings?.specLabels) },
      faqTitle: detailSettings?.faqTitle || detailDefaults.faqTitle,
      defaultFaqs: defaultFaqs && defaultFaqs.length > 0 ? defaultFaqs : detailDefaults.defaultFaqs,
      quoteTitle: detailSettings?.quoteTitle || detailDefaults.quoteTitle,
      quoteText: detailSettings?.quoteText || detailDefaults.quoteText,
      contactEngineerLabel: detailSettings?.contactEngineerLabel || detailDefaults.contactEngineerLabel,
      maxHeroSpecs: validBoundedInteger(detailSettings?.maxHeroSpecs, detailDefaults.maxHeroSpecs, 1, 8),
      maxApplicationCards: validBoundedInteger(detailSettings?.maxApplicationCards, detailDefaults.maxApplicationCards, 1, 8),
      maxHotspots: validBoundedInteger(detailSettings?.maxHotspots, detailDefaults.maxHotspots, 1, 6),
      heroVisibility: {
        ...detailDefaults.heroVisibility,
        ...cleanBooleanRecord(detailSettings?.heroVisibility),
      },
      catalogVisibility: {
        ...detailDefaults.catalogVisibility,
        ...cleanBooleanRecord(detailSettings?.catalogVisibility),
      },
      quoteVisibility: {
        ...detailDefaults.quoteVisibility,
        ...cleanBooleanRecord(detailSettings?.quoteVisibility),
      },
      sectionVisibility: {
        ...detailDefaults.sectionVisibility,
        ...cleanBooleanRecord(detailSettings?.sectionVisibility),
      },
    },
  };
}

function capacityRangesFromSettings(
  settingsItems: (CapacityRangeSettings | null)[] | null | undefined,
  defaults: ProductCapacityRangeOption[],
): ProductCapacityRangeOption[] {
  const fallbackByValue = new Map(defaults.map((item) => [item.value, item]));
  const items = settingsItems?.flatMap((item) => {
    if (!item?.key) return [];
    const fallback = fallbackByValue.get(item.key);
    const min = typeof item.min === "number" ? item.min : fallback?.min;
    if (typeof min !== "number") return [];

    return [{
      value: item.key,
      label: item.label || fallback?.label || item.key,
      min,
      max: typeof item.max === "number" ? item.max : (fallback?.max ?? null),
      enabled: item.enabled !== false,
      sortOrder: item.sortOrder ?? fallback?.sortOrder ?? 100,
    }];
  });

  return sortDictionary(items && items.length > 0 ? items : defaults);
}

function dictionaryOptionsFromSettings<T extends string>(
  settingsItems: (DictionaryOptionSettings<T> | null)[] | null | undefined,
  defaults: ProductFilterDictionaryOption<T>[],
): ProductFilterDictionaryOption<T>[] {
  const fallbackByValue = new Map(defaults.map((item) => [item.value, item]));
  const items = settingsItems?.flatMap((item) => {
    if (!item?.key) return [];
    const fallback = fallbackByValue.get(item.key);

    return [{
      value: item.key,
      label: item.label || fallback?.label || item.key,
      enabled: item.enabled !== false,
      sortOrder: item.sortOrder ?? fallback?.sortOrder ?? 100,
    }];
  });

  return sortDictionary(items && items.length > 0 ? items : defaults);
}

function applicationOptionsFromSettings(
  settingsItems: (ApplicationSettings | null)[] | null | undefined,
  defaults: ProductApplicationOption[],
): ProductApplicationOption[] {
  const fallbackByValue = new Map(defaults.map((item) => [item.value, item]));
  const items = settingsItems?.flatMap((item) => {
    if (!item?.key) return [];
    const fallback = fallbackByValue.get(item.key);

    return [{
      value: item.key,
      label: item.label || fallback?.label || item.key,
      title: item.title || item.label || fallback?.title || item.key,
      text: item.text || fallback?.text || "",
      image: mediaUrl(item.image, "card") ?? item.imagePath ?? fallback?.image ?? "/images/construction-scene.jpg",
      iconName: item.iconName || fallback?.iconName || "construction",
      enabled: item.enabled !== false,
      sortOrder: item.sortOrder ?? fallback?.sortOrder ?? 100,
    }];
  });

  return sortDictionary(items && items.length > 0 ? items : defaults);
}

function labelsFromDictionary<T extends string>(items: ProductFilterDictionaryOption<T>[]): Record<T, string> {
  return Object.fromEntries(items.map((item) => [item.value, item.label])) as Record<T, string>;
}

function sortDictionary<T extends { sortOrder: number; value: string }>(items: T[]): T[] {
  return [...items].sort((a, b) => a.sortOrder - b.sortOrder || a.value.localeCompare(b.value));
}

function cleanRecord(record: Record<string, unknown> | null | undefined): Record<string, string> {
  if (!record) return {};
  return Object.fromEntries(Object.entries(record).filter(([, value]) => typeof value === "string" && Boolean(value))) as Record<string, string>;
}

function cleanBooleanRecord(record: Record<string, unknown> | null | undefined): Record<string, boolean> {
  if (!record) return {};
  return Object.fromEntries(Object.entries(record).filter(([, value]) => typeof value === "boolean")) as Record<string, boolean>;
}

function validPositiveInteger(value: number | null | undefined, fallback: number) {
  return typeof value === "number" && Number.isInteger(value) && value > 0 ? value : fallback;
}

function validBoundedInteger(value: number | null | undefined, fallback: number, min: number, max: number) {
  if (typeof value !== "number" || !Number.isInteger(value)) return fallback;
  return Math.min(max, Math.max(min, value));
}

function validSortOption(value: ProductSortOption | null | undefined, fallback: ProductSortOption) {
  return value && ["recommended", "capacityAsc", "capacityDesc", "newest", "popular"].includes(value) ? value : fallback;
}

function firstVisibleSortOption(visibility: Partial<Record<ProductSortOption, boolean>>, fallback: ProductSortOption) {
  return (["recommended", "capacityAsc", "capacityDesc", "newest", "popular"] as ProductSortOption[])
    .find((option) => visibility[option] !== false) ?? fallback;
}

function validViewMode(value: ProductViewMode | null | undefined, fallback: ProductViewMode) {
  return value === "grid" || value === "list" ? value : fallback;
}
