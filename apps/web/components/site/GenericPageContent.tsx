import Image from "next/image";
import { ArrowRight } from "lucide-react";

import type { GenericPageContent as GenericPageContentModel } from "@/lib/cms-pages";

export function GenericPageContent({ page }: { page: GenericPageContentModel }) {
  return (
    <main className="bg-[#F8FAFC] text-ink">
      <section className="relative overflow-hidden border-b border-line bg-[radial-gradient(circle_at_74%_26%,#ffffff_0%,#edf4ff_42%,#f8fafc_78%)]">
        <div className="page-shell grid min-h-[560px] grid-cols-1 items-center gap-10 py-12 lg:grid-cols-[0.9fr_1.1fr]">
          <div className="relative z-10 max-w-[680px]">
            {page.heroLabel ? <p className="section-label mb-5">{page.heroLabel}</p> : null}
            <h1 className="text-[44px] font-black leading-[0.98] tracking-[-0.065em] text-ink md:text-[68px] xl:text-[78px]">
              {page.heroTitle || page.title}
            </h1>
            {page.heroDescription ? <p className="mt-7 max-w-[620px] text-lg font-semibold leading-8 text-slate-600">{page.heroDescription}</p> : null}
          </div>
          <div className="relative min-h-[360px] overflow-hidden rounded-[32px] border border-white/80 bg-white shadow-card lg:min-h-[500px]">
            {page.heroImage ? (
              <Image className="object-cover" src={page.heroImage} alt={page.heroTitle || page.title} fill priority sizes="(min-width: 1024px) 52vw, 92vw" />
            ) : (
              <div className="absolute inset-0 bg-[linear-gradient(135deg,#ffffff_0%,#edf4ff_48%,#dbeafe_100%)]" />
            )}
            <span className="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(8,35,74,0.04),rgba(8,35,74,0.18))]" />
          </div>
        </div>
      </section>

      <section className="page-shell py-10 md:py-16">
        <div className="grid gap-6">
          {page.contentBlocks.map((block, index) => (
            <article className="grid overflow-hidden rounded-[28px] border border-line bg-white shadow-card lg:grid-cols-[0.9fr_1.1fr]" key={`${block.title}-${index}`}>
              <div className="flex flex-col justify-center p-6 md:p-9">
                {block.label ? <p className="section-label mb-4">{block.label}</p> : null}
                <h2 className="text-3xl font-black leading-tight tracking-[-0.045em] text-ink md:text-5xl">{block.title}</h2>
                <p className="mt-5 text-base font-semibold leading-7 text-slate-600 md:text-lg md:leading-8">{block.text}</p>
                {block.ctaLabel && block.ctaHref ? (
                  <a className="mt-7 inline-flex w-fit items-center gap-2 rounded-full bg-navy px-6 py-3 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-[#0B3F7D]" href={block.ctaHref}>
                    {block.ctaLabel} <ArrowRight className="h-4 w-4" />
                  </a>
                ) : null}
              </div>
              <div className="relative min-h-[280px] bg-[#edf4ff] lg:min-h-[420px]">
                {block.image ? (
                  <Image className="object-cover" src={block.image} alt={block.title} fill sizes="(min-width: 1024px) 48vw, 92vw" />
                ) : (
                  <div className="absolute inset-0 bg-[radial-gradient(circle_at_50%_40%,#ffffff_0%,#edf4ff_48%,#dbe5f1_100%)]" />
                )}
              </div>
            </article>
          ))}
        </div>
      </section>
    </main>
  );
}
