import { Maximize2, RotateCw, Weight } from "lucide-react";
import type { ComponentType } from "react";

import { defaultProductUiContent, specLabel, specValue, type ProductListingUiContent } from "@/lib/product-ui";
import type { Product } from "@/types/product-listing";

const specIconMap: Record<string, ComponentType<{ className?: string; strokeWidth?: number }>> = {
  "Max Lift": Weight,
  "Boom Length": Maximize2,
  Rotation: RotateCw,
};

export function ProductSpecGrid({
  product,
  compact = false,
  maxSpecs,
  ui = defaultProductUiContent.listing,
}: {
  product: Product;
  compact?: boolean;
  maxSpecs?: number;
  ui?: ProductListingUiContent;
}) {
  const specs = product.specs.slice(0, boundedSpecCount(maxSpecs ?? ui.maxCardSpecs));
  if (specs.length === 0) return null;

  return (
    <div className={`grid grid-cols-3 gap-2 border-t border-[#E5E7EB] pt-5 ${compact ? "mt-4" : "mt-5"}`}>
      {specs.map((item) => {
        const Icon = specIconMap[item.label] ?? Weight;

        return (
          <div
            className="flex min-h-[132px] min-w-0 flex-col items-center justify-end rounded-[24px] border border-[#DDE5EF] bg-white px-2 pb-4 pt-4 text-center shadow-[inset_0_1px_0_rgba(255,255,255,0.92),0_12px_32px_rgba(8,35,74,0.055)]"
            key={`${item.label}-${item.value}`}
          >
            <Icon className="mb-3 h-7 w-7 text-[#2563EB]" strokeWidth={1.9} />
            <p className="line-clamp-2 max-w-full text-[clamp(8px,0.52vw,10px)] font-black uppercase tracking-[0.08em] text-slate-500">{specLabel(item.label, ui)}</p>
            <p className="mt-2 line-clamp-3 max-w-full text-[clamp(15px,0.95vw,21px)] font-black leading-tight tracking-[-0.045em] text-[#0B1B33]">{specValue(item.value, ui)}</p>
          </div>
        );
      })}
    </div>
  );
}

function boundedSpecCount(value: number) {
  return Number.isInteger(value) ? Math.min(6, Math.max(1, value)) : 3;
}
