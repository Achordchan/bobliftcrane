import Image from "next/image";
import { ArrowRight, ChevronLeft } from "lucide-react";

import type { CaseStudy } from "@/lib/cms-pages";
import { localizedPath } from "@/lib/i18n";
import type { PageUiContent } from "@/lib/site-ui";

export function CaseDetailContent({
  item,
  ui,
  locale,
}: {
  item: CaseStudy;
  ui: PageUiContent["caseDetail"];
  locale: string;
}) {
  return (
    <>
      <header className="relative overflow-hidden bg-[linear-gradient(180deg,#f8fbff_0%,#ffffff_100%)] py-8 md:py-14">
        <div className="page-shell">
          <a className="mb-6 inline-flex items-center gap-2 text-sm font-black text-blue-600" href={localizedPath("/cases", locale)}>
            <ChevronLeft className="h-4 w-4" /> {ui.backLabel}
          </a>
          <div className="grid grid-cols-1 gap-7 lg:grid-cols-[0.78fr_1.22fr] lg:items-end">
            <div>
              <p className="section-label mb-4">{item.type}</p>
              <h1 className="text-[40px] font-black leading-[1.02] tracking-[-0.06em] text-ink md:text-[70px]">{item.title}</h1>
              <p className="mt-5 text-base font-semibold leading-8 text-slate-600">{item.summary}</p>
            </div>
            <div className="relative min-h-[280px] overflow-hidden rounded-[32px] border border-line shadow-[0_34px_100px_rgba(8,35,74,0.12)] md:min-h-[520px]">
              <Image className="object-cover" src={item.image} alt={item.title} fill priority sizes="(min-width: 1024px) 56vw, 100vw" />
            </div>
          </div>
        </div>
      </header>

      <section className="page-shell grid grid-cols-1 gap-8 py-10 md:py-16 lg:grid-cols-[320px_1fr] lg:gap-12">
        <aside className="h-fit rounded-[28px] border border-line bg-white p-5 shadow-card lg:sticky lg:top-8">
          <p className="section-label mb-4">{ui.factsTitle}</p>
          <div className="grid gap-3">
            {item.facts.map((fact) => (
              <div className="rounded-2xl bg-[#f8fbff] p-4" key={fact.label}>
                <p className="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">{fact.label}</p>
                <p className="mt-2 text-lg font-black text-ink">{fact.value}</p>
              </div>
            ))}
          </div>
          <a className="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-navy px-5 py-3 text-sm font-black text-white" href={localizedPath("/quote", locale)}>
            {ui.discussSimilarLabel} <ArrowRight className="h-4 w-4" />
          </a>
        </aside>

        <CaseBody item={item} />
      </section>
    </>
  );
}

function CaseBody({ item }: { item: CaseStudy }) {
  if (item.sections.length > 0) {
    return (
      <div className="rounded-[30px] border border-line bg-white p-5 shadow-card md:p-9">
        {item.sections.map((section) => (
          <section className="border-b border-line py-7 first:pt-0 last:border-b-0 last:pb-0" key={section.heading}>
            <h2 className="text-2xl font-black tracking-[-0.035em] text-ink md:text-3xl">{section.heading}</h2>
            <p className="mt-4 text-base font-semibold leading-8 text-slate-600">{section.body}</p>
          </section>
        ))}
      </div>
    );
  }

  if (item.contentHtml) {
    return <div className="cms-richtext rounded-[30px] border border-line bg-white p-5 shadow-card md:p-9" dangerouslySetInnerHTML={{ __html: item.contentHtml }} />;
  }

  return (
    <div className="rounded-[30px] border border-line bg-white p-5 text-base font-semibold leading-8 text-slate-600 shadow-card md:p-9">
      {item.summary}
    </div>
  );
}
