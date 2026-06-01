export type ProductCategory = string;

export type LiftingCapacityRange = string;

export type BoomType = string;

export type ProductApplication = string;

export type ProductStatus = string;

export type ProductSortOption = "recommended" | "capacityAsc" | "capacityDesc" | "newest" | "popular";

export type ProductViewMode = "grid" | "list";

export type ProductFilters = {
  categories: ProductCategory[];
  capacities: LiftingCapacityRange[];
  boomTypes: BoomType[];
  applications: ProductApplication[];
  availability: ProductStatus[];
};

export type ProductSpecItem = {
  label: string;
  value: string;
};

export type ProductFilterDictionaryOption<T extends string> = {
  value: T;
  label: string;
  enabled: boolean;
  sortOrder: number;
  depth?: number;
  hasChildren?: boolean;
};

export type ProductCapacityRangeOption = ProductFilterDictionaryOption<LiftingCapacityRange> & {
  min: number;
  max?: number | null;
};

export type ProductApplicationOption = ProductFilterDictionaryOption<ProductApplication> & {
  title: string;
  text: string;
  image: string;
  iconName: string;
};

export type Product = {
  id: string;
  slug: string;
  name: string;
  model?: string;
  category: ProductCategory;
  categorySlug?: string;
  capacity: string;
  capacityValue: number;
  boomType: BoomType;
  applications: ProductApplication[];
  status: ProductStatus;
  image: string;
  shortDescription: string;
  tags: string[];
  specs: ProductSpecItem[];
  sourceUrl?: string;
  parameterReviewStatus?: "needs-review" | "in-review" | "verified-from-source" | "changes-required";
  inquiryUrl: string;
  detailUrl: string;
  isFeatured: boolean;
  createdAt: string;
  popularity: number;
};

export type ProductFilterOption<T extends string> = {
  value: T;
  label: string;
  count: number;
  depth?: number;
  hasChildren?: boolean;
};

export type ProductListingCategoryMeta = {
  label: string;
  slug: string;
  legacyPath?: string;
  parentSlug?: string;
  depth?: number;
  hasChildren?: boolean;
  includedCategorySlugs?: string[];
  showInProductFilters?: boolean;
  defaultFilters?: Partial<ProductFilters>;
  bannerLabel?: string;
  bannerTitle?: string;
  bannerImage?: string;
  bannerImageAlt?: string;
  bannerParagraphs?: string[];
};

export type ProductListingSeoSegment = {
  text: string;
  href?: string;
  filters?: Partial<ProductFilters>;
};

export type ProductListingBannerContent = {
  label: string;
  title: string;
  image: string;
  imageAlt: string;
  watermarkLabel?: string;
  paragraphs: {
    segments: ProductListingSeoSegment[];
  }[];
};
