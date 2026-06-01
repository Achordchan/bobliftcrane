"use client";

import Image from "next/image";
import { useCallback, useEffect, useRef, useState } from "react";
import { ArrowRight, ChevronLeft, ChevronRight, Ruler, Weight } from "lucide-react";
import { defaultHomeProductSectionContent, type HomeFeaturedProduct, type HomeProductSectionContent } from "@/lib/home-defaults";

import { type CatalogProduct, type ProductCategory } from "@/lib/product-catalog";

function specsFor(product: CatalogProduct, section: HomeProductSectionContent) {
  return [
    { label: section.modelSpecLabel, value: product.model },
    { label: section.capacitySpecLabel, value: product.capacity },
    { label: section.typeSpecLabel, value: product.type },
    { label: section.systemSpecLabel, value: section.systemValue },
  ];
}

export function ProductFamilies({
  catalog = [],
  section = defaultHomeProductSectionContent,
}: {
  catalog?: ProductCategory[];
  section?: HomeProductSectionContent;
}) {
  const [categoryIndex, setCategoryIndex] = useState(0);
  const [selectedProduct, setSelectedProduct] = useState<CatalogProduct | null>(null);
  const [categoryScroll, setCategoryScroll] = useState({ next: false, prev: false });
  const categoryScrollerRef = useRef<HTMLDivElement>(null);
  const selectedCategory = catalog[Math.min(categoryIndex, catalog.length - 1)] ?? null;

  const updateCategoryScrollState = useCallback(() => {
    const node = categoryScrollerRef.current;
    if (!node) return;

    const maxScrollLeft = node.scrollWidth - node.clientWidth;
    const nextState = {
      prev: node.scrollLeft > 2,
      next: node.scrollLeft < maxScrollLeft - 2,
    };

    setCategoryScroll((current) =>
      current.prev === nextState.prev && current.next === nextState.next ? current : nextState,
    );
  }, []);

  useEffect(() => {
    const node = categoryScrollerRef.current;
    if (!node) return;

    updateCategoryScrollState();
    node.addEventListener("scroll", updateCategoryScrollState, { passive: true });
    window.addEventListener("resize", updateCategoryScrollState);

    return () => {
      node.removeEventListener("scroll", updateCategoryScrollState);
      window.removeEventListener("resize", updateCategoryScrollState);
    };
  }, [updateCategoryScrollState]);

  function scrollCategories(direction: "prev" | "next") {
    const node = categoryScrollerRef.current;
    if (!node) return;

    const distance = Math.min(node.clientWidth * 0.72, 620);
    node.scrollBy({ left: direction === "next" ? distance : -distance, behavior: "smooth" });
  }

  function selectCategory(index: number) {
    setCategoryIndex(index);
    setSelectedProduct(null);
  }

  function openProduct(product: CatalogProduct) {
    if (section.showInlineProductDetail) {
      setSelectedProduct(product);
      return;
    }

    window.location.href = product.detailPath ?? product.href;
  }

  const stageClassName = selectedProduct
    ? "product-stage relative z-10 mt-8 overflow-hidden rounded-[28px] bg-white/80 p-5 shadow-[0_32px_90px_rgba(8,35,74,0.08)] md:mt-10 md:p-8 xl:rounded-[36px] xl:p-10"
    : "relative z-10 mt-8 md:mt-10";

  if (!selectedCategory) return null;

  return (
    <section id="products" className="relative overflow-hidden bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_48%,#ffffff_100%)] py-8 md:py-14 lg:py-20">
      <div className="page-shell">
        <div className="relative">
          <div className="mb-6 flex flex-col gap-3 md:mb-8 md:gap-4 lg:mb-10 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <p className="section-label mb-3">{section.label}</p>
              <h2 className="max-w-none whitespace-nowrap text-[clamp(26px,6.5vw,32px)] font-black leading-[0.96] tracking-[-0.055em] text-ink sm:text-[48px] lg:text-[62px]">
                {section.title}
              </h2>
            </div>
          </div>

          <div className="relative z-10">
            {section.showCategoryScrollButtons ? <CategoryScrollButton direction="prev" disabled={!categoryScroll.prev} label={section.previousCategoriesLabel} onClick={() => scrollCategories("prev")} /> : null}
            <div ref={categoryScrollerRef} className="-mx-4 overflow-x-auto scroll-smooth px-4 py-2 [-ms-overflow-style:none] [scrollbar-width:none] sm:mx-0 sm:px-0.5 [&::-webkit-scrollbar]:hidden">
              <div className="grid auto-cols-[calc((100vw-56px)/2)] grid-flow-col gap-3 sm:auto-cols-[230px] md:gap-4 lg:auto-cols-[250px] xl:auto-cols-[270px]">
                {catalog.map((item, index) => <ProductTile active={categoryIndex === index} item={item} key={`${item.name}-${index}`} onSelect={() => selectCategory(index)} />)}
              </div>
            </div>
            {section.showCategoryScrollButtons ? <CategoryScrollButton direction="next" disabled={!categoryScroll.next} label={section.nextCategoriesLabel} onClick={() => scrollCategories("next")} /> : null}
          </div>

          <div className={stageClassName}>
            {selectedProduct ? (
              <ProductDetailView key={selectedProduct.model} onBack={() => setSelectedProduct(null)} product={selectedProduct} section={section} />
            ) : (
              <ProductGridView category={selectedCategory} key={`${selectedCategory.name}-${categoryIndex}`} onSelect={openProduct} section={section} />
            )}
          </div>
        </div>
      </div>
    </section>
  );
}

export function FeaturedProducts({
  products = [],
  section = defaultHomeProductSectionContent,
}: {
  products?: HomeFeaturedProduct[];
  section?: HomeProductSectionContent;
}) {
  if (products.length === 0) return null;

  return (
    <section className="bg-gradient-to-r from-white via-[#f8fafc] to-white py-8 md:py-14">
      <div className="page-shell">
        <div className="mb-5 flex flex-col gap-3 sm:mb-8 lg:grid lg:grid-cols-[300px_1fr] lg:gap-10">
          <div>
            <p className="section-label mb-3 md:mb-4">{section.featuredLabel}</p>
            <h2 className="max-w-none text-[clamp(27px,6.9vw,34px)] font-black leading-[1.02] tracking-[-0.055em] text-ink sm:text-3xl sm:leading-tight sm:tracking-[-0.03em]">
              {section.featuredTitle}
            </h2>
          </div>
          {section.showFeaturedCta ? (
            <a className="inline-flex w-fit items-center gap-2 text-sm font-black text-ink lg:mt-9 lg:self-start" href={section.featuredCtaHref}>
              {section.featuredCtaLabel} <ArrowRight className="h-4 w-4" />
            </a>
          ) : null}
        </div>

        <div className="-mx-4 overflow-x-auto px-4 pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:mx-0 sm:overflow-visible sm:px-0 [&::-webkit-scrollbar]:hidden">
          <div className="grid auto-cols-[76%] grid-flow-col gap-4 sm:auto-cols-auto sm:grid-flow-row sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
            {products.slice(0, section.maxFeaturedProducts).map((product) => (
              <a className="group block h-full" href={product.href} key={`${product.model}-${product.href}`}>
                <article className="h-full rounded-[24px] border border-line bg-white p-2 shadow-card transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-soft sm:rounded-2xl sm:p-1">
                  <div className="px-2 pt-2 sm:px-4 sm:pt-4">
                    <div className="relative h-32 rounded-[18px] bg-[#f7fbff] sm:h-36 sm:bg-transparent">
                      <Image className="object-contain p-2 transition duration-300 group-hover:scale-[1.04] sm:p-0" src={product.image} alt={product.name} fill sizes="(min-width: 1024px) 20vw, 76vw" />
                    </div>
                  </div>
                  <div className="px-3 pb-2 pt-3 sm:px-4">
                    {section.showFeaturedProductModel ? <p className="text-[11px] font-bold text-slate-400 sm:text-xs">{product.model}</p> : null}
                    <h3 className="min-h-10 text-sm font-black leading-5 text-ink">{product.name}</h3>
                  </div>
                  {section.showFeaturedProductSpecs ? (
                    <div className="grid grid-cols-1 gap-2 px-3 pb-4 text-[11px] font-semibold text-slate-500 sm:px-4 sm:text-xs">
                      <p className="flex items-center gap-1.5"><Weight className="h-3.5 w-3.5 text-gold sm:h-4 sm:w-4" /> {section.featuredCapacityLabel}: {product.capacity}</p>
                      <p className="flex items-center gap-1.5"><Ruler className="h-3.5 w-3.5 text-gold sm:h-4 sm:w-4" /> {section.featuredTypeLabel}: {product.type}</p>
                    </div>
                  ) : null}
                </article>
              </a>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

function CategoryScrollButton({ direction, disabled, label, onClick }: { direction: "prev" | "next"; disabled: boolean; label: string; onClick: () => void }) {
  const Icon = direction === "prev" ? ChevronLeft : ChevronRight;
  const sideClass = direction === "prev" ? "-left-4" : "-right-4";

  return (
    <button
      aria-label={label}
      className={`group absolute ${sideClass} top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-2xl border border-blue-100 bg-white/95 text-[#08234a] shadow-[0_14px_34px_rgba(8,35,74,0.14)] backdrop-blur transition duration-300 hover:scale-105 hover:border-blue-300 hover:text-blue-700 hover:shadow-[0_18px_42px_rgba(37,99,235,0.16)] disabled:pointer-events-none disabled:opacity-30 lg:grid`}
      disabled={disabled}
      onClick={onClick}
      type="button"
    >
      <Icon className="h-5 w-5 drop-shadow-sm" strokeWidth={3} />
    </button>
  );
}

function ProductTile({ item, active, onSelect }: { item: ProductCategory; active: boolean; onSelect: () => void }) {
  return (
    <button
      aria-pressed={active}
      className={`group relative h-[146px] overflow-hidden rounded-[18px] border bg-white text-ink shadow-[0_10px_28px_rgba(8,35,74,0.045)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_18px_44px_rgba(8,35,74,0.08)] sm:h-[208px] xl:h-[224px] ${active ? "border-blue-500 bg-blue-50/55 shadow-[0_18px_48px_rgba(37,99,235,0.14)]" : "border-line"}`}
      onClick={onSelect}
      type="button"
    >
      <span className={`absolute inset-x-8 bottom-0 h-[3px] rounded-t-full transition ${active ? "bg-blue-600" : "bg-transparent group-hover:bg-blue-200"}`} />
      <span className="block px-3 pt-3 sm:px-4 sm:pt-4">
        <span className="relative block h-14 rounded-[16px] bg-[radial-gradient(circle_at_50%_52%,rgba(248,251,255,0.95),rgba(255,255,255,0)_70%)] sm:h-28">
          <Image className="object-contain p-1.5 transition duration-300 group-hover:scale-[1.045]" src={item.image} alt={item.name} fill sizes="270px" />
        </span>
      </span>
      <span className="block px-3 pb-4 pt-2 text-center sm:px-4 sm:pb-5">
        <span className="line-clamp-3 text-sm font-black leading-5 tracking-[-0.02em] text-ink sm:line-clamp-2 sm:text-base sm:leading-6">{item.name}</span>
      </span>
    </button>
  );
}

function ProductGridView({ category, onSelect, section }: { category: ProductCategory; onSelect: (product: CatalogProduct) => void; section: HomeProductSectionContent }) {
  return (
    <div className="product-flip-panel">
      <div className="mb-6 flex flex-col gap-3 md:mb-8 md:gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p className="mb-3 flex items-center gap-2 text-[11px] font-black uppercase leading-5 tracking-[0.16em] text-blue-600 sm:mb-4 sm:gap-3 sm:text-sm sm:tracking-[0.24em]">
            <span className="h-2 w-7 shrink-0 skew-x-[-24deg] bg-blue-600 sm:h-2.5 sm:w-9" />
            {category.name}
          </p>
          <h3 className="text-[clamp(34px,9vw,42px)] font-black leading-none tracking-[-0.06em] text-ink sm:text-[56px] lg:text-[68px]">{section.findModelTitle}</h3>
        </div>
      </div>
      <div className="grid grid-cols-2 gap-3 sm:gap-6 xl:grid-cols-4 xl:gap-7">
        {category.products.map((product, productIndex) => (
          <button className="group flex min-h-[260px] flex-col overflow-hidden rounded-[18px] border border-line bg-white text-left shadow-[0_12px_32px_rgba(8,35,74,0.055)] transition duration-300 hover:-translate-y-1.5 hover:border-blue-300 hover:shadow-[0_26px_70px_rgba(37,99,235,0.13)] sm:min-h-[410px] sm:rounded-[22px]" key={`${product.model}-${product.href}-${productIndex}`} onClick={() => onSelect(product)} type="button">
            <span className="relative block h-[136px] w-full overflow-hidden bg-[radial-gradient(circle_at_48%_34%,#ffffff_0%,#f8fafc_34%,#eef2f7_64%,#e2e8f0_100%)] sm:h-auto sm:aspect-[1.3/1]">
              <span className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_82%,rgba(15,23,42,0.12),transparent_44%)]" />
              <span className="pointer-events-none absolute bottom-4 left-[16%] right-[16%] h-8 rounded-full bg-slate-400/18 blur-xl sm:bottom-6 sm:h-12 sm:blur-2xl" />
              <Image className="z-10 object-contain p-3 drop-shadow-[0_24px_28px_rgba(11,27,51,0.14)] transition duration-300 group-hover:scale-[1.035] sm:p-5" src={product.image} alt={product.name} fill sizes="(min-width: 1280px) 300px, (min-width: 640px) 42vw, 46vw" />
            </span>
            <span className="mt-3 block px-3 text-[11px] font-black uppercase tracking-[0.16em] text-slate-400 sm:mt-5 sm:px-6 sm:text-xs sm:tracking-[0.18em]">{product.model}</span>
            <span className="mt-1 block min-h-14 px-3 text-base font-black leading-5 tracking-[-0.025em] text-ink sm:px-6 sm:text-xl sm:leading-7">{product.name.replace(product.model, "")}</span>
            <span className="mx-3 mb-3 mt-auto flex flex-col gap-2 border-t border-line pt-3 text-xs font-bold text-slate-500 sm:mx-6 sm:mb-6 sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:pt-4 sm:text-sm">
              {section.showProductSpecs ? (
                <span className="flex min-w-0 items-center gap-1.5">
                  <span>{product.capacity}</span>
                  <span className="h-3 w-px bg-slate-200" />
                  <span className="min-w-0 flex-1 truncate">{product.type}</span>
                </span>
              ) : null}
              <span className="inline-flex items-center gap-1.5 whitespace-nowrap font-black text-blue-600">
                <span className="sm:hidden">{section.detailsShortLabel}</span><span className="hidden sm:inline">{section.viewDetailsLabel}</span> <ArrowRight className="h-3.5 w-3.5 transition duration-300 group-hover:translate-x-1" />
              </span>
            </span>
          </button>
        ))}
      </div>
    </div>
  );
}

function ProductDetailView({ product, onBack, section }: { product: CatalogProduct; onBack: () => void; section: HomeProductSectionContent }) {
  return (
    <div className="product-flip-panel grid grid-cols-1 gap-8 xl:grid-cols-[0.82fr_1.18fr] xl:gap-10">
      <div className="relative z-10 flex flex-col justify-center xl:pl-2 xl:pr-6">
        <button className="mb-5 inline-flex w-fit items-center gap-2 text-sm font-black text-blue-600 transition hover:text-ink" onClick={onBack} type="button">
          <ChevronLeft className="h-4 w-4" /> {section.backToModelsLabel}
        </button>
        <div className="max-w-[540px] rounded-[28px] border border-line/80 bg-white/86 p-6 shadow-[0_24px_70px_rgba(8,35,74,0.08)] sm:p-8">
          <p className="text-xs font-black uppercase tracking-[0.26em] text-gold">{section.productNameLabel}</p>
          <h3 className="mt-3 text-[34px] font-black uppercase leading-none tracking-[-0.055em] text-ink sm:text-[48px] xl:text-[60px]">
            {product.name.replace(product.model, "")}
          </h3>
          <div className="mt-5 inline-flex items-center gap-3 rounded-full border border-blue-100 bg-blue-50 px-4 py-2 text-sm font-black text-blue-700">
            {section.modelLabel}
            <span className="h-1 w-1 rounded-full bg-blue-400" />
            {product.model}
          </div>
          <p className="mt-6 text-base leading-7 text-slate-600 sm:text-lg sm:leading-8">{product.intro}</p>
          {section.showProductSpecs ? (
            <div className="mt-7 grid grid-cols-2 gap-3">
              {specsFor(product, section).map((item) => (
                <div className="rounded-2xl border border-line bg-[#f7faff] px-4 py-4" key={item.label}>
                  <p className="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">{item.label}</p>
                  <p className="mt-2 text-base font-black text-ink">{item.value}</p>
                </div>
              ))}
            </div>
          ) : null}
          <a className="mt-7 inline-flex h-12 items-center justify-center gap-3 rounded-full bg-navy px-7 text-base font-black text-white shadow-[0_18px_42px_rgba(8,35,74,0.18)] transition hover:-translate-y-0.5 hover:bg-[#0b2d5b]" href={product.detailPath ?? product.href}>
            {section.viewDetailsLabel} <ArrowRight className="h-4 w-4" />
          </a>
        </div>
      </div>
      <div className="relative min-h-[440px] overflow-hidden rounded-[26px] bg-[linear-gradient(180deg,#edf4ff_0%,#f6faff_54%,#e8f1ff_100%)] perspective-[1200px] sm:min-h-[560px] xl:min-h-[660px] xl:rounded-[34px]">
        {section.showProductVisualWatermark ? (
          <span className="pointer-events-none absolute inset-x-2 top-6 select-none bg-[linear-gradient(180deg,rgba(8,35,74,0.18),rgba(37,99,235,0.07))] bg-clip-text text-center font-sans text-[clamp(4.2rem,9.8vw,11rem)] font-black uppercase leading-none tracking-[-0.09em] text-transparent [text-shadow:0_20px_42px_rgba(8,35,74,0.1)] sm:top-8 xl:top-10">
            {section.visualWatermarkLabel}
          </span>
        ) : null}
        <div className="absolute inset-x-[14%] top-14 h-20 rounded-[50%] bg-white/78 blur-3xl" />
        <div className="absolute inset-x-8 bottom-6 h-36 rounded-[50%] bg-blue-500/10 blur-3xl" />
        <div className="absolute inset-x-[12%] bottom-[42px] h-[120px] rounded-[32px] bg-[linear-gradient(180deg,#f9fcff_0%,#edf4ff_42%,#d8e2f2_100%)] shadow-[0_36px_88px_rgba(37,99,235,0.18)] sm:bottom-[52px] sm:h-[156px] xl:bottom-[58px] xl:h-[178px] xl:rounded-[44px]" />
        <div className="absolute inset-x-[14%] bottom-[62px] h-[64px] rounded-[24px] border border-white/85 bg-[linear-gradient(180deg,#ffffff_0%,#f1f6ff_100%)] shadow-[inset_0_-20px_30px_rgba(37,99,235,0.08),0_18px_40px_rgba(8,35,74,0.08)] sm:bottom-[78px] sm:h-[78px] xl:bottom-[82px] xl:h-[84px] xl:rounded-[32px]" />
        <Image priority className="product-hero-3d absolute bottom-[54px] left-1/2 z-10 h-[340px] w-auto max-w-none object-contain drop-shadow-[0_34px_32px_rgba(8,35,74,0.18)] sm:bottom-[58px] sm:h-[450px] xl:bottom-[62px] xl:h-[540px]" src={product.image} alt={product.name} width={701} height={530} />
      </div>
    </div>
  );
}
