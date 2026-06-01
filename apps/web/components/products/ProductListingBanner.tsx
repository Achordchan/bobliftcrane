"use client";

import Image from "next/image";
import type { MouseEvent } from "react";

import type { ProductFilters, ProductListingBannerContent, ProductListingSeoSegment } from "@/types/product-listing";

export function ProductListingBanner({
  content,
  onSeoLinkClick,
}: {
  content: ProductListingBannerContent;
  onSeoLinkClick: (filters: Partial<ProductFilters>, href: string) => void;
}) {
  return (
    <section className="mb-6 overflow-hidden rounded-[32px] border border-[#DCE6F3] bg-[linear-gradient(135deg,#ffffff_0%,#eef6ff_54%,#dbeafe_100%)] shadow-[0_22px_70px_rgba(8,35,74,0.08)] md:mb-7">
      <div className="grid min-h-[168px] grid-cols-1 items-center gap-6 px-6 py-7 md:min-h-[210px] md:grid-cols-[minmax(0,1fr)_300px] md:px-9 lg:grid-cols-[minmax(0,1fr)_420px] xl:px-12">
        <div className="relative z-10 max-w-5xl">
          <p className="text-xs font-black uppercase tracking-[0.18em] text-[#0B3F7D]">{content.label}</p>
          <h1 className="mt-3 text-4xl font-black leading-none tracking-[-0.055em] text-[#0B1B33] md:text-6xl">{content.title}</h1>
          <div className="mt-4 space-y-3 text-sm font-semibold leading-7 text-slate-600 md:text-base md:leading-8">
            {content.paragraphs.map((paragraph, paragraphIndex) => (
              <p key={paragraphIndex}>
                {paragraph.segments.map((segment, segmentIndex) => (
                  <SeoSegment
                    key={`${paragraphIndex}-${segmentIndex}`}
                    segment={segment}
                    onSeoLinkClick={onSeoLinkClick}
                  />
                ))}
              </p>
            ))}
          </div>
        </div>

        <div className="relative hidden h-[150px] md:block">
          <span className="pointer-events-none absolute right-0 top-0 select-none text-[72px] font-black uppercase leading-none tracking-[-0.08em] text-white/80 lg:text-[92px]">
            {content.watermarkLabel ?? "BOB-LIFT"}
          </span>
          <div className="absolute inset-x-6 bottom-2 h-16 rounded-full bg-white/70 blur-xl" />
          <Image
            className="object-contain object-right-bottom drop-shadow-[0_26px_26px_rgba(8,35,74,0.16)]"
            src={content.image}
            alt={content.imageAlt}
            fill
            sizes="460px"
            priority
          />
        </div>
      </div>
    </section>
  );
}

function SeoSegment({
  segment,
  onSeoLinkClick,
}: {
  segment: ProductListingSeoSegment;
  onSeoLinkClick: (filters: Partial<ProductFilters>, href: string) => void;
}) {
  if (!segment.href) return <>{segment.text}</>;

  function handleClick(event: MouseEvent<HTMLAnchorElement>) {
    if (!segment.filters || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;

    event.preventDefault();
    onSeoLinkClick(segment.filters, segment.href ?? "/products");
  }

  return (
    <a className="font-black text-[#0B3F7D] underline decoration-blue-300/70 underline-offset-4 transition hover:text-blue-600 hover:decoration-blue-500" href={segment.href} onClick={handleClick}>
      {segment.text}
    </a>
  );
}
