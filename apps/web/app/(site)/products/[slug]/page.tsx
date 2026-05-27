import Image from "next/image";
import { notFound } from "next/navigation";
import { ArrowRight, CheckCircle2, ChevronLeft } from "lucide-react";

import { PageHero } from "@/components/site/PageHero";
import { SiteChrome } from "@/components/site/SiteChrome";
import { allProductEntries, getProductEntryBySlug, productDetailPath, productSlug } from "@/lib/product-catalog";

type PageProps = { params: Promise<{ slug: string }> };

export function generateStaticParams() {
  return allProductEntries().map(({ product }) => ({ slug: productSlug(product) }));
}

export async function generateMetadata({ params }: PageProps) {
  const { slug } = await params;
  const entry = getProductEntryBySlug(slug);
  return {
    title: entry ? `${entry.product.name} | BOB-LIFT` : "Product | BOB-LIFT",
    description: entry?.product.intro ?? "BOB-LIFT product detail page.",
  };
}

export default async function ProductDetailPage({ params }: PageProps) {
  const { slug } = await params;
  const entry = getProductEntryBySlug(slug);
  if (!entry) notFound();

  const { product, category } = entry;
  const related = category.products.filter((item) => item.model !== product.model).slice(0, 4);
  const specs = [
    { label: "Model", value: product.model },
    { label: "Capacity", value: product.capacity },
    { label: "Type", value: product.type },
    { label: "Category", value: category.name },
  ];

  return (
    <SiteChrome>
      <PageHero label="Product Detail" title={product.name} description={product.intro} image="/images/global-network-blue.jpg">
        <a className="btn-primary rounded-full" href="/quote">Request Quote <ArrowRight className="h-4 w-4" /></a>
        <a className="btn-white rounded-full" href="/products"><ChevronLeft className="h-4 w-4" /> Back to Products</a>
      </PageHero>

      <section className="page-shell grid grid-cols-1 gap-8 py-10 md:py-16 lg:grid-cols-[1.05fr_0.95fr] lg:gap-12">
        <div className="relative min-h-[360px] overflow-hidden rounded-[32px] border border-line bg-[linear-gradient(180deg,#edf4ff_0%,#f8fbff_60%,#e8f1ff_100%)] shadow-[0_34px_100px_rgba(8,35,74,0.1)] md:min-h-[580px]">
          <span className="pointer-events-none absolute inset-x-4 top-8 select-none bg-[linear-gradient(180deg,rgba(8,35,74,0.16),rgba(37,99,235,0.06))] bg-clip-text text-center text-[clamp(4rem,12vw,11rem)] font-black uppercase leading-none tracking-[-0.1em] text-transparent">BOB-LIFT</span>
          <div className="absolute inset-x-[12%] bottom-8 h-28 rounded-[34px] bg-white/70 blur-sm md:h-40" />
          <Image className="absolute bottom-8 left-1/2 z-10 h-[300px] w-auto max-w-none -translate-x-1/2 object-contain drop-shadow-[0_34px_32px_rgba(8,35,74,0.16)] md:h-[520px]" src={product.image} alt={product.name} width={760} height={560} priority />
        </div>

        <div className="flex flex-col justify-center">
          <p className="section-label mb-4">Specifications</p>
          <h2 className="text-[34px] font-black leading-none tracking-[-0.05em] text-ink md:text-[56px]">Built for reliable lifting work.</h2>
          <p className="mt-5 text-base font-semibold leading-8 text-slate-600">Review the model identity, capacity range and application direction before sending your working-condition details to BOB-LIFT.</p>

          <div className="mt-7 grid grid-cols-2 gap-3">
            {specs.map((item) => (
              <div className="rounded-2xl border border-line bg-[#f8fbff] p-4" key={item.label}>
                <p className="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">{item.label}</p>
                <p className="mt-2 text-base font-black text-ink md:text-lg">{item.value}</p>
              </div>
            ))}
          </div>

          <div className="mt-7 rounded-[26px] border border-line bg-white p-5 shadow-card">
            <h3 className="text-lg font-black text-ink">Before quotation</h3>
            <div className="mt-4 grid gap-3 text-sm font-semibold leading-6 text-slate-600">
              {["Target load and working radius", "Truck chassis or vessel installation space", "Destination country and delivery plan"].map((item) => (
                <p className="flex gap-3" key={item}><CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-gold" /> {item}</p>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className="bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)] py-10 md:py-16">
        <div className="page-shell">
          <div className="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
              <p className="section-label mb-3">Related Models</p>
              <h2 className="text-3xl font-black tracking-[-0.04em] text-ink md:text-5xl">More from {category.name}</h2>
            </div>
            <a className="inline-flex items-center gap-2 text-sm font-black text-ink" href="/products">View all products <ArrowRight className="h-4 w-4" /></a>
          </div>
          <div className="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5">
            {related.map((item) => (
              <a className="card-hover rounded-[22px] border border-line bg-white p-3 shadow-card" href={productDetailPath(item)} key={item.model}>
                <div className="relative h-32 rounded-[18px] bg-[#f8fbff] md:h-40"><Image className="object-contain p-3" src={item.image} alt={item.name} fill sizes="25vw" /></div>
                <p className="mt-3 text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">{item.model}</p>
                <h3 className="mt-1 line-clamp-2 text-sm font-black leading-5 text-ink">{item.name.replace(item.model, "").trim()}</h3>
              </a>
            ))}
          </div>
        </div>
      </section>
    </SiteChrome>
  );
}
