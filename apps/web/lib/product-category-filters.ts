import type { Product, ProductFilters, ProductListingCategoryMeta } from "@/types/product-listing";

export function expandCategoryFilters(filters: ProductFilters, categories: ProductListingCategoryMeta[]): ProductFilters {
  if (filters.categories.length === 0) return filters;

  const categoryBySlug = new Map(categories.map((category) => [category.slug, category]));
  const expandedFilters: ProductFilters = {
    categories: [],
    capacities: [...filters.capacities],
    boomTypes: [...filters.boomTypes],
    applications: [...filters.applications],
    availability: [...filters.availability],
  };

  filters.categories.forEach((value) => {
    const category = categoryBySlug.get(value);
    if (category?.defaultFilters) {
      appendUnique(expandedFilters.categories, category.defaultFilters.categories);
      appendUnique(expandedFilters.capacities, category.defaultFilters.capacities);
      appendUnique(expandedFilters.boomTypes, category.defaultFilters.boomTypes);
      appendUnique(expandedFilters.applications, category.defaultFilters.applications);
      appendUnique(expandedFilters.availability, category.defaultFilters.availability);
      return;
    }

    expandedFilters.categories.push(value, ...(category?.includedCategorySlugs ?? []));
  });

  return {
    categories: Array.from(new Set(expandedFilters.categories)),
    capacities: Array.from(new Set(expandedFilters.capacities)),
    boomTypes: Array.from(new Set(expandedFilters.boomTypes)),
    applications: Array.from(new Set(expandedFilters.applications)),
    availability: Array.from(new Set(expandedFilters.availability)),
  };
}

export function productMatchesCategoryOption(product: Product, value: string, categories: ProductListingCategoryMeta[]) {
  const category = categories.find((item) => item.slug === value || item.label === value);
  const productCategory = product.categorySlug ?? product.category;
  const defaultCategories = category?.defaultFilters?.categories ?? [];

  if (defaultCategories.length > 0) {
    return defaultCategories.includes(productCategory) || defaultCategories.includes(product.category);
  }

  return (
    productCategory === value ||
    product.category === value ||
    product.category === category?.label ||
    Boolean(category?.includedCategorySlugs?.includes(productCategory))
  );
}

function appendUnique(target: string[], values?: string[]) {
  values?.forEach((value) => {
    if (value && !target.includes(value)) target.push(value);
  });
}
