"use client";

import Image from "next/image";
import { useCallback, useEffect, useRef, useState } from "react";
import { ArrowRight, ChevronLeft, ChevronRight, Ruler, Weight } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { featuredProducts } from "@/lib/home-data";

import { type CatalogProduct, type ProductCategory, productCatalog, productDetailPath } from "@/lib/product-catalog";

function specsFor(product: CatalogProduct) {
  return [
    { label: "Model", value: product.model },
    { label: "Capacity", value: product.capacity },
    { label: "Type", value: product.type },
    { label: "System", value: "Hydraulic" },
  ];
}

export function ProductFamilies() {
  const [categoryIndex, setCategoryIndex] = useState(0);
  const [selectedProduct, setSelectedProduct] = useState<CatalogProduct | null>(null);
  const [categoryScroll, setCategoryScroll] = useState({ next: false, prev: false });
  const categoryScrollerRef = useRef<HTMLDivElement>(null);
  const selectedCategory = productCatalog[categoryIndex];

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

  const stageClassName = selectedProduct
    ? "product-stage relative z-10 mt-8 overflow-hidden rounded-[28px] bg-white/80 p-5 shadow-[0_32px_90px_rgba(8,35,74,0.08)] md:mt-10 md:p-8 xl:rounded-[36px] xl:p-10"
    : "relative z-10 mt-8 md:mt-10";

  return (
    <section id="products" className="relative overflow-hidden bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_48%,#ffffff_100%)] py-8 md:py-14 lg:py-20">
      <div className="page-shell max-w-[1440px]">
        <div className="relative">
          <div className="mb-6 flex flex-col gap-3 md:mb-8 md:gap-4 lg:mb-10 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <p className="section-label mb-3">Main Products</p>
              <h2 className="max-w-none whitespace-nowrap text-[clamp(26px,6.5vw,32px)] font-black leading-[0.96] tracking-[-0.055em] text-ink sm:text-[48px] lg:text-[62px]">
                BOB-LIFT Crane Solutions
              </h2>
            </div>
          </div>

          <div className="relative z-10">
            <CategoryScrollButton direction="prev" disabled={!categoryScroll.prev} onClick={() => scrollCategories("prev")} />
            <div ref={categoryScrollerRef} className="-mx-4 overflow-x-auto scroll-smooth px-4 py-2 [-ms-overflow-style:none] [scrollbar-width:none] sm:mx-0 sm:px-0.5 [&::-webkit-scrollbar]:hidden">
              <div className="grid auto-cols-[calc((100vw-56px)/2)] grid-flow-col gap-3 sm:auto-cols-[210px] md:gap-4 lg:auto-cols-[220px] xl:auto-cols-[230px]">
                {productCatalog.map((item, index) => <ProductTile active={categoryIndex === index} item={item} key={item.name} onSelect={() => selectCategory(index)} />)}
              </div>
            </div>
            <CategoryScrollButton direction="next" disabled={!categoryScroll.next} onClick={() => scrollCategories("next")} />
          </div>

          <div className={stageClassName}>
            {selectedProduct ? (
              <ProductDetailView key={selectedProduct.model} onBack={() => setSelectedProduct(null)} product={selectedProduct} />
            ) : (
              <ProductGridView category={selectedCategory} key={selectedCategory.name} onSelect={setSelectedProduct} />
            )}
          </div>
        </div>
      </div>
    </section>
  );
}

function CategoryScrollButton({ direction, disabled, onClick }: { direction: "prev" | "next"; disabled: boolean; onClick: () => void }) {
  const Icon = direction === "prev" ? ChevronLeft : ChevronRight;
  const sideClass = direction === "prev" ? "-left-4" : "-right-4";
  const label = direction === "prev" ? "Previous product categories" : "Next product categories";

  return (
    <button
      aria-label={label}
      className={`group absolute ${sideClass} top-1/2 z-20 hidden h-11 w-11 -translate-y-1/2 place-items-center rounded-2xl border border-line bg-white/92 text-slate-500 shadow-[0_16px_36px_rgba(8,35,74,0.12)] backdrop-blur transition duration-300 hover:-translate-y-[54%] hover:border-blue-200 hover:text-blue-600 hover:shadow-[0_20px_46px_rgba(37,99,235,0.16)] disabled:pointer-events-none disabled:opacity-30 lg:grid`}
      disabled={disabled}
      onClick={onClick}
      type="button"
    >
      <Icon className="h-5 w-5" strokeWidth={2.1} />
    </button>
  );
}

function ProductTile({ item, active, onSelect }: { item: ProductCategory; active: boolean; onSelect: () => void }) {
  return (
    <button
      aria-pressed={active}
      className={`group relative h-[136px] overflow-hidden rounded-[18px] border bg-white text-ink shadow-[0_10px_28px_rgba(8,35,74,0.045)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_18px_44px_rgba(8,35,74,0.08)] sm:h-[188px] ${active ? "border-blue-500 bg-blue-50/55 shadow-[0_18px_48px_rgba(37,99,235,0.14)]" : "border-line"}`}
      onClick={onSelect}
      type="button"
    >
      <span className={`absolute inset-x-8 bottom-0 h-[3px] rounded-t-full transition ${active ? "bg-blue-600" : "bg-transparent group-hover:bg-blue-200"}`} />
      <span className="block px-3 pt-3 sm:px-4 sm:pt-4">
        <span className="relative block h-14 rounded-[16px] bg-[radial-gradient(circle_at_50%_52%,rgba(248,251,255,0.95),rgba(255,255,255,0)_70%)] sm:h-28">
          <Image className="object-contain p-1.5 transition duration-300 group-hover:scale-[1.045]" src={item.image} alt={item.name} fill sizes="220px" />
        </span>
      </span>
      <span className="block px-3 pb-4 pt-2 text-center sm:px-4 sm:pb-5">
        <span className="line-clamp-3 text-[12px] font-black leading-4 tracking-[-0.02em] text-ink sm:line-clamp-2 sm:text-[15px] sm:leading-5">{item.name}</span>
      </span>
    </button>
  );
}

function ProductGridView({ category, onSelect }: { category: ProductCategory; onSelect: (product: CatalogProduct) => void }) {
  return (
    <div className="product-flip-panel">
      <div className="mb-6 flex flex-col gap-3 md:mb-8 md:gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p className="mb-3 flex items-center gap-2 text-[11px] font-black uppercase leading-5 tracking-[0.16em] text-blue-600 sm:mb-4 sm:gap-3 sm:text-sm sm:tracking-[0.24em]">
            <span className="h-2 w-7 shrink-0 skew-x-[-24deg] bg-blue-600 sm:h-2.5 sm:w-9" />
            {category.name}
          </p>
          <h3 className="text-[clamp(34px,9vw,42px)] font-black leading-none tracking-[-0.06em] text-ink sm:text-[56px] lg:text-[68px]">Find Your Model</h3>
        </div>
      </div>
      <div className="grid grid-cols-2 gap-3 sm:gap-6 xl:grid-cols-4 xl:gap-7">
        {category.products.map((product) => (
          <button className="group flex min-h-[242px] flex-col rounded-[18px] border border-line bg-white p-3 text-left shadow-[0_12px_32px_rgba(8,35,74,0.055)] transition duration-300 hover:-translate-y-1.5 hover:border-blue-300 hover:shadow-[0_26px_70px_rgba(37,99,235,0.13)] sm:min-h-[374px] sm:rounded-[22px] sm:p-5" key={product.model} onClick={() => onSelect(product)} type="button">
            <span className="relative mx-auto block h-[124px] w-full rounded-[16px] bg-[linear-gradient(180deg,#fbfdff_0%,#f5f9ff_100%)] sm:h-auto sm:aspect-[1.18/1] sm:rounded-[18px]">
              <Image className="object-contain p-2 transition duration-300 group-hover:scale-[1.035] sm:p-3" src={product.image} alt={product.name} fill sizes="(min-width: 1280px) 300px, (min-width: 640px) 42vw, 46vw" />
            </span>
            <span className="mt-3 block text-[9px] font-black uppercase tracking-[0.16em] text-slate-400 sm:mt-5 sm:text-[11px] sm:tracking-[0.18em]">{product.model}</span>
            <span className="mt-1 block min-h-12 text-[13px] font-black leading-4 tracking-[-0.025em] text-ink sm:text-lg sm:leading-6">{product.name.replace(product.model, "")}</span>
            <span className="mt-auto flex flex-col gap-2 border-t border-line pt-3 text-[10px] font-bold text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:pt-4 sm:text-xs">
              <span className="flex min-w-0 items-center gap-1.5">
              <span>{product.capacity}</span>
              <span className="h-3 w-px bg-slate-200" />
                <span className="min-w-0 flex-1 truncate">{product.type}</span>
              </span>
              <span className="inline-flex items-center gap-1.5 whitespace-nowrap font-black text-blue-600">
                <span className="sm:hidden">Details</span><span className="hidden sm:inline">View Details</span> <ArrowRight className="h-3.5 w-3.5 transition duration-300 group-hover:translate-x-1" />
              </span>
            </span>
          </button>
        ))}
      </div>
    </div>
  );
}

function ProductDetailView({ product, onBack }: { product: CatalogProduct; onBack: () => void }) {
  return (
    <div className="product-flip-panel grid grid-cols-1 gap-8 xl:grid-cols-[0.82fr_1.18fr] xl:gap-10">
      <div className="relative z-10 flex flex-col justify-center xl:pl-2 xl:pr-6">
        <button className="mb-5 inline-flex w-fit items-center gap-2 text-sm font-black text-blue-600 transition hover:text-ink" onClick={onBack} type="button">
          <ChevronLeft className="h-4 w-4" /> Back to Models
        </button>
        <div className="max-w-[540px] rounded-[28px] border border-line/80 bg-white/86 p-6 shadow-[0_24px_70px_rgba(8,35,74,0.08)] sm:p-8">
          <p className="text-xs font-black uppercase tracking-[0.26em] text-gold">Product Name</p>
          <h3 className="mt-3 text-[34px] font-black uppercase leading-none tracking-[-0.055em] text-ink sm:text-[48px] xl:text-[60px]">
            {product.name.replace(product.model, "")}
          </h3>
          <div className="mt-5 inline-flex items-center gap-3 rounded-full border border-blue-100 bg-blue-50 px-4 py-2 text-sm font-black text-blue-700">
            Model
            <span className="h-1 w-1 rounded-full bg-blue-400" />
            {product.model}
          </div>
          <p className="mt-6 text-base leading-7 text-slate-600 sm:text-lg sm:leading-8">{product.intro}</p>
          <div className="mt-7 grid grid-cols-2 gap-3">
            {specsFor(product).map((item) => (
              <div className="rounded-2xl border border-line bg-[#f7faff] px-4 py-4" key={item.label}>
                <p className="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">{item.label}</p>
                <p className="mt-2 text-base font-black text-ink">{item.value}</p>
              </div>
            ))}
          </div>
          <a className="mt-7 inline-flex h-12 items-center justify-center gap-3 rounded-full bg-navy px-7 text-sm font-black text-white shadow-[0_18px_42px_rgba(8,35,74,0.18)] transition hover:-translate-y-0.5 hover:bg-[#0b2d5b]" href={productDetailPath(product)}>
            View Details <ArrowRight className="h-4 w-4" />
          </a>
        </div>
      </div>
      <div className="relative min-h-[440px] overflow-hidden rounded-[26px] bg-[linear-gradient(180deg,#edf4ff_0%,#f6faff_54%,#e8f1ff_100%)] perspective-[1200px] sm:min-h-[560px] xl:min-h-[660px] xl:rounded-[34px]">
        <span className="pointer-events-none absolute inset-x-2 top-6 select-none bg-[linear-gradient(180deg,rgba(8,35,74,0.18),rgba(37,99,235,0.07))] bg-clip-text text-center font-sans text-[clamp(4.2rem,9.8vw,11rem)] font-black uppercase leading-none tracking-[-0.09em] text-transparent [text-shadow:0_20px_42px_rgba(8,35,74,0.1)] sm:top-8 xl:top-10">
          BOB-LIFT
        </span>
        <div className="absolute inset-x-[14%] top-14 h-20 rounded-[50%] bg-white/78 blur-3xl" />
        <div className="absolute inset-x-8 bottom-6 h-36 rounded-[50%] bg-blue-500/10 blur-3xl" />
        <div className="absolute inset-x-[12%] bottom-[42px] h-[120px] rounded-[32px] bg-[linear-gradient(180deg,#f9fcff_0%,#edf4ff_42%,#d8e2f2_100%)] shadow-[0_36px_88px_rgba(37,99,235,0.18)] sm:bottom-[52px] sm:h-[156px] xl:bottom-[58px] xl:h-[178px] xl:rounded-[44px]" />
        <div className="absolute inset-x-[14%] bottom-[62px] h-[64px] rounded-[24px] border border-white/85 bg-[linear-gradient(180deg,#ffffff_0%,#f1f6ff_100%)] shadow-[inset_0_-20px_30px_rgba(37,99,235,0.08),0_18px_40px_rgba(8,35,74,0.08)] sm:bottom-[78px] sm:h-[78px] xl:bottom-[82px] xl:h-[84px] xl:rounded-[32px]" />
        <Image priority className="product-hero-3d absolute bottom-[54px] left-1/2 z-10 h-[340px] w-auto max-w-none object-contain drop-shadow-[0_34px_32px_rgba(8,35,74,0.18)] sm:bottom-[58px] sm:h-[450px] xl:bottom-[62px] xl:h-[540px]" src={product.image} alt={product.name} width={701} height={530} />
      </div>
    </div>
  );
}

export function FeaturedProducts() {
  return (
    <section className="bg-gradient-to-r from-white via-[#f8fafc] to-white py-8 md:py-14">
      <div className="page-shell">
        <div className="mb-5 flex flex-col gap-3 sm:mb-8 lg:grid lg:grid-cols-[260px_1fr] lg:gap-10">
          <div>
            <p className="section-label mb-3 md:mb-4">Featured Products</p>
            <h2 className="max-w-none text-[clamp(27px,6.9vw,34px)] font-black leading-[1.02] tracking-[-0.055em] text-ink sm:text-3xl sm:leading-tight sm:tracking-[-0.03em]">
              High Performance Cranes. Built for Extreme Demands.
            </h2>
          </div>
          <a className="inline-flex w-fit items-center gap-2 text-sm font-black text-ink lg:mt-9 lg:self-start" href="/products">
            View All Products <ArrowRight className="h-4 w-4" />
          </a>
        </div>

        <div className="-mx-4 overflow-x-auto px-4 pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:mx-0 sm:overflow-visible sm:px-0 [&::-webkit-scrollbar]:hidden">
          <div className="grid auto-cols-[76%] grid-flow-col gap-4 sm:auto-cols-auto sm:grid-flow-row sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
            {featuredProducts.map((item) => (
              <a className="block" href={item.href} key={item.name}>
                <Card className="card-hover h-full rounded-[24px] border-line bg-white p-2 shadow-card ring-0 sm:rounded-2xl sm:p-1">
                  <CardContent className="px-2 pt-2 sm:px-4 sm:pt-4">
                    <div className="relative h-32 rounded-[18px] bg-[#f7fbff] sm:h-36 sm:bg-transparent">
                      <Image className="object-contain p-2 sm:p-0" src={item.image} alt={item.name} fill sizes="(min-width: 1024px) 20vw, 76vw" />
                    </div>
                  </CardContent>
                  <CardHeader className="px-3 pb-2 pt-3 sm:px-4">
                    <p className="text-[11px] font-bold text-slate-400 sm:text-xs">{item.reach}</p>
                    <CardTitle className="min-h-10 text-sm font-black leading-5 text-ink">{item.name}</CardTitle>
                  </CardHeader>
                  <CardContent className="grid grid-cols-2 gap-2 px-3 pb-4 text-[11px] font-semibold text-slate-500 sm:flex sm:flex-col sm:px-4 sm:text-xs">
                    <p className="flex items-center gap-1.5"><Weight className="h-3.5 w-3.5 text-gold sm:h-4 sm:w-4" /> {item.cap}</p>
                    <p className="flex items-center gap-1.5"><Ruler className="h-3.5 w-3.5 text-gold sm:h-4 sm:w-4" /> {item.reach}</p>
                  </CardContent>
                </Card>
              </a>
            ))}
          </div>
        </div>

        <div className="hidden justify-end gap-3 lg:flex">
          <button className="grid h-10 w-10 place-items-center rounded-full bg-white shadow-card" type="button"><ChevronLeft className="h-4 w-4" /></button>
          <button className="grid h-10 w-10 place-items-center rounded-full bg-white shadow-card" type="button"><ChevronRight className="h-4 w-4" /></button>
        </div>
      </div>
    </section>
  );
}
