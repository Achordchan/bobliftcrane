import Image from "next/image";
import { ArrowRight, Eye, FileText, Heart } from "lucide-react";

import { ProductBadge } from "@/components/products/ProductBadge";
import { ProductSpecGrid } from "@/components/products/ProductSpecGrid";
import { defaultProductUiContent, formatProductUiText, type ProductListingUiContent } from "@/lib/product-ui";
import type { Product } from "@/types/product-listing";

export function ProductCard({
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
  return (
    <article className="group flex min-h-full flex-col overflow-hidden rounded-[28px] border border-[#E5E7EB] bg-white shadow-[0_18px_50px_rgba(8,35,74,0.055)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_24px_70px_rgba(8,35,74,0.1)]">
      <div className="relative h-64 overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] sm:h-72 lg:h-64 xl:h-72">
        <span className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_82%,rgba(15,23,42,0.13),transparent_44%)]" />
        <span className="pointer-events-none absolute bottom-8 left-[16%] right-[16%] h-14 rounded-full bg-slate-400/18 blur-2xl" />
        <Image
          className="z-10 scale-[1.08] object-contain p-4 drop-shadow-[0_30px_34px_rgba(11,27,51,0.16)] transition duration-500 group-hover:scale-[1.12] sm:p-5"
          src={product.image}
          alt={product.name}
          fill
          loading={eagerImage ? "eager" : undefined}
          sizes="(min-width: 1280px) 430px, (min-width: 768px) 45vw, 92vw"
        />
        {ui.cardVisibility.statusBadge ? <ProductBadge status={product.status} className="absolute left-5 top-5 z-20" ui={ui} /> : null}
        {ui.showFavoriteButton || ui.showQuickViewButton ? (
          <div className="absolute right-5 top-5 z-20 flex flex-col gap-3">
            {ui.showFavoriteButton ? (
              <button
                type="button"
                aria-label={isFavorite ? ui.favoriteRemoveLabel : ui.favoriteAddLabel}
                className="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-[#0B1B33] shadow-[0_10px_24px_rgba(11,27,51,0.14)] transition hover:-translate-y-0.5 hover:text-blue-700"
                onClick={() => onToggleFavorite(product.id)}
              >
                <Heart className={`h-5 w-5 ${isFavorite ? "fill-blue-700 text-blue-700" : ""}`} />
              </button>
            ) : null}
            {ui.showQuickViewButton ? (
              <button
                type="button"
                aria-label={formatProductUiText(ui.quickViewLabelTemplate, { product: product.name })}
                className="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-[#0B1B33] shadow-[0_10px_24px_rgba(11,27,51,0.14)] transition hover:-translate-y-0.5 hover:text-blue-700"
                onClick={() => onQuickView(product)}
              >
                <Eye className="h-5 w-5" />
              </button>
            ) : null}
          </div>
        ) : null}
      </div>

      <div className="flex flex-1 flex-col p-6">
        {ui.cardVisibility.categoryLabel || ui.cardVisibility.capacityLabel ? (
          <div className="mb-3 flex items-center justify-between gap-4 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
            {ui.cardVisibility.categoryLabel ? <span>{product.category}</span> : <span />}
            {ui.cardVisibility.capacityLabel ? <span className="whitespace-nowrap text-[#0B1B33]">{product.capacity}</span> : null}
          </div>
        ) : null}
        <h2 className="text-xl font-black leading-tight tracking-[-0.035em] text-[#0B1B33]">{product.name}</h2>
        {ui.cardVisibility.specGrid ? <ProductSpecGrid product={product} maxSpecs={ui.maxCardSpecs} ui={ui} /> : null}

        {ui.cardVisibility.ctaButtons ? (
          <div className="mt-auto grid grid-cols-2 gap-3 pt-5">
            <a className="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-[18px] bg-[#0B1B33] px-4 text-[13px] font-black text-white shadow-[0_12px_28px_rgba(8,35,74,0.16)] transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" href={product.inquiryUrl}>
              <FileText className="h-4 w-4" /> {ui.requestQuoteLabel}
            </a>
            <a className="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-[18px] border border-[#0B1B33]/40 bg-white px-4 text-[13px] font-black text-[#0B1B33] transition hover:-translate-y-0.5 hover:border-blue-500 hover:text-blue-700" href={product.detailUrl}>
              {ui.viewDetailsLabel} <ArrowRight className="h-4 w-4" />
            </a>
          </div>
        ) : null}
      </div>
    </article>
  );
}
