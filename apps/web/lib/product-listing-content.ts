import type {
  BoomType,
  ProductCategory,
  ProductFilters,
  ProductListingBannerContent,
  ProductListingCategoryMeta,
  ProductListingSeoSegment,
} from "@/types/product-listing";

function hrefForFilters(filters: Partial<ProductFilters>) {
  const params = new URLSearchParams();
  const category = filters.categories?.[0];
  const boomType = filters.boomTypes?.[0];

  if (category) params.set("category", slugifyFilterValue(category));
  if (boomType) params.set("boom", slugifyFilterValue(boomType));

  return params.size > 0 ? `/products?${params.toString()}` : "/products";
}

function seoLink(text: string, filters: Partial<ProductFilters>): ProductListingSeoSegment {
  return { text, href: hrefForFilters(filters), filters };
}

const categoryFilter = (category: ProductCategory): Partial<ProductFilters> => ({
  categories: [category],
});

const categoryBoomFilter = (category: ProductCategory, boomType: BoomType): Partial<ProductFilters> => ({
  categories: [category],
  boomTypes: [boomType],
});

export const defaultProductListingBannerContent: ProductListingBannerContent = {
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
};

export const productCategoryBannerContent: Partial<Record<ProductCategory, ProductListingBannerContent>> = {
  "Truck Mounted Crane": {
    label: "BOB-LIFT Product Range",
    title: "Truck Mounted Cranes",
    image: "/images/products/truck-mounted-crane.png",
    imageAlt: "BOB-LIFT truck mounted crane",
    paragraphs: [
      {
        segments: [
          { text: "One of the things you need to ensure when getting " },
          seoLink("truck mounted cranes", categoryFilter("Truck Mounted Crane")),
          { text: " is choosing the best in the market. Our catalog is made of the best quality cranes for both new and returning clients. We are committed to delivering quality, whether you choose to go with a " },
          seoLink("knuckle boom crane", categoryBoomFilter("Truck Mounted Crane", "knuckle-boom")),
          { text: " or a " },
          seoLink("telescopic boom option", categoryBoomFilter("Truck Mounted Crane", "telescopic-boom")),
          { text: "." },
        ],
      },
      {
        segments: [
          { text: "The list below contains some of our top " },
          seoLink("BOB-LIFT truck mounted cranes", categoryFilter("Truck Mounted Crane")),
          { text: ". They get assembled on whatever truck you need them on as long as they are compatible. Our cranes have different weights as well and we will help you figure out what works best." },
        ],
      },
    ],
  },
  "Marine Crane": {
    label: "BOB-LIFT Marine Product Range",
    title: "Marine Cranes",
    image: "/images/products/marine-crane.png",
    imageAlt: "BOB-LIFT marine crane",
    paragraphs: [
      {
        segments: [
          seoLink("Marine cranes", categoryFilter("Marine Crane")),
          { text: " from BOB-LIFT are built for vessel decks, port handling, dock service and offshore lifting tasks. Choose " },
          seoLink("knuckle boom marine cranes", categoryBoomFilter("Marine Crane", "knuckle-boom")),
          { text: " for flexible loading or telescopic marine cranes for straight reach and compact installation." },
        ],
      },
    ],
  },
  "Aerial Work Platform": {
    label: "BOB-LIFT Access Equipment",
    title: "Aerial Work Platforms",
    image: "/images/products/aerial-work-platform.png",
    imageAlt: "BOB-LIFT aerial work platform",
    paragraphs: [
      {
        segments: [
          seoLink("Aerial work platforms", categoryFilter("Aerial Work Platform")),
          { text: " support municipal maintenance, construction access, lighting work and elevated service tasks where stable working height and practical truck matching matter." },
        ],
      },
    ],
  },
  "Mini Dumper": {
    label: "BOB-LIFT Compact Equipment",
    title: "Mini Dumpers",
    image: "/images/products/mini-dumper.png",
    imageAlt: "BOB-LIFT mini dumper",
    paragraphs: [
      {
        segments: [
          seoLink("Mini dumpers", categoryFilter("Mini Dumper")),
          { text: " are compact material transport machines for construction, agriculture and narrow working areas where ordinary trucks cannot move efficiently." },
        ],
      },
    ],
  },
  "Forklift Fly Jib Crane": {
    label: "BOB-LIFT Attachment Range",
    title: "Forklift Fly Jib Cranes",
    image: "/images/products/forklift-fly-jib.png",
    imageAlt: "BOB-LIFT forklift fly jib crane",
    paragraphs: [
      {
        segments: [
          seoLink("Forklift fly jib cranes", categoryFilter("Forklift Fly Jib Crane")),
          { text: " convert compatible forklifts into practical lifting tools for warehouse, yard logistics and equipment positioning work." },
        ],
      },
    ],
  },
  "Pickup Truck Crane": {
    label: "BOB-LIFT Light Lifting Range",
    title: "Pickup Truck Cranes",
    image: "/images/products/pickup-truck-crane.png",
    imageAlt: "BOB-LIFT pickup truck crane",
    paragraphs: [
      {
        segments: [
          seoLink("Pickup truck cranes", categoryFilter("Pickup Truck Crane")),
          { text: " are designed for mobile loading, field service and light equipment handling when a smaller vehicle needs a reliable lifting solution." },
        ],
      },
    ],
  },
  "Crane Accessories": {
    label: "BOB-LIFT Crane Support Range",
    title: "Crane Accessories",
    image: "/images/products/crane-accessories.png",
    imageAlt: "BOB-LIFT crane accessories",
    paragraphs: [
      {
        segments: [
          seoLink("Crane accessories", categoryFilter("Crane Accessories")),
          { text: " cover matched lifting components, control kits and custom support parts for installation, service and project-specific crane configurations." },
        ],
      },
    ],
  },
};

export function getProductListingBannerContent(filters: ProductFilters, categories: ProductListingCategoryMeta[] = [], defaultContent = defaultProductListingBannerContent) {
  const normalizedDefaultContent = normalizeBannerContent(defaultContent);
  if (filters.categories.length !== 1) return normalizedDefaultContent;

  const selectedCategory = filters.categories[0];
  const category = categories.find((item) => item.slug === selectedCategory || item.label === selectedCategory);
  if (category) {
    return {
      label: category.bannerLabel || "BOB-LIFT Product Range",
      title: category.bannerTitle || category.label,
      image: category.bannerImage || normalizedDefaultContent.image,
      imageAlt: category.bannerImageAlt || category.label,
      watermarkLabel: normalizedDefaultContent.watermarkLabel,
      paragraphs: category.bannerParagraphs?.length
        ? category.bannerParagraphs.map(parseBannerParagraph)
        : normalizedDefaultContent.paragraphs,
    };
  }

  const fallbackContent = productCategoryBannerContent[selectedCategory];
  return fallbackContent ? normalizeBannerContent(fallbackContent) : normalizedDefaultContent;
}

export function productListingFiltersFromSearch(search: string): ProductFilters {
  const params = new URLSearchParams(search);

  return {
    categories: paramValues(params, "category"),
    capacities: paramValues(params, "capacity"),
    boomTypes: paramValues(params, "boom"),
    applications: paramValues(params, "application"),
    availability: paramValues(params, "status"),
  };
}

function paramValues(params: URLSearchParams, key: string) {
  return params.getAll(key).flatMap((value) => value.split(",")).map((value) => value.trim()).filter(Boolean);
}

function slugifyFilterValue(value: string) {
  return value.toLowerCase().replace(/&/g, " and ").replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");
}

function normalizeBannerContent(content: ProductListingBannerContent): ProductListingBannerContent {
  return {
    ...content,
    paragraphs: content.paragraphs.map((paragraph) => ({
      segments: paragraph.segments.flatMap((segment) => {
        if (segment.href) return [segment];
        return parseBannerTextSegments(segment.text);
      }),
    })),
  };
}

function parseBannerParagraph(text: string): ProductListingBannerContent["paragraphs"][number] {
  return { segments: parseBannerTextSegments(text) };
}

function parseBannerTextSegments(text: string): ProductListingSeoSegment[] {
  const segments: ProductListingSeoSegment[] = [];
  const linkPattern = /\[([^\]]+)\]\(([^)\s]+)\)/g;
  let lastIndex = 0;
  let match: RegExpExecArray | null;

  while ((match = linkPattern.exec(text))) {
    if (match.index > lastIndex) {
      segments.push({ text: text.slice(lastIndex, match.index) });
    }

    const linkText = match[1]?.trim();
    const href = match[2]?.trim();
    if (linkText && href) {
      segments.push({ text: linkText, href, filters: filtersFromHref(href) });
    }
    lastIndex = match.index + match[0].length;
  }

  if (lastIndex < text.length) {
    segments.push({ text: text.slice(lastIndex) });
  }

  return segments.length > 0 ? segments : [{ text }];
}

function filtersFromHref(href: string): Partial<ProductFilters> | undefined {
  try {
    const url = new URL(href, "https://www.bobliftcrane.local");
    const filters = productListingFiltersFromSearch(url.search);
    return Object.values(filters).some((items) => items.length > 0) ? filters : undefined;
  } catch {
    return undefined;
  }
}
