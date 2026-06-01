"use client";

import { useEffect, useMemo, useRef, useState } from "react";

import { ProductEmptyState } from "@/components/products/ProductEmptyState";
import { ProductFilterSidebar } from "@/components/products/ProductFilterSidebar";
import { ProductGrid } from "@/components/products/ProductGrid";
import { ProductListingBanner } from "@/components/products/ProductListingBanner";
import { ProductToolbar } from "@/components/products/ProductToolbar";
import { QuickViewModal } from "@/components/products/QuickViewModal";
import { expandCategoryFilters, productMatchesCategoryOption } from "@/lib/product-category-filters";
import { localeFromPathname, localizedPath } from "@/lib/i18n";
import { countByValue, emptyProductFilters, filterProducts, productMatchesCapacity, sortProducts } from "@/lib/product-filters";
import { getProductListingBannerContent } from "@/lib/product-listing-content";
import { defaultProductUiContent, type ProductListingUiContent } from "@/lib/product-ui";
import type { Product, ProductFilters, ProductListingCategoryMeta, ProductSortOption, ProductViewMode } from "@/types/product-listing";

const favoriteStorageKey = "boblift-product-favorites";

export function ProductListingPage({
  products = [],
  categories = [],
  ui = defaultProductUiContent.listing,
  initialCategorySlug,
}: {
  products?: Product[];
  categories?: ProductListingCategoryMeta[];
  ui?: ProductListingUiContent;
  initialCategorySlug?: string;
}) {
  const [filters, setFilters] = useState<ProductFilters>(() => initialFiltersFromCategory(initialCategorySlug));
  const [sortBy, setSortBy] = useState<ProductSortOption>(ui.defaultSortOption);
  const [viewMode, setViewMode] = useState<ProductViewMode>(ui.defaultViewMode);
  const [favoriteIds, setFavoriteIds] = useState<Set<string>>(() => new Set());
  const [quickViewProduct, setQuickViewProduct] = useState<Product | null>(null);
  const [isFilterDrawerOpen, setIsFilterDrawerOpen] = useState(false);
  const [gridColumnCount, setGridColumnCount] = useState(3);
  const [currentPage, setCurrentPage] = useState(1);
  const productResultsRef = useRef<HTMLDivElement>(null);
  const hasSyncedFiltersFromUrl = useRef(false);
  const suppressNextFilterUrlWrite = useRef(false);
  const allowFilterUrlWrites = useRef(false);
  const hasLoadedFavorites = useRef(false);

  const filterableCategories = useMemo(() => categories.filter((category) => category.showInProductFilters !== false), [categories]);
  const categoryValues = useMemo(
    () => filterableCategories.length > 0
      ? filterableCategories.map((category, index) => ({ value: category.slug, label: category.label, enabled: true, sortOrder: index + 1, depth: category.depth, hasChildren: category.hasChildren }))
      : Array.from(new Set(products.map((product) => product.category).filter(Boolean))),
    [filterableCategories, products],
  );
  const activeCapacityRanges = useMemo(() => ui.capacityRanges.filter((option) => option.enabled), [ui]);
  const activeBoomTypeOptions = useMemo(() => ui.boomTypeOptions.filter((option) => option.enabled), [ui]);
  const activeApplicationOptions = useMemo(() => ui.applicationOptions.filter((option) => option.enabled), [ui]);
  const activeAvailabilityOptions = useMemo(() => ui.availabilityOptions.filter((option) => option.enabled), [ui]);
  const effectiveFilters = useMemo(() => visibleFilters(filters, ui), [filters, ui]);
  const productFilters = useMemo(() => expandCategoryFilters(effectiveFilters, categories), [categories, effectiveFilters]);
  const filteredProducts = useMemo(() => filterProducts(products, productFilters, activeCapacityRanges), [activeCapacityRanges, productFilters, products]);
  const activeSortBy = useMemo(() => visibleSortOption(sortBy, ui), [sortBy, ui]);
  const displayedProducts = useMemo(() => sortProducts(filteredProducts, activeSortBy), [activeSortBy, filteredProducts]);
  const bannerContent = useMemo(() => getProductListingBannerContent(effectiveFilters, categories, ui.defaultBanner), [categories, effectiveFilters, ui]);
  const productsPerPage = gridColumnCount * ui.rowsPerPage;
  const totalPages = ui.showPagination ? Math.max(1, Math.ceil(displayedProducts.length / productsPerPage)) : 1;
  const paginatedProducts = useMemo(() => {
    if (!ui.showPagination) return displayedProducts;
    const startIndex = (currentPage - 1) * productsPerPage;
    return displayedProducts.slice(startIndex, startIndex + productsPerPage);
  }, [currentPage, displayedProducts, productsPerPage, ui.showPagination]);

  useEffect(() => {
    function updateGridColumns() {
      const width = window.innerWidth;
      if (width >= 1536) setGridColumnCount(3);
      else if (width >= 768) setGridColumnCount(2);
      else setGridColumnCount(1);
    }

    updateGridColumns();
    window.addEventListener("resize", updateGridColumns);
    return () => window.removeEventListener("resize", updateGridColumns);
  }, []);

  useEffect(() => {
    function syncFiltersFromSearch() {
      const params = new URLSearchParams(window.location.search);
      const categoryParams = paramValues(params, "category", initialCategorySlug);
      const categoryOptions = categories.length > 0
        ? categories.map((category) => ({ value: category.slug, label: category.label }))
        : Array.from(new Map(products.map((product) => [product.categorySlug ?? product.category, { value: product.categorySlug ?? product.category, label: product.category }])).values());

      suppressNextFilterUrlWrite.current = true;
      setFilters({
        categories: resolveParamValues(categoryParams, categoryOptions),
        capacities: resolveParamValues(paramValues(params, "capacity"), activeCapacityRanges),
        boomTypes: resolveParamValues(paramValues(params, "boom"), activeBoomTypeOptions),
        applications: resolveParamValues(paramValues(params, "application"), activeApplicationOptions),
        availability: resolveParamValues(paramValues(params, "status"), activeAvailabilityOptions),
      });
      hasSyncedFiltersFromUrl.current = true;
    }

    syncFiltersFromSearch();
    window.addEventListener("popstate", syncFiltersFromSearch);
    return () => window.removeEventListener("popstate", syncFiltersFromSearch);
  }, [activeApplicationOptions, activeAvailabilityOptions, activeBoomTypeOptions, activeCapacityRanges, categories, initialCategorySlug, products]);

  useEffect(() => {
    if (!hasSyncedFiltersFromUrl.current) return;
    if (suppressNextFilterUrlWrite.current) {
      suppressNextFilterUrlWrite.current = false;
      return;
    }
    if (!allowFilterUrlWrites.current) return;
    window.history.replaceState(null, "", currentLocalizedPath(pathForFilters(filters)));
  }, [filters]);

  useEffect(() => {
    setCurrentPage(1);
  }, [filters, sortBy, viewMode, productsPerPage]);

  useEffect(() => {
    if (currentPage > totalPages) setCurrentPage(totalPages);
  }, [currentPage, totalPages]);

  useEffect(() => {
    if (!ui.showFavoriteButton) return;

    try {
      const storedValue = window.localStorage.getItem(favoriteStorageKey);
      const storedIds = storedValue ? JSON.parse(storedValue) : [];
      if (Array.isArray(storedIds)) setFavoriteIds(new Set(storedIds.filter((item) => typeof item === "string")));
    } catch {
      setFavoriteIds(new Set());
    }
    hasLoadedFavorites.current = true;
  }, [ui.showFavoriteButton]);

  useEffect(() => {
    if (!ui.showFavoriteButton) return;
    if (!hasLoadedFavorites.current) return;
    window.localStorage.setItem(favoriteStorageKey, JSON.stringify(Array.from(favoriteIds)));
  }, [favoriteIds, ui.showFavoriteButton]);

  const filterOptions = useMemo(
    () => ({
      categories: countByValue(categoryValues, products, (product, value) => productMatchesCategoryOption(product, value, categories)),
      capacities: countByValue(activeCapacityRanges, products, (product, value) => productMatchesCapacity(product, value, activeCapacityRanges)),
      boomTypes: countByValue(activeBoomTypeOptions, products, (product, value) => product.boomType === value),
      applications: countByValue(activeApplicationOptions, products, (product, value) => product.applications.includes(value)),
      availability: countByValue(activeAvailabilityOptions, products, (product, value) => product.status === value),
    }),
    [activeApplicationOptions, activeAvailabilityOptions, activeBoomTypeOptions, activeCapacityRanges, categories, categoryValues, products],
  );

  function toggleFilter(group: keyof ProductFilters, value: ProductFilters[keyof ProductFilters][number]) {
    allowFilterUrlWrites.current = true;
    setFilters((currentFilters) => {
      const currentValues = currentFilters[group] as string[];
      const nextValues = currentValues.includes(value) ? currentValues.filter((item) => item !== value) : [...currentValues, value];
      return { ...currentFilters, [group]: nextValues };
    });
  }

  function clearAllFilters() {
    allowFilterUrlWrites.current = true;
    setFilters(emptyProductFilters);
    window.history.pushState(null, "", currentLocalizedPath("/products"));
  }

  function toggleFavorite(productId: string) {
    setFavoriteIds((currentIds) => {
      const nextIds = new Set(currentIds);
      if (nextIds.has(productId)) nextIds.delete(productId);
      else nextIds.add(productId);
      return nextIds;
    });
  }

  function changePage(page: number) {
    if (page === currentPage) return;

    setCurrentPage(page);
    window.requestAnimationFrame(() => {
      productResultsRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  }

  function applySeoLinkFilters(partialFilters: Partial<ProductFilters>, href: string) {
    allowFilterUrlWrites.current = true;
    setFilters({
      categories: partialFilters.categories ? [...partialFilters.categories] : [],
      capacities: partialFilters.capacities ? [...partialFilters.capacities] : [],
      boomTypes: partialFilters.boomTypes ? [...partialFilters.boomTypes] : [],
      applications: partialFilters.applications ? [...partialFilters.applications] : [],
      availability: partialFilters.availability ? [...partialFilters.availability] : [],
    });

    window.history.pushState(null, "", currentLocalizedPath(href));
    window.requestAnimationFrame(() => {
      productResultsRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  }

  const sidebar = (
    <ProductFilterSidebar
      filters={effectiveFilters}
      options={filterOptions}
      onToggleFilter={toggleFilter}
      onClearAll={clearAllFilters}
      ui={ui}
    />
  );

  return (
    <section className="bg-[#F8FAFC] py-8 md:py-12 lg:py-14">
      <div className="mx-auto max-w-[2320px] px-4 sm:px-6 xl:px-10 2xl:px-12">
        {ui.showBanner ? <ProductListingBanner content={bannerContent} onSeoLinkClick={applySeoLinkFilters} /> : null}
        <ProductToolbar
          productCount={paginatedProducts.length}
          totalProductCount={displayedProducts.length}
          sortBy={activeSortBy}
          viewMode={viewMode}
          onSortChange={setSortBy}
          onViewModeChange={setViewMode}
          onOpenFilters={() => setIsFilterDrawerOpen(true)}
          ui={ui}
        />

        <div className={ui.showFilters ? "grid gap-7 lg:grid-cols-[320px_minmax(0,1fr)] xl:gap-8 min-[1800px]:grid-cols-[400px_minmax(0,1fr)]" : "grid gap-7"}>
          {ui.showFilters ? <div className="hidden lg:block lg:self-start">{sidebar}</div> : null}

          <div className="min-w-0 scroll-mt-6" ref={productResultsRef}>
            {displayedProducts.length > 0 ? (
              <>
                <ProductGrid products={paginatedProducts} viewMode={viewMode} favoriteIds={favoriteIds} onToggleFavorite={toggleFavorite} onQuickView={setQuickViewProduct} ui={ui} />
                <ProductPagination currentPage={currentPage} totalPages={totalPages} onPageChange={changePage} ui={ui} />
              </>
            ) : (
              <ProductEmptyState onClearFilters={clearAllFilters} ui={ui} />
            )}
          </div>
        </div>
      </div>

      {ui.showFilters && isFilterDrawerOpen ? (
        <div className="fixed inset-0 z-[60] bg-[#0B1B33]/40 backdrop-blur-sm lg:hidden" onMouseDown={() => setIsFilterDrawerOpen(false)}>
          <div className="ml-auto h-full w-full max-w-md overflow-y-auto bg-[#F8FAFC] p-4 shadow-[0_30px_90px_rgba(8,35,74,0.24)]" onMouseDown={(event) => event.stopPropagation()}>
            <ProductFilterSidebar
              filters={filters}
              options={filterOptions}
              onToggleFilter={toggleFilter}
              onClearAll={clearAllFilters}
              onClose={() => setIsFilterDrawerOpen(false)}
              ui={ui}
            />
          </div>
        </div>
      ) : null}

      {ui.showQuickViewButton ? (
        <QuickViewModal product={quickViewProduct} isFavorite={quickViewProduct ? favoriteIds.has(quickViewProduct.id) : false} onClose={() => setQuickViewProduct(null)} onToggleFavorite={toggleFavorite} ui={ui} />
      ) : null}
    </section>
  );
}


function initialFiltersFromCategory(initialCategorySlug?: string): ProductFilters {
  return initialCategorySlug
    ? { ...emptyProductFilters, categories: [initialCategorySlug] }
    : emptyProductFilters;
}


function ProductPagination({
  currentPage,
  totalPages,
  onPageChange,
  ui,
}: {
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  ui: ProductListingUiContent;
}) {
  if (totalPages <= 1) return null;

  const pages = Array.from({ length: totalPages }, (_, index) => index + 1);

  return (
    <nav className="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label={ui.paginationLabel}>
      <button
        type="button"
        className="h-11 rounded-full border border-[#D8DEE8] bg-white px-5 text-sm font-black text-[#0B1B33] transition hover:border-blue-300 disabled:cursor-not-allowed disabled:opacity-40"
        disabled={currentPage === 1}
        onClick={() => onPageChange(currentPage - 1)}
      >
        {ui.previousPageLabel}
      </button>

      {pages.map((page) => (
        <button
          type="button"
          aria-current={page === currentPage ? "page" : undefined}
          className={`h-11 min-w-11 rounded-full px-4 text-sm font-black transition ${page === currentPage ? "bg-[#0B1B33] text-white" : "border border-[#D8DEE8] bg-white text-[#0B1B33] hover:border-blue-300"}`}
          key={page}
          onClick={() => onPageChange(page)}
        >
          {page}
        </button>
      ))}

      <button
        type="button"
        className="h-11 rounded-full border border-[#D8DEE8] bg-white px-5 text-sm font-black text-[#0B1B33] transition hover:border-blue-300 disabled:cursor-not-allowed disabled:opacity-40"
        disabled={currentPage === totalPages}
        onClick={() => onPageChange(currentPage + 1)}
      >
        {ui.nextPageLabel}
      </button>
    </nav>
  );
}


function slugifyFilterValue(value: string) {
  return value.toLowerCase().replace(/&/g, " and ").replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");
}

function paramValues(params: URLSearchParams, key: string, fallback?: string) {
  const values = params.getAll(key).flatMap((value) => value.split(",")).map((value) => value.trim()).filter(Boolean);
  return values.length > 0 ? values : fallback ? [fallback] : [];
}

function resolveParamValues<T extends string>(values: string[], options: { value: T; label: string }[]) {
  const optionMap = new Map<string, T>();
  options.forEach((option) => {
    optionMap.set(option.value, option.value);
    optionMap.set(slugifyFilterValue(option.value), option.value);
    optionMap.set(slugifyFilterValue(option.label), option.value);
  });

  return values.flatMap((value) => {
    const resolved = optionMap.get(value) ?? optionMap.get(slugifyFilterValue(value));
    return resolved ? [resolved] : [];
  });
}

function pathForFilters(filters: ProductFilters) {
  const params = new URLSearchParams();
  appendFilterParams(params, "category", filters.categories);
  appendFilterParams(params, "capacity", filters.capacities);
  appendFilterParams(params, "boom", filters.boomTypes);
  appendFilterParams(params, "application", filters.applications);
  appendFilterParams(params, "status", filters.availability);
  const query = params.toString();
  return query ? `/products?${query}` : "/products";
}

function appendFilterParams(params: URLSearchParams, key: string, values: string[]) {
  values.forEach((value) => params.append(key, slugifyFilterValue(value)));
}

function currentLocalizedPath(href: string) {
  return localizedPath(href, localeFromPathname(window.location.pathname));
}

function visibleFilters(filters: ProductFilters, ui: ProductListingUiContent): ProductFilters {
  return ui.showFilters || Object.values(filters).some((items) => items.length > 0)
    ? filters
    : emptyProductFilters;
}

function visibleSortOption(sortBy: ProductSortOption, ui: ProductListingUiContent): ProductSortOption {
  if (ui.sortVisibility[sortBy] !== false) return sortBy;
  return (["recommended", "capacityAsc", "capacityDesc", "newest", "popular"] as ProductSortOption[])
    .find((option) => ui.sortVisibility[option] !== false) ?? ui.defaultSortOption;
}
