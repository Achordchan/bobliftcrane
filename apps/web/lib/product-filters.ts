import type {
  LiftingCapacityRange,
  Product,
  ProductCapacityRangeOption,
  ProductFilterDictionaryOption,
  ProductFilters,
  ProductFilterOption,
  ProductSortOption,
} from "@/types/product-listing";

const fallbackCapacityRanges: ProductCapacityRangeOption[] = [
  { value: "0 - 5 tons", label: "0 - 5 tons", min: 0, max: 5, enabled: true, sortOrder: 10 },
  { value: "5 - 10 tons", label: "5 - 10 tons", min: 5, max: 10, enabled: true, sortOrder: 20 },
  { value: "10 - 20 tons", label: "10 - 20 tons", min: 10, max: 20, enabled: true, sortOrder: 30 },
  { value: "20 - 50 tons", label: "20 - 50 tons", min: 20, max: 50, enabled: true, sortOrder: 40 },
  { value: "50+ tons", label: "50+ tons", min: 50, max: null, enabled: true, sortOrder: 50 },
];

export const emptyProductFilters: ProductFilters = {
  categories: [],
  capacities: [],
  boomTypes: [],
  applications: [],
  availability: [],
};

export function isEmptyFilters(filters: ProductFilters) {
  return Object.values(filters).every((items) => items.length === 0);
}

export function productMatchesCapacity(product: Product, range: LiftingCapacityRange, capacityRanges: ProductCapacityRangeOption[] = fallbackCapacityRanges) {
  const bounds = capacityRanges.find((item) => item.value === range) ?? fallbackCapacityRanges.find((item) => item.value === range);
  if (product.capacityValue <= 0) return false;
  if (!bounds) return false;

  const max = typeof bounds.max === "number" ? bounds.max : Number.POSITIVE_INFINITY;
  if (bounds.min <= 0) return product.capacityValue > 0 && product.capacityValue <= max;
  return product.capacityValue > bounds.min && product.capacityValue <= max;
}

export function filterProducts(products: Product[], filters: ProductFilters, capacityRanges: ProductCapacityRangeOption[] = fallbackCapacityRanges) {
  return products.filter((product) => {
    const matchesCategory = filters.categories.length === 0 || filters.categories.includes(product.categorySlug ?? product.category) || filters.categories.includes(product.category);
    const matchesCapacity = filters.capacities.length === 0 || filters.capacities.some((range) => productMatchesCapacity(product, range, capacityRanges));
    const matchesBoom = filters.boomTypes.length === 0 || filters.boomTypes.includes(product.boomType);
    const matchesApplication = filters.applications.length === 0 || filters.applications.some((app) => product.applications.includes(app));
    const matchesAvailability = filters.availability.length === 0 || filters.availability.includes(product.status);

    return matchesCategory && matchesCapacity && matchesBoom && matchesApplication && matchesAvailability;
  });
}

export function sortProducts(products: Product[], sortBy: ProductSortOption) {
  const sorted = [...products];

  if (sortBy === "capacityAsc") {
    return sorted.sort((a, b) => capacityForAscending(a) - capacityForAscending(b));
  }

  if (sortBy === "capacityDesc") {
    return sorted.sort((a, b) => capacityForDescending(b) - capacityForDescending(a));
  }

  if (sortBy === "newest") {
    return sorted.sort((a, b) => Date.parse(b.createdAt) - Date.parse(a.createdAt));
  }

  if (sortBy === "popular") {
    return sorted.sort((a, b) => b.popularity - a.popularity);
  }

  return sorted.sort((a, b) => Number(b.isFeatured) - Number(a.isFeatured) || b.popularity - a.popularity || Date.parse(b.createdAt) - Date.parse(a.createdAt));
}

export function countByValue<T extends string>(
  items: readonly (T | ProductFilterDictionaryOption<T>)[],
  products: Product[],
  predicate: (product: Product, value: T) => boolean,
): ProductFilterOption<T>[] {
  return items.map((item) => {
    const option = typeof item === "string" ? { value: item, label: item } : item;

    return {
      value: option.value,
      label: option.label,
      count: products.filter((product) => predicate(product, option.value)).length,
    };
  });
}

function capacityForAscending(product: Product) {
  return product.capacityValue > 0 ? product.capacityValue : Number.MAX_SAFE_INTEGER;
}

function capacityForDescending(product: Product) {
  return product.capacityValue > 0 ? product.capacityValue : -1;
}
