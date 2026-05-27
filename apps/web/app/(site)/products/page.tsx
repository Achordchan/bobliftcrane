import Image from "next/image";
import { ArrowRight, Filter, Ruler, Weight } from "lucide-react";

import { PageHero } from "@/components/site/PageHero";
import { SiteChrome } from "@/components/site/SiteChrome";
import { categorySlug, productCatalog, productDetailPath } from "@/lib/product-catalog";

export const metadata = {
  title: "Products | BOB-LIFT",
  description: "Browse BOB-LIFT truck mounted cranes, marine cranes, pickup cranes, forklift fly jib cranes and mini dumpers.",
};

export default function ProductsPage() {
  const totalModels = productCatalog.reduce((sum, category) => sum + category.products.length, 0);

  return (
    <SiteChrome>
      <PageHero
        label="Product Center"
        title="Real BOB-LIFT models, organized for fast selection."
        description={`Explore ${productCatalog.length} product families and ${totalModels} model pages from the BOB-LIFT catalog. Compare capacity, boom type and working scenario before requesting a quotation.`}
        image="/images/telescopic-banner.jpg"
      >
        <a className="btn-primary rounded-full" href="/quote">Request Quote <ArrowRight className="h-4 w-4" /></a>
        <a className="btn-white rounded-full" href="#product-families">Browse Families</a>
      </PageHero>

      <section id="product-families" className="page-shell py-10 md:py-16">
        <div className="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p className="section-label mb-3">Catalog Navigation</p>
            <h2 className="text-[34px] font-black leading-none tracking-[-0.05em] text-ink md:text-[56px]">Choose by working condition.</h2>
          </div>
          <div className="flex items-center gap-2 rounded-full border border-line bg-white px-4 py-3 text-sm font-black text-slate-500 shadow-card">
            <Filter className="h-4 w-4 text-gold" /> {totalModels} models available
          </div>
        </div>

        <div className="-mx-4 overflow-x-auto px-4 pb-4 [-ms-overflow-style:none] [scrollbar-width:none] md:mx-0 md:px-0 [&::-webkit-scrollbar]:hidden">
          <div className="flex min-w-max gap-3 md:min-w-0 md:flex-wrap">
            {productCatalog.map((category) => (
              <a className="rounded-full border border-line bg-white px-5 py-3 text-xs font-black text-ink shadow-[0_10px_28px_rgba(8,35,74,0.055)] transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700" href={`#${categorySlug(category)}`} key={category.name}>
                {category.name}
              </a>
            ))}
          </div>
        </div>

        <div className="mt-6 space-y-10 md:mt-10 md:space-y-14">
          {productCatalog.map((category) => (
            <section className="scroll-mt-28" id={categorySlug(category)} key={category.name}>
              <div className="mb-5 grid grid-cols-1 gap-4 rounded-[28px] border border-line bg-[linear-gradient(135deg,#ffffff_0%,#f7fbff_100%)] p-4 shadow-[0_24px_70px_rgba(8,35,74,0.06)] md:grid-cols-[220px_1fr] md:items-center md:p-5">
                <div className="relative h-36 overflow-hidden rounded-[22px] bg-white md:h-40">
                  <Image className="object-contain p-4" src={category.image} alt={category.name} fill sizes="220px" />
                </div>
                <div>
                  <p className="text-xs font-black uppercase tracking-[0.18em] text-blue-600">{category.products.length} Models</p>
                  <h3 className="mt-2 text-2xl font-black tracking-[-0.04em] text-ink md:text-4xl">{category.name}</h3>
                  <p className="mt-3 max-w-[760px] text-sm font-semibold leading-6 text-slate-500 md:text-base md:leading-7">Select a model to view capacity, product type, matched scenarios and quotation entry.</p>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 xl:grid-cols-4">
                {category.products.map((product) => (
                  <a className="group flex min-h-[250px] flex-col rounded-[22px] border border-line bg-white p-3 shadow-card transition duration-300 hover:-translate-y-1.5 hover:border-blue-300 hover:shadow-soft md:min-h-[342px] md:p-5" href={productDetailPath(product)} key={product.model}>
                    <span className="relative block h-32 rounded-[18px] bg-[#f8fbff] md:h-44">
                      <Image className="object-contain p-3 transition duration-300 group-hover:scale-[1.035]" src={product.image} alt={product.name} fill sizes="(min-width: 1280px) 22vw, (min-width: 768px) 30vw, 46vw" />
                    </span>
                    <span className="mt-4 text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{product.model}</span>
                    <span className="mt-1 line-clamp-2 text-sm font-black leading-5 tracking-[-0.03em] text-ink md:text-lg md:leading-6">{product.name.replace(product.model, "").trim()}</span>
                    <span className="mt-auto grid gap-2 border-t border-line pt-3 text-[11px] font-bold text-slate-500 md:text-xs">
                      <span className="flex items-center gap-2"><Weight className="h-3.5 w-3.5 text-gold" />{product.capacity}</span>
                      <span className="flex items-center gap-2"><Ruler className="h-3.5 w-3.5 text-gold" />{product.type}</span>
                      <span className="inline-flex items-center gap-1 pt-1 font-black text-blue-600">View Details <ArrowRight className="h-3.5 w-3.5 transition group-hover:translate-x-1" /></span>
                    </span>
                  </a>
                ))}
              </div>
            </section>
          ))}
        </div>
      </section>
    </SiteChrome>
  );
}
