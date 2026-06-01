import type {
  BoomType,
  LiftingCapacityRange,
  ProductApplication,
  ProductApplicationOption,
  ProductCapacityRangeOption,
  ProductFilterDictionaryOption,
  ProductListingBannerContent,
  ProductSortOption,
  ProductStatus,
} from "@/types/product-listing";

export type ProductListingUiContent = {
  defaultBanner: ProductListingBannerContent;
  resultSummaryTemplate: string;
  filterButtonLabel: string;
  filtersTitle: string;
  clearAllLabel: string;
  closeFiltersLabel: string;
  filterGroups: {
    categories: string;
    capacities: string;
    boomTypes: string;
    applications: string;
    availability: string;
  };
  sortLabel: string;
  sortOptions: Record<ProductSortOption, string>;
  sortVisibility: Record<ProductSortOption, boolean>;
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
  defaultViewMode: "grid" | "list";
  showFilters: boolean;
  showSortDropdown: boolean;
  showViewModeToggle: boolean;
  showFavoriteButton: boolean;
  showQuickViewButton: boolean;
  showPagination: boolean;
  showBanner: boolean;
  showResultSummary: boolean;
  showFilterCounts: boolean;
  filterVisibility: {
    categories: boolean;
    capacities: boolean;
    boomTypes: boolean;
    applications: boolean;
    availability: boolean;
  };
  cardVisibility: {
    statusBadge: boolean;
    categoryLabel: boolean;
    capacityLabel: boolean;
    specGrid: boolean;
    ctaButtons: boolean;
  };
  listItemVisibility: {
    statusBadge: boolean;
    categoryLabel: boolean;
    capacityLabel: boolean;
    boomTypeLabel: boolean;
    description: boolean;
    specGrid: boolean;
    ctaButtons: boolean;
  };
  quickViewVisibility: {
    statusBadge: boolean;
    categoryLabel: boolean;
    description: boolean;
    specGrid: boolean;
    ctaButtons: boolean;
    favoriteButton: boolean;
  };
  statusLabels: Record<ProductStatus, string>;
  boomTypeLabels: Record<BoomType, string>;
  applicationLabels: Record<ProductApplication, string>;
  capacityLabels: Record<LiftingCapacityRange, string>;
  capacityRanges: ProductCapacityRangeOption[];
  boomTypeOptions: ProductFilterDictionaryOption<BoomType>[];
  applicationOptions: ProductApplicationOption[];
  availabilityOptions: ProductFilterDictionaryOption<ProductStatus>[];
  specLabels: Record<string, string>;
};

export type ProductDetailUiContent = {
  requestQuoteLabel: string;
  downloadCatalogLabel: string;
  viewTechnicalSpecsLabel: string;
  anchorLabels: {
    overview: string;
    features: string;
    technical: string;
    details: string;
    catalog: string;
    applications: string;
    faq: string;
    quote: string;
  };
  ariaLabels: {
    anchorNav: string;
    sideNav: string;
  };
  hotspots: string[];
  overviewTitle: string;
  overviewTextTemplate: string;
  defaultFeatures: { title: string; text: string }[];
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
  defaultGalleryLabels: string[];
  applicationsTitle: string;
  applicationCards: ProductApplicationOption[];
  reviewStatusLabels: {
    needsReview: string;
    inReview: string;
    verifiedFromSource: string;
    changesRequired: string;
  };
  customizationLabels: {
    available: string;
    availableOnRequest: string;
  };
  specLabels: Record<string, string>;
  faqTitle: string;
  defaultFaqs: { question: string; answer: string }[];
  quoteTitle: string;
  quoteText: string;
  contactEngineerLabel: string;
  maxHeroSpecs: number;
  maxApplicationCards: number;
  maxHotspots: number;
  heroVisibility: {
    categoryLabel: boolean;
    description: boolean;
    heroSpecs: boolean;
    hotspots: boolean;
    requestQuoteButton: boolean;
    catalogButton: boolean;
    technicalButton: boolean;
  };
  catalogVisibility: {
    cover: boolean;
    downloadButton: boolean;
    requestPdfButton: boolean;
    extraDocumentLinks: boolean;
  };
  quoteVisibility: {
    requestQuoteButton: boolean;
    contactEngineerButton: boolean;
  };
  sectionVisibility: {
    anchorNav: boolean;
    sideNav: boolean;
    overview: boolean;
    features: boolean;
    technical: boolean;
    gallery: boolean;
    catalog: boolean;
    applications: boolean;
    faq: boolean;
    quote: boolean;
  };
};

export type ProductUiContent = {
  listing: ProductListingUiContent;
  detail: ProductDetailUiContent;
};

export const defaultCapacityRanges: ProductCapacityRangeOption[] = [
  { value: "0 - 5 tons", label: "0 - 5 tons", min: 0, max: 5, enabled: true, sortOrder: 10 },
  { value: "5 - 10 tons", label: "5 - 10 tons", min: 5, max: 10, enabled: true, sortOrder: 20 },
  { value: "10 - 20 tons", label: "10 - 20 tons", min: 10, max: 20, enabled: true, sortOrder: 30 },
  { value: "20 - 50 tons", label: "20 - 50 tons", min: 20, max: 50, enabled: true, sortOrder: 40 },
  { value: "50+ tons", label: "50+ tons", min: 50, max: null, enabled: true, sortOrder: 50 },
];

export const defaultBoomTypeOptions: ProductFilterDictionaryOption<BoomType>[] = [
  { value: "telescopic-boom", label: "Telescopic Boom", enabled: true, sortOrder: 10 },
  { value: "knuckle-boom", label: "Knuckle Boom", enabled: true, sortOrder: 20 },
  { value: "straight-boom", label: "Straight Boom", enabled: true, sortOrder: 30 },
  { value: "fly-jib", label: "Fly Jib", enabled: true, sortOrder: 40 },
  { value: "custom-solution", label: "Custom Solution", enabled: true, sortOrder: 50 },
];

export const defaultAvailabilityOptions: ProductFilterDictionaryOption<ProductStatus>[] = [
  { value: "in-stock", label: "In Stock", enabled: true, sortOrder: 10 },
  { value: "best-seller", label: "Best Seller", enabled: true, sortOrder: 20 },
  { value: "customizable", label: "Customizable", enabled: true, sortOrder: 30 },
  { value: "pre-order", label: "Pre-Order", enabled: true, sortOrder: 40 },
];

export const defaultApplicationOptions: ProductApplicationOption[] = [
  {
    value: "construction",
    label: "Construction",
    title: "Construction",
    text: "Building sites, structural lifting, material handling.",
    image: "/images/construction-scene.jpg",
    iconName: "construction",
    enabled: true,
    sortOrder: 10,
  },
  {
    value: "logistics",
    label: "Logistics",
    title: "Logistics",
    text: "Loading, unloading, and cargo handling at various sites.",
    image: "/images/cta-lift-photo.png",
    iconName: "truck",
    enabled: true,
    sortOrder: 20,
  },
  {
    value: "municipal-engineering",
    label: "Municipal Engineering",
    title: "Municipal Engineering",
    text: "Urban construction, road maintenance, and public facility projects.",
    image: "/images/global-network-blue.jpg",
    iconName: "building",
    enabled: true,
    sortOrder: 30,
  },
  {
    value: "marine",
    label: "Marine",
    title: "Marine",
    text: "Deck service, port handling, and offshore lifting operations.",
    image: "/images/marine-scene.jpg",
    iconName: "ship",
    enabled: true,
    sortOrder: 40,
  },
  {
    value: "agriculture",
    label: "Agriculture",
    title: "Agriculture",
    text: "Farm loading, field transport, and rural equipment support.",
    image: "/images/telescopic-banner.jpg",
    iconName: "tractor",
    enabled: true,
    sortOrder: 50,
  },
  {
    value: "mining",
    label: "Mining",
    title: "Mining",
    text: "Remote site handling and heavy material support work.",
    image: "/images/factory-workshop.jpg",
    iconName: "wrench",
    enabled: true,
    sortOrder: 60,
  },
];

export const defaultProductUiContent: ProductUiContent = {
  listing: {
    defaultBanner: {
      label: "BOB-LIFT Product Range",
      title: "Construction Equipment",
      image: "/images/products/truck-mounted-crane.png",
      imageAlt: "BOB-LIFT truck mounted crane",
      watermarkLabel: "BOB-LIFT",
      paragraphs: [
        {
          segments: [
            {
              text: "Explore reliable BOB-LIFT lifting and construction machinery for global projects.",
            },
          ],
        },
      ],
    },
    resultSummaryTemplate: "Showing {shown} of {total} products",
    filterButtonLabel: "Filter",
    filtersTitle: "Filters",
    clearAllLabel: "Clear All",
    closeFiltersLabel: "Close filters",
    filterGroups: {
      categories: "Product Category",
      capacities: "Lifting Capacity",
      boomTypes: "Boom Type",
      applications: "Application",
      availability: "Availability",
    },
    sortLabel: "Sort by:",
    sortOptions: {
      recommended: "Recommended",
      capacityAsc: "Capacity: Low to High",
      capacityDesc: "Capacity: High to Low",
      newest: "Newest",
      popular: "Popular",
    },
    sortVisibility: {
      recommended: true,
      capacityAsc: true,
      capacityDesc: true,
      newest: true,
      popular: true,
    },
    viewModeLabel: "Product view mode",
    gridViewLabel: "Grid view",
    listViewLabel: "List view",
    favoriteAddLabel: "Add to favorites",
    favoriteRemoveLabel: "Remove from favorites",
    quickViewLabelTemplate: "Quick view {product}",
    quickViewCloseLabel: "Close quick view",
    requestQuoteLabel: "Request Quote",
    viewDetailsLabel: "View Details",
    emptyTitle: "No matching products found",
    emptyText: "Try adjusting your filters or contact us for a custom lifting solution.",
    clearFiltersButtonLabel: "Clear Filters",
    previousPageLabel: "Previous",
    nextPageLabel: "Next",
    paginationLabel: "Product pagination",
    rowsPerPage: 3,
    maxCardSpecs: 3,
    maxListItemSpecs: 3,
    maxQuickViewSpecs: 3,
    defaultSortOption: "recommended",
    defaultViewMode: "grid",
    showFilters: true,
    showSortDropdown: true,
    showViewModeToggle: true,
    showFavoriteButton: true,
    showQuickViewButton: true,
    showPagination: true,
    showBanner: true,
    showResultSummary: true,
    showFilterCounts: true,
    filterVisibility: {
      categories: true,
      capacities: true,
      boomTypes: true,
      applications: true,
      availability: true,
    },
    cardVisibility: {
      statusBadge: true,
      categoryLabel: true,
      capacityLabel: true,
      specGrid: true,
      ctaButtons: true,
    },
    listItemVisibility: {
      statusBadge: true,
      categoryLabel: true,
      capacityLabel: true,
      boomTypeLabel: true,
      description: true,
      specGrid: true,
      ctaButtons: true,
    },
    quickViewVisibility: {
      statusBadge: true,
      categoryLabel: true,
      description: true,
      specGrid: true,
      ctaButtons: true,
      favoriteButton: true,
    },
    statusLabels: labelsFromOptions(defaultAvailabilityOptions),
    boomTypeLabels: labelsFromOptions(defaultBoomTypeOptions),
    applicationLabels: labelsFromOptions(defaultApplicationOptions),
    capacityLabels: labelsFromOptions(defaultCapacityRanges),
    capacityRanges: defaultCapacityRanges,
    boomTypeOptions: defaultBoomTypeOptions,
    applicationOptions: defaultApplicationOptions,
    availabilityOptions: defaultAvailabilityOptions,
    specLabels: {
      "Max Lift": "Max Lift",
      "Boom Length": "Boom Length",
      Rotation: "Rotation",
      "Boom Type": "Boom Type",
      "Specification Status": "Specification Status",
      "Parameter Review": "Parameter Review",
      Model: "Model",
      Application: "Application",
      Customization: "Customization",
    },
  },
  detail: {
    requestQuoteLabel: "Request Quote",
    downloadCatalogLabel: "Download Catalog",
    viewTechnicalSpecsLabel: "View Technical Specs",
    anchorLabels: {
      overview: "Overview",
      features: "Key Features",
      technical: "Technical Specifications",
      details: "Product Details",
      catalog: "Catalog",
      applications: "Applications",
      faq: "FAQ",
      quote: "Request Quote",
    },
    ariaLabels: {
      anchorNav: "Product detail sections",
      sideNav: "Product page navigation",
    },
    hotspots: ["Telescopic Boom", "Hydraulic Cylinder", "Outriggers", "Control System", "Slewing Mechanism", "Truck Mounting Base"],
    overviewTitle: "Built for reliable truck-mounted lifting work.",
    overviewTextTemplate: "{description} The configuration focuses on smooth operation, stable control and practical export use for contractors, fleet buyers and project teams.",
    defaultFeatures: [
      { title: "Flexible Boom Operation", text: "Telescopic boom extends for versatile lifting in multiple working conditions." },
      { title: "Stable & Safe Lifting", text: "Outriggers support stable operation for material handling and site work." },
      { title: "Wide Application", text: "Designed for construction, logistics, utility work and municipal projects." },
      { title: "Export-Ready Configuration", text: "Prepared for overseas projects with practical documentation and delivery support." },
      { title: "Custom Chassis Matching", text: "Can be matched with compatible truck chassis and load requirements." },
    ],
    technicalTitle: "Technical Specifications",
    catalogBrandLabel: "BOB-LIFT",
    catalogCoverTitle: "Product Catalog",
    catalogTitleTemplate: "{model} Product Catalog",
    catalogCoverAltTemplate: "{product} catalog cover",
    downloadCenterLabel: "Download Center",
    downloadCatalogTitle: "Download Product Catalog",
    catalogDescriptionTemplate: "{catalogTitle} includes core specifications, working range information, and configuration notes for project evaluation.",
    requestPdfLabel: "Request PDF by Email",
    galleryTitle: "Product Detail Gallery",
    galleryEmptyLabel: "Product Detail View",
    galleryEmptyTitle: "Engineering visuals for product evaluation.",
    galleryEmptyText: "Present boom structure, installation layout, hydraulic system, operating range, and chassis mounting details in one focused section.",
    galleryMainImageLabel: "Main product view",
    galleryDetailImageLabel: "Product detail image",
    galleryDrawingLabel: "Specification drawing",
    defaultGalleryLabels: ["Full crane structure diagram", "Boom extension detail", "Outrigger stability detail", "Control system detail", "Installation and mounting detail"],
    applicationsTitle: "Applications",
    applicationCards: defaultApplicationOptions,
    reviewStatusLabels: {
      needsReview: "Needs review",
      inReview: "In review",
      verifiedFromSource: "Verified from source",
      changesRequired: "Changes required",
    },
    customizationLabels: {
      available: "Available",
      availableOnRequest: "Available on request",
    },
    specLabels: {
      model: "Model",
      maxLift: "Max Lift",
      maxLiftingCapacity: "Max Lifting Capacity",
      boomLength: "Boom Length",
      maxBoomLength: "Max Boom Length",
      rotation: "Rotation",
      boomType: "Boom Type",
      application: "Application",
      customization: "Customization",
      specificationStatus: "Specification Status",
      parameterReview: "Parameter Review",
    },
    faqTitle: "FAQ",
    defaultFaqs: [
      {
        question: "Which truck chassis can be matched with this model?",
        answer: "Send the working conditions, destination and chassis details to BOB-LIFT. The engineering team will confirm a practical configuration before quotation.",
      },
      {
        question: "Can the crane be customized for our working conditions?",
        answer: "Send the working conditions, destination and chassis details to BOB-LIFT. The engineering team will confirm a practical configuration before quotation.",
      },
      {
        question: "What is the typical delivery time?",
        answer: "Send the working conditions, destination and chassis details to BOB-LIFT. The engineering team will confirm a practical configuration before quotation.",
      },
      {
        question: "What export documents are provided?",
        answer: "Send the working conditions, destination and chassis details to BOB-LIFT. The engineering team will confirm a practical configuration before quotation.",
      },
      {
        question: "Do you provide installation and operation training?",
        answer: "Send the working conditions, destination and chassis details to BOB-LIFT. The engineering team will confirm a practical configuration before quotation.",
      },
      {
        question: "What after-sales support is available?",
        answer: "Send the working conditions, destination and chassis details to BOB-LIFT. The engineering team will confirm a practical configuration before quotation.",
      },
    ],
    quoteTitle: "Need a truck crane matched to your chassis and lifting task?",
    quoteText: "Send your working radius, load requirement, destination country, and truck chassis information. BOB-LIFT will help match a practical configuration.",
    contactEngineerLabel: "Contact Engineer",
    maxHeroSpecs: 4,
    maxApplicationCards: 4,
    maxHotspots: 6,
    heroVisibility: {
      categoryLabel: true,
      description: true,
      heroSpecs: true,
      hotspots: true,
      requestQuoteButton: true,
      catalogButton: true,
      technicalButton: true,
    },
    catalogVisibility: {
      cover: true,
      downloadButton: true,
      requestPdfButton: true,
      extraDocumentLinks: true,
    },
    quoteVisibility: {
      requestQuoteButton: true,
      contactEngineerButton: true,
    },
    sectionVisibility: {
      anchorNav: true,
      sideNav: true,
      overview: true,
      features: true,
      technical: true,
      gallery: true,
      catalog: true,
      applications: true,
      faq: true,
      quote: true,
    },
  },
};

export function formatProductUiText(template: string, values: Record<string, string | number>) {
  return Object.entries(values).reduce((text, [key, value]) => text.replaceAll(`{${key}}`, String(value)), template);
}

export function productStatusLabel(status: ProductStatus, ui: ProductListingUiContent) {
  return ui.statusLabels[status] ?? status;
}

export function boomTypeLabel(boomType: BoomType, ui: ProductListingUiContent) {
  return ui.boomTypeLabels[boomType] ?? boomType;
}

export function applicationLabel(application: ProductApplication, ui: ProductListingUiContent) {
  return ui.applicationLabels[application] ?? application;
}

export function capacityRangeLabel(range: LiftingCapacityRange, ui: ProductListingUiContent) {
  return ui.capacityLabels[range] ?? range;
}

export function specLabel(label: string, ui: ProductListingUiContent) {
  return ui.specLabels[label] ?? label;
}

export function specValue(value: string, ui: ProductListingUiContent) {
  const normalized = value.trim();
  return (
    ui.boomTypeLabels[normalized as BoomType] ??
    ui.applicationLabels[normalized as ProductApplication] ??
    ui.statusLabels[normalized as ProductStatus] ??
    ui.capacityLabels[normalized as LiftingCapacityRange] ??
    value
  );
}

function labelsFromOptions<T extends string>(options: ProductFilterDictionaryOption<T>[]) {
  return Object.fromEntries(options.map((option) => [option.value, option.label])) as Record<T, string>;
}
