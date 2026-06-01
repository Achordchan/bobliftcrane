import { X } from "lucide-react";

import {
  applicationLabel,
  boomTypeLabel,
  capacityRangeLabel,
  defaultProductUiContent,
  productStatusLabel,
  type ProductListingUiContent,
} from "@/lib/product-ui";
import { cn } from "@/lib/utils";
import type {
  BoomType,
  LiftingCapacityRange,
  ProductApplication,
  ProductCategory,
  ProductFilterOption,
  ProductFilters,
  ProductStatus,
} from "@/types/product-listing";

type FilterOptions = {
  categories: ProductFilterOption<ProductCategory>[];
  capacities: ProductFilterOption<LiftingCapacityRange>[];
  boomTypes: ProductFilterOption<BoomType>[];
  applications: ProductFilterOption<ProductApplication>[];
  availability: ProductFilterOption<ProductStatus>[];
};

export function ProductFilterSidebar({
  filters,
  options,
  onToggleFilter,
  onClearAll,
  onClose,
  ui = defaultProductUiContent.listing,
}: {
  filters: ProductFilters;
  options: FilterOptions;
  onToggleFilter: (group: keyof ProductFilters, value: ProductFilters[keyof ProductFilters][number]) => void;
  onClearAll: () => void;
  onClose?: () => void;
  ui?: ProductListingUiContent;
}) {
  return (
    <aside className="rounded-[28px] border border-[#E5E7EB] bg-white p-6 shadow-[0_18px_50px_rgba(8,35,74,0.055)]">
      <div className="mb-6 flex items-center justify-between gap-4">
        <h2 className="text-2xl font-black tracking-[-0.04em] text-[#0B1B33]">{ui.filtersTitle}</h2>
        <div className="flex items-center gap-3">
          <button type="button" className="text-sm font-bold text-slate-500 transition hover:text-blue-700" onClick={onClearAll}>
            {ui.clearAllLabel}
          </button>
          {onClose ? (
            <button type="button" className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#E5E7EB] text-[#0B1B33] transition hover:border-blue-300 hover:text-blue-700" onClick={onClose} aria-label={ui.closeFiltersLabel}>
              <X className="h-5 w-5" />
            </button>
          ) : null}
        </div>
      </div>

      <div className="space-y-7">
        {ui.filterVisibility.categories ? <FilterGroup title={ui.filterGroups.categories} group="categories" selected={filters.categories} options={options.categories} ui={ui} onToggle={(value) => onToggleFilter("categories", value)} /> : null}
        {ui.filterVisibility.capacities ? <FilterGroup title={ui.filterGroups.capacities} group="capacities" selected={filters.capacities} options={options.capacities} ui={ui} onToggle={(value) => onToggleFilter("capacities", value)} /> : null}
        {ui.filterVisibility.boomTypes ? <FilterGroup title={ui.filterGroups.boomTypes} group="boomTypes" selected={filters.boomTypes} options={options.boomTypes} ui={ui} onToggle={(value) => onToggleFilter("boomTypes", value)} /> : null}
        {ui.filterVisibility.applications ? <FilterGroup title={ui.filterGroups.applications} group="applications" selected={filters.applications} options={options.applications} ui={ui} onToggle={(value) => onToggleFilter("applications", value)} /> : null}
        {ui.filterVisibility.availability ? <FilterGroup title={ui.filterGroups.availability} group="availability" selected={filters.availability} options={options.availability} ui={ui} onToggle={(value) => onToggleFilter("availability", value)} /> : null}
      </div>

      <div className="mt-8 border-t border-[#E5E7EB] pt-6">
        <button type="button" className="min-h-12 w-full rounded-full border border-[#D8DEE8] bg-white px-5 text-sm font-black text-[#0B1B33] transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700" onClick={onClearAll}>
          {ui.clearAllLabel}
        </button>
      </div>
    </aside>
  );
}

function FilterGroup<T extends string>({
  title,
  group,
  selected,
  options,
  ui,
  onToggle,
}: {
  title: string;
  group: keyof ProductFilters;
  selected: T[];
  options: ProductFilterOption<T>[];
  ui: ProductListingUiContent;
  onToggle: (value: T) => void;
}) {
  return (
    <fieldset className="border-t border-[#E5E7EB] pt-6 first:border-t-0 first:pt-0">
      <legend className="mb-4 text-base font-black text-[#0B1B33]">{title}</legend>
      <div className="space-y-3">
        {options.map((option) => {
          const checked = selected.includes(option.value);
          const categoryDepth = group === "categories" ? option.depth ?? 0 : 0;
          return (
            <label
              className={cn(
                "flex cursor-pointer items-center gap-3 text-sm font-semibold text-slate-700",
                group === "categories" && option.hasChildren && "font-black text-[#0B1B33]",
                categoryDepth > 0 && "pl-6 text-slate-600",
              )}
              key={option.value}
            >
              <span className="relative flex h-5 w-5 shrink-0 items-center justify-center">
                <input className="peer h-5 w-5 appearance-none rounded border border-slate-300 bg-white transition checked:border-[#0B3F7D] checked:bg-[#0B3F7D] focus:outline-none focus:ring-4 focus:ring-blue-100" type="checkbox" checked={checked} onChange={() => onToggle(option.value)} />
                <span className="pointer-events-none absolute h-2.5 w-1.5 -translate-y-0.5 rotate-45 border-b-2 border-r-2 border-white opacity-0 peer-checked:opacity-100" />
              </span>
              <span className="min-w-0 flex-1 whitespace-nowrap">{option.label || displayFilterLabel(group, option.value, ui)}</span>
              {ui.showFilterCounts ? <span className="shrink-0 text-xs font-black text-slate-400">{option.count}</span> : null}
            </label>
          );
        })}
      </div>
    </fieldset>
  );
}

function displayFilterLabel(group: keyof ProductFilters, value: string, ui: ProductListingUiContent) {
  if (group === "capacities") return capacityRangeLabel(value as LiftingCapacityRange, ui);
  if (group === "boomTypes") return boomTypeLabel(value as BoomType, ui);
  if (group === "applications") return applicationLabel(value as ProductApplication, ui);
  if (group === "availability") return productStatusLabel(value as ProductStatus, ui);
  return value;
}
