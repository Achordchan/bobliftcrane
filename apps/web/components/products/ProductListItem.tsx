import Image from "next/image";
import { ArrowRight, Eye, FileText, Heart } from "lucide-react";

import { ProductBadge } from "@/components/products/ProductBadge";
import { boomTypeLabel, defaultProductUiContent, formatProductUiText, specLabel, type ProductListingUiContent } from "@/lib/product-ui";
import type { Product } from "@/types/product-listing";

export function ProductListItem({
  product,
  eagerImage = false,
  isFavorite,
  onToggleFavorite,
  onQuickView,
  ui = defaultProductUiContent.listing,
}: {
  product: Product;
  eagerImage?: boolean;
  isFavorite: boolean;
  onToggleFavorite: (productId: string) => void;
  onQuickView: (product: Product) => void;
  ui?: ProductListingUiContent;
}) {
  const specs = product.specs.slice(0, boundedSpecCount(ui.maxListItemSpecs));

  return (
    <article className="group grid gap-5 rounded-[28px] border border-[#E5E7EB] bg-white p-4 shadow-[0_18px_50px_rgba(8,35,74,0.055)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_24px_70px_rgba(8,35,74,0.1)] md:grid-cols-[300px_1fr] md:p-5 xl:grid-cols-[320px_1fr]">
      <div className="relative aspect-square w-full self-start overflow-hidden rounded-[22px] bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)]">
        <span className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_82%,rgba(15,23,42,0.12),transparent_44%)]" />
        <span className="pointer-events-none absolute bottom-8 left-[16%] right-[16%] h-12 rounded-full bg-slate-400/18 blur-2xl" />
        <Image
          className="z-10 scale-[1.08] object-contain p-4 drop-shadow-[0_28px_32px_rgba(11,27,51,0.16)] transition duration-500 group-hover:scale-[1.12] sm:p-5"
          src={product.image}
          alt={product.name}
          fill
          loading={eagerImage ? "eager" : undefined}
          sizes="(min-width: 1280px) 320px, (min-width: 768px) 300px, 92vw"
        />
        {ui.listItemVisibility.statusBadge ? <ProductBadge status={product.status} className="absolute left-4 top-4 z-20" ui={ui} /> : null}
      </div>

      <div className="flex flex-col justify-between gap-5 py-1">
        <div>
          {ui.listItemVisibility.categoryLabel || ui.listItemVisibility.capacityLabel || ui.listItemVisibility.boomTypeLabel ? (
            <div className="flex flex-wrap items-center gap-3 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
              {ui.listItemVisibility.categoryLabel ? <span>{product.category}</span> : null}
              {ui.listItemVisibility.categoryLabel && (ui.listItemVisibility.capacityLabel || ui.listItemVisibility.boomTypeLabel) ? <span className="h-1 w-1 rounded-full bg-slate-300" /> : null}
              {ui.listItemVisibility.capacityLabel ? <span className="text-[#0B1B33]">{product.capacity}</span> : null}
              {ui.listItemVisibility.capacityLabel && ui.listItemVisibility.boomTypeLabel ? <span className="h-1 w-1 rounded-full bg-slate-300" /> : null}
              {ui.listItemVisibility.boomTypeLabel ? <span>{boomTypeLabel(product.boomType, ui)}</span> : null}
            </div>
          ) : null}
          <h2 className="mt-3 text-2xl font-black leading-tight tracking-[-0.04em] text-[#0B1B33] lg:text-3xl">{product.name}</h2>
          {ui.listItemVisibility.description ? <p className="mt-3 max-w-3xl text-sm font-semibold leading-6 text-slate-600 lg:text-base lg:leading-7">{product.shortDescription}</p> : null}
          {ui.listItemVisibility.specGrid && specs.length > 0 ? (
            <div className="mt-4 grid max-w-3xl grid-cols-1 gap-2 sm:grid-cols-3">
              {specs.map((spec) => (
                <div className="rounded-2xl border border-[#E5E7EB] bg-[#F8FAFC] px-4 py-3" key={`${spec.label}-${spec.value}`}>
                  <p className="line-clamp-2 text-[10px] font-black uppercase tracking-[0.12em] text-slate-400">{specLabel(spec.label, ui)}</p>
                  <p className="mt-1 line-clamp-2 break-words text-base font-black leading-tight text-[#0B1B33]">{spec.value}</p>
                </div>
              ))}
            </div>
          ) : null}
        </div>

        <div className="flex flex-col gap-3 border-t border-[#E5E7EB] pt-5 sm:flex-row sm:items-center sm:justify-between">
          {ui.showFavoriteButton || ui.showQuickViewButton ? (
            <div className="flex gap-3">
              {ui.showFavoriteButton ? (
                <button
                  type="button"
                  aria-label={isFavorite ? ui.favoriteRemoveLabel : ui.favoriteAddLabel}
                  className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#E5E7EB] bg-white text-[#0B1B33] transition hover:-translate-y-0.5 hover:text-blue-700"
                  onClick={() => onToggleFavorite(product.id)}
                >
                  <Heart className={`h-5 w-5 ${isFavorite ? "fill-blue-700 text-blue-700" : ""}`} />
                </button>
              ) : null}
              {ui.showQuickViewButton ? (
                <button
                  type="button"
                  aria-label={formatProductUiText(ui.quickViewLabelTemplate, { product: product.name })}
                  className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#E5E7EB] bg-white text-[#0B1B33] transition hover:-translate-y-0.5 hover:text-blue-700"
                  onClick={() => onQuickView(product)}
                >
                  <Eye className="h-5 w-5" />
                </button>
              ) : null}
            </div>
          ) : null}
          {ui.listItemVisibility.ctaButtons ? (
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-[auto_auto]">
              <a className="inline-flex min-h-11 items-center justify-center gap-2 whitespace-nowrap rounded-[18px] bg-[#0B1B33] px-5 text-[13px] font-black text-white transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" href={product.inquiryUrl}>
                <FileText className="h-4 w-4" /> {ui.requestQuoteLabel}
              </a>
              <a className="inline-flex min-h-11 items-center justify-center gap-2 whitespace-nowrap rounded-[18px] border border-[#0B1B33]/40 bg-white px-5 text-[13px] font-black text-[#0B1B33] transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700" href={product.detailUrl}>
                {ui.viewDetailsLabel} <ArrowRight className="h-4 w-4" />
              </a>
            </div>
          ) : null}
        </div>
      </div>
    </article>
  );
}

function boundedSpecCount(value: number) {
  return Number.isInteger(value) ? Math.min(6, Math.max(1, value)) : 3;
}
