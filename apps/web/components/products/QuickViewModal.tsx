"use client";

import Image from "next/image";
import { ArrowRight, Heart, X } from "lucide-react";
import { useEffect } from "react";

import { ProductBadge } from "@/components/products/ProductBadge";
import { ProductSpecGrid } from "@/components/products/ProductSpecGrid";
import { defaultProductUiContent, type ProductListingUiContent } from "@/lib/product-ui";
import type { Product } from "@/types/product-listing";

export function QuickViewModal({
  product,
  isFavorite,
  onClose,
  onToggleFavorite,
  ui = defaultProductUiContent.listing,
}: {
  product: Product | null;
  isFavorite: boolean;
  onClose: () => void;
  onToggleFavorite: (productId: string) => void;
  ui?: ProductListingUiContent;
}) {
  useEffect(() => {
    if (!product) return;

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") onClose();
    };

    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", onKeyDown);

    return () => {
      document.body.style.overflow = "";
      window.removeEventListener("keydown", onKeyDown);
    };
  }, [onClose, product]);

  if (!product) return null;

  return (
    <div className="fixed inset-0 z-[70] flex items-center justify-center bg-[#0B1B33]/45 px-4 py-8 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="quick-view-title" onMouseDown={onClose}>
      <div className="max-h-[calc(100vh-4rem)] w-full max-w-5xl overflow-y-auto rounded-[32px] bg-white shadow-[0_34px_120px_rgba(8,35,74,0.28)]" onMouseDown={(event) => event.stopPropagation()}>
        <div className="grid gap-0 lg:grid-cols-[0.95fr_1.05fr]">
          <div className="relative min-h-[340px] bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] lg:min-h-[560px]">
            <span className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_82%,rgba(15,23,42,0.12),transparent_44%)]" />
            <span className="pointer-events-none absolute bottom-12 left-[14%] right-[14%] h-16 rounded-full bg-slate-400/18 blur-2xl" />
            <Image className="z-10 object-contain p-10 drop-shadow-[0_32px_38px_rgba(11,27,51,0.16)]" src={product.image} alt={product.name} fill sizes="(min-width: 1024px) 480px, 92vw" />
            {ui.quickViewVisibility.statusBadge ? <ProductBadge status={product.status} className="absolute left-6 top-6 z-20" ui={ui} /> : null}
          </div>

          <div className="flex flex-col p-6 md:p-8">
            <div className="flex items-start justify-between gap-4">
              <div>
                {ui.quickViewVisibility.categoryLabel ? <p className="text-xs font-black uppercase tracking-[0.16em] text-blue-700">{product.category}</p> : null}
                <h2 id="quick-view-title" className="mt-3 text-3xl font-black leading-tight tracking-[-0.045em] text-[#0B1B33] md:text-4xl">
                  {product.name}
                </h2>
              </div>
              <button type="button" className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-[#E5E7EB] text-[#0B1B33] transition hover:border-blue-300 hover:text-blue-700" onClick={onClose} aria-label={ui.quickViewCloseLabel}>
                <X className="h-5 w-5" />
              </button>
            </div>

            {ui.quickViewVisibility.description ? <p className="mt-4 text-base font-semibold leading-7 text-slate-600">{product.shortDescription}</p> : null}

            {ui.quickViewVisibility.specGrid ? <ProductSpecGrid product={product} compact maxSpecs={ui.maxQuickViewSpecs} ui={ui} /> : null}

            <div className={ui.showFavoriteButton && ui.quickViewVisibility.favoriteButton ? "mt-auto grid gap-3 pt-8 sm:grid-cols-[1fr_1fr_auto]" : "mt-auto grid gap-3 pt-8 sm:grid-cols-2"}>
              {ui.quickViewVisibility.ctaButtons ? (
                <>
                  <a className="inline-flex min-h-12 items-center justify-center rounded-full bg-[#0B1B33] px-6 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" href={product.inquiryUrl}>
                    {ui.requestQuoteLabel}
                  </a>
                  <a className="inline-flex min-h-12 items-center justify-center gap-2 rounded-full border border-[#D8DEE8] bg-white px-6 text-sm font-black text-[#0B1B33] transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700" href={product.detailUrl}>
                    {ui.viewDetailsLabel} <ArrowRight className="h-4 w-4" />
                  </a>
                </>
              ) : null}
              {ui.showFavoriteButton && ui.quickViewVisibility.favoriteButton ? (
                <button type="button" className="inline-flex min-h-12 items-center justify-center rounded-full border border-[#D8DEE8] bg-white px-5 text-[#0B1B33] transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700" onClick={() => onToggleFavorite(product.id)} aria-label={isFavorite ? ui.favoriteRemoveLabel : ui.favoriteAddLabel}>
                  <Heart className={`h-5 w-5 ${isFavorite ? "fill-blue-700 text-blue-700" : ""}`} />
                </button>
              ) : null}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
