"use client";

import { Check, ChevronDown, Grid3X3, List, SlidersHorizontal } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";

import { defaultProductUiContent, formatProductUiText, type ProductListingUiContent } from "@/lib/product-ui";
import type { ProductSortOption, ProductViewMode } from "@/types/product-listing";

const sortValues: ProductSortOption[] = ["recommended", "capacityAsc", "capacityDesc", "newest", "popular"];

export function ProductToolbar({
  productCount,
  totalProductCount,
  sortBy,
  viewMode,
  onSortChange,
  onViewModeChange,
  onOpenFilters,
  ui = defaultProductUiContent.listing,
}: {
  productCount: number;
  totalProductCount: number;
  sortBy: ProductSortOption;
  viewMode: ProductViewMode;
  onSortChange: (sortBy: ProductSortOption) => void;
  onViewModeChange: (viewMode: ProductViewMode) => void;
  onOpenFilters: () => void;
  ui?: ProductListingUiContent;
}) {
  const hasSortOptions = sortValues.some((sortValue) => ui.sortVisibility[sortValue] !== false);

  return (
    <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between lg:mb-7">
      <div className="flex flex-wrap items-center gap-3">
        {ui.showResultSummary ? (
          <p className="text-sm font-black text-slate-500 md:text-base">{formatProductUiText(ui.resultSummaryTemplate, { shown: productCount, total: totalProductCount })}</p>
        ) : null}
        {ui.showFilters ? (
          <button
            type="button"
            className="inline-flex h-12 items-center gap-2 rounded-full border border-[#D8DEE8] bg-white px-5 text-sm font-black text-[#0B1B33] shadow-[0_10px_28px_rgba(8,35,74,0.06)] transition hover:border-blue-300 hover:text-blue-700 lg:hidden"
            onClick={onOpenFilters}
          >
            <SlidersHorizontal className="h-4 w-4" /> {ui.filterButtonLabel}
          </button>
        ) : null}
      </div>

      {(ui.showSortDropdown && hasSortOptions) || ui.showViewModeToggle ? (
        <div className="flex flex-wrap items-center gap-3">
          {ui.showSortDropdown && hasSortOptions ? <SortDropdown value={sortBy} onChange={onSortChange} ui={ui} /> : null}

          {ui.showViewModeToggle ? (
            <div className="flex rounded-xl border border-[#D8DEE8] bg-white p-1 shadow-[0_10px_28px_rgba(8,35,74,0.06)]" aria-label={ui.viewModeLabel}>
              <button
                type="button"
                aria-label={ui.gridViewLabel}
                aria-pressed={viewMode === "grid"}
                className={`inline-flex h-10 w-10 items-center justify-center rounded-lg transition ${viewMode === "grid" ? "bg-[#0B1B33] text-white" : "text-[#0B1B33] hover:bg-slate-100"}`}
                onClick={() => onViewModeChange("grid")}
              >
                <Grid3X3 className="h-5 w-5" />
              </button>
              <button
                type="button"
                aria-label={ui.listViewLabel}
                aria-pressed={viewMode === "list"}
                className={`inline-flex h-10 w-10 items-center justify-center rounded-lg transition ${viewMode === "list" ? "bg-[#0B1B33] text-white" : "text-[#0B1B33] hover:bg-slate-100"}`}
                onClick={() => onViewModeChange("list")}
              >
                <List className="h-5 w-5" />
              </button>
            </div>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}

function SortDropdown({ value, onChange, ui }: { value: ProductSortOption; onChange: (value: ProductSortOption) => void; ui: ProductListingUiContent }) {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const sortOptions = useMemo(
    () => sortValues
      .filter((sortValue) => ui.sortVisibility[sortValue] !== false)
      .map((sortValue) => ({ value: sortValue, label: ui.sortOptions[sortValue] })),
    [ui],
  );
  const selectedOption = useMemo(() => sortOptions.find((option) => option.value === value) ?? sortOptions[0], [sortOptions, value]);

  useEffect(() => {
    function handlePointerDown(event: MouseEvent) {
      if (!dropdownRef.current?.contains(event.target as Node)) setIsOpen(false);
    }

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") setIsOpen(false);
    }

    document.addEventListener("pointerdown", handlePointerDown);
    document.addEventListener("keydown", handleKeyDown);

    return () => {
      document.removeEventListener("pointerdown", handlePointerDown);
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, []);

  return (
    <div className="relative flex items-center gap-3" ref={dropdownRef}>
      <span className="text-sm font-black text-slate-500">{ui.sortLabel}</span>
      <button
        type="button"
        className="inline-flex h-12 min-w-[240px] items-center justify-between gap-4 rounded-xl border border-[#D8DEE8] bg-white px-4 text-left text-sm font-black text-[#0B1B33] shadow-[0_10px_28px_rgba(8,35,74,0.06)] transition hover:border-blue-300 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100"
        aria-haspopup="listbox"
        aria-expanded={isOpen}
        onClick={() => setIsOpen((current) => !current)}
      >
        <span>{selectedOption.label}</span>
        <ChevronDown className={`h-4 w-4 text-slate-400 transition ${isOpen ? "rotate-180" : ""}`} />
      </button>

      {isOpen ? (
        <div className="absolute right-0 top-[calc(100%+10px)] z-50 w-[240px] overflow-hidden rounded-2xl border border-[#DDE5EF] bg-white p-2 shadow-[0_24px_70px_rgba(8,35,74,0.16)]" role="listbox">
          {sortOptions.map((option) => {
            const selected = option.value === value;
            return (
              <button
                type="button"
                role="option"
                aria-selected={selected}
                className={`flex min-h-10 w-full items-center justify-between gap-3 rounded-xl px-3 text-left text-sm font-black transition ${selected ? "bg-blue-50 text-blue-700" : "text-[#0B1B33] hover:bg-[#F8FAFC]"}`}
                key={option.value}
                onClick={() => {
                  onChange(option.value);
                  setIsOpen(false);
                }}
              >
                <span>{option.label}</span>
                {selected ? <Check className="h-4 w-4" /> : null}
              </button>
            );
          })}
        </div>
      ) : null}
    </div>
  );
}
