import Image from "next/image";
import { ArrowRight, BookOpen, Building2, ChevronDown, Download, FileText, Globe2, Home, Layers, Mail, Maximize2, RotateCw, ShieldCheck, SlidersHorizontal, Weight, Wrench } from "lucide-react";
import type { ComponentType } from "react";

import type { ProductDetailUiContent } from "@/lib/product-ui";
import type { ProductDetailModel } from "@/components/products/ProductDetailTypes";

const anchorItemDefinitions = [
  { id: "overview", icon: Globe2, visibleWhen: "overview" },
  { id: "features", icon: ShieldCheck, visibleWhen: "features" },
  { id: "technical", icon: SlidersHorizontal, visibleWhen: "technical" },
  { id: "details", icon: FileText, visibleWhen: "gallery" },
  { id: "catalog", icon: BookOpen, visibleWhen: "catalog" },
  { id: "applications", icon: Building2, visibleWhen: "applications" },
  { id: "faq", icon: ChevronDown, visibleWhen: "faq" },
  { id: "quote", icon: Mail, visibleWhen: "quote" },
] as const;

const hotspotPositions = [
  "right-[5%] top-[9%]",
  "right-[30%] top-[30%]",
  "left-[36%] bottom-[10%]",
  "right-[32%] top-[47%]",
  "right-[10%] top-[58%]",
  "right-[15%] bottom-[10%]",
];

type AnchorAvailability = {
  hasApplications?: boolean;
};

function anchorItems(ui: ProductDetailUiContent, availability: AnchorAvailability = {}) {
  return anchorItemDefinitions
    .filter((item) => {
      if (!ui.sectionVisibility[item.visibleWhen]) return false;
      if (item.id === "applications") return availability.hasApplications !== false;
      return true;
    })
    .map((item) => ({
      ...item,
      label: ui.anchorLabels[item.id],
    }));
}

const specIconMap: Record<string, ComponentType<{ className?: string }>> = {
  maxLift: Weight,
  maxLiftingCapacity: Weight,
  boomLength: Maximize2,
  maxBoomLength: Maximize2,
  rotation: RotateCw,
  boomType: Wrench,
};

export function ProductHero({
  product,
  catalogActionLabel,
  catalogHref,
  ui,
}: {
  product: ProductDetailModel;
  catalogActionLabel: string;
  catalogHref: string;
  ui: ProductDetailUiContent;
}) {
  const hotspots = product.hotspots?.length ? product.hotspots : ui.hotspots;

  return (
    <section className="relative overflow-hidden border-b border-[#E5E7EB] bg-[radial-gradient(circle_at_72%_28%,#ffffff_0%,#edf4ff_38%,#f8fafc_74%)]">
      <div className="page-shell grid min-h-[650px] grid-cols-1 items-center gap-10 py-12 lg:grid-cols-[0.9fr_1.1fr] xl:min-h-[700px]">
        <div className="relative z-10 max-w-[650px]">
          {ui.heroVisibility.categoryLabel ? (
            <p className="mb-5 text-sm font-black text-blue-700">
              {product.category}
            </p>
          ) : null}
          <h1 className="text-[46px] font-black leading-[0.98] tracking-[-0.065em] text-[#0B1B33] md:text-[70px] xl:text-[78px]">
            {product.name}
          </h1>
          {ui.heroVisibility.description ? (
            <p className="mt-7 max-w-[620px] text-lg font-semibold leading-8 text-slate-600">
              {product.description}
            </p>
          ) : null}

          {ui.heroVisibility.heroSpecs ? <div className="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-4">
            {product.heroSpecs.map((spec) => {
              const Icon = spec.key ? specIconMap[spec.key] ?? Layers : Layers;

              return (
                <div
                  className="min-h-[126px] rounded-[18px] border border-[#DDE5EF] bg-white/88 p-4 shadow-[0_14px_40px_rgba(8,35,74,0.07)]"
                  key={spec.label}
                >
                  <Icon className="h-7 w-7 text-blue-600" />
                  <p className="mt-4 text-[11px] font-black text-slate-500">
                    {spec.label}
                  </p>
                  <p className="mt-1 text-2xl font-black leading-tight tracking-[-0.045em] text-[#0B1B33]">
                    {spec.value}
                  </p>
                </div>
              );
            })}
          </div> : null}

          {ui.heroVisibility.requestQuoteButton || ui.heroVisibility.catalogButton || ui.heroVisibility.technicalButton ? (
            <div className="mt-8 flex flex-wrap items-center gap-4">
              {ui.heroVisibility.requestQuoteButton ? (
                <a
                  className="inline-flex h-14 items-center justify-center gap-3 rounded-[8px] bg-[#0B3F7D] px-7 text-sm font-black text-white shadow-[0_18px_38px_rgba(11,63,125,0.2)] transition hover:-translate-y-0.5 hover:bg-[#0B1B33]"
                  href={product.inquiryUrl}
                >
                  {ui.requestQuoteLabel} <ArrowRight className="h-4 w-4" />
                </a>
              ) : null}
              {ui.heroVisibility.catalogButton && ui.sectionVisibility.catalog ? (
              <a
                className="inline-flex h-14 items-center justify-center gap-3 rounded-[8px] border border-blue-200 bg-white px-7 text-sm font-black text-[#0B3F7D] transition hover:-translate-y-0.5 hover:border-blue-400"
                href={catalogHref}
              >
                <Download className="h-4 w-4" /> {catalogActionLabel}
              </a>
            ) : null}
            {ui.heroVisibility.technicalButton && ui.sectionVisibility.technical ? (
              <a
                className="inline-flex h-14 items-center justify-center gap-2 px-2 text-sm font-black text-[#0B3F7D]"
                href="#technical"
              >
                {ui.viewTechnicalSpecsLabel} <ArrowRight className="h-4 w-4" />
              </a>
            ) : null}
            </div>
          ) : null}
        </div>

        <div className="relative min-h-[420px] lg:min-h-[560px]">
          <span className="pointer-events-none absolute inset-0 rounded-[42px] bg-[linear-gradient(135deg,rgba(255,255,255,0.5),rgba(226,232,240,0.68))]" />
          <span className="pointer-events-none absolute inset-x-[18%] bottom-[12%] h-24 rounded-full bg-slate-500/15 blur-3xl" />
          <Image
            className="z-10 object-contain p-5 drop-shadow-[0_38px_38px_rgba(8,35,74,0.18)]"
            src={product.image}
            alt={product.name}
            fill
            sizes="(min-width: 1024px) 55vw, 92vw"
            priority
          />
          {ui.heroVisibility.hotspots ? <div className="pointer-events-none absolute inset-0 z-20 hidden lg:block">
            {hotspots
              .slice(0, Math.min(hotspotPositions.length, boundedHotspotCount(ui.maxHotspots)))
              .map((label, index) => (
                <div
                  className={`absolute ${hotspotPositions[index]}`}
                  key={label}
                >
                  <span className="inline-flex items-center gap-2 rounded-[12px] bg-white/95 px-3 py-2 text-xs font-semibold text-[#0B1B33] shadow-[0_12px_32px_rgba(8,35,74,0.14)] ring-1 ring-[#E5E7EB]">
                    <span className="grid h-7 w-7 place-items-center rounded-full bg-blue-600 text-xs font-black text-white">
                      {index + 1}
                    </span>
                    {label}
                  </span>
                </div>
              ))}
          </div> : null}
        </div>
      </div>
    </section>
  );
}

function boundedHotspotCount(value: number) {
  return Number.isInteger(value) ? Math.min(6, Math.max(1, value)) : 6;
}

export function AnchorNav({
  hasApplications,
  ui,
}: {
  hasApplications?: boolean;
  ui: ProductDetailUiContent;
}) {
  const items = anchorItems(ui, { hasApplications });
  const homeTarget = items[0]?.id;
  if (!homeTarget) return null;

  return (
    <div className="sticky top-0 z-30 border-y border-[#E5E7EB] bg-white/92 backdrop-blur-xl">
      <nav
        className="page-shell flex min-h-14 items-center overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        aria-label={ui.ariaLabels.anchorNav}
      >
        <a
          className="grid h-14 min-w-16 place-items-center border-x border-[#E5E7EB] text-[#0B3F7D]"
          href={`#${homeTarget}`}
        >
          <Home className="h-5 w-5" />
        </a>
        {items.map((item) => (
          <a
            className="flex h-14 min-w-max items-center justify-center border-r border-[#E5E7EB] px-8 text-sm font-semibold text-[#0B1B33] transition hover:bg-blue-50 hover:text-blue-700"
            href={`#${item.id}`}
            key={item.id}
          >
            {item.label}
          </a>
        ))}
      </nav>
    </div>
  );
}

export function SideAnchorNav({
  hasApplications,
  ui,
}: {
  hasApplications?: boolean;
  ui: ProductDetailUiContent;
}) {
  const items = anchorItems(ui, { hasApplications });
  if (items.length === 0) return null;

  return (
    <aside className="hidden xl:block">
      <nav
        className="sticky top-20 rounded-[20px] border border-[#E5E7EB] bg-white p-4 shadow-[0_18px_50px_rgba(8,35,74,0.055)]"
        aria-label={ui.ariaLabels.sideNav}
      >
        {items.map(({ id, label, icon: Icon }) => (
          <a
            className="flex items-center gap-3 rounded-[14px] px-3 py-3 text-sm font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-blue-700"
            href={`#${id}`}
            key={id}
          >
            <Icon className="h-4 w-4" /> {label}
          </a>
        ))}
      </nav>
    </aside>
  );
}
