import { SearchX } from "lucide-react";

import { defaultProductUiContent, type ProductListingUiContent } from "@/lib/product-ui";

export function ProductEmptyState({ onClearFilters, ui = defaultProductUiContent.listing }: { onClearFilters: () => void; ui?: ProductListingUiContent }) {
  return (
    <div className="flex min-h-[420px] flex-col items-center justify-center rounded-[32px] border border-dashed border-[#CBD5E1] bg-white px-6 text-center shadow-[0_18px_50px_rgba(8,35,74,0.045)]">
      <span className="inline-flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-700">
        <SearchX className="h-7 w-7" />
      </span>
      <h2 className="mt-5 text-2xl font-black tracking-[-0.04em] text-[#0B1B33]">{ui.emptyTitle}</h2>
      <p className="mt-3 max-w-md text-sm font-semibold leading-6 text-slate-600">
        {ui.emptyText}
      </p>
      <button type="button" className="mt-6 min-h-12 rounded-full bg-[#0B1B33] px-6 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" onClick={onClearFilters}>
        {ui.clearFiltersButtonLabel}
      </button>
    </div>
  );
}
