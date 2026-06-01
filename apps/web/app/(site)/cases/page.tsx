import Image from "next/image";
import { ArrowRight, MapPin } from "lucide-react";

import { PageHero } from "@/components/site/PageHero";
import { SiteChrome } from "@/components/site/SiteChrome";
import { casePublicPath, getCaseIndexContent } from "@/lib/cms-pages";
import { getPageUiContent } from "@/lib/cms-content";
import { localizedPath } from "@/lib/i18n";
import { localizedSeoMetadata } from "@/lib/seo-metadata";
import { getRequestLocale } from "@/lib/server-locale";

export const dynamic = "force-dynamic";

export async function generateMetadata() {
  const ui = (await getPageUiContent()).casesPage;
  return localizedSeoMetadata({ title: ui.metaTitle, description: ui.metaDescription, canonicalPath: ui.canonicalPath });
}

export default async function CasesPage() {
  const [caseStudies, pageUi, locale] = await Promise.all([getCaseIndexContent(), getPageUiContent(), getRequestLocale()]);
  const ui = pageUi.casesPage;

  return (
    <SiteChrome>
      <PageHero label={ui.heroLabel} title={ui.heroTitle} description={ui.heroDescription} image={ui.heroImage}>
        <a className="btn-primary rounded-full" href={localizedPath("/quote", locale)}>{ui.primaryCtaLabel} <ArrowRight className="h-4 w-4" /></a>
        <a className="btn-white rounded-full" href="#case-list">{ui.secondaryCtaLabel}</a>
      </PageHero>

      <section id="case-list" className="page-shell py-10 md:py-16">
        <div className="mb-8 max-w-[760px]">
          <p className="section-label mb-3">{ui.listLabel}</p>
          <h2 className="text-[34px] font-black leading-none tracking-[-0.05em] text-ink md:text-[58px]">{ui.listTitle}</h2>
        </div>

        <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
          {caseStudies.map((item, index) => (
            <a className={`group overflow-hidden rounded-[30px] border border-line bg-white p-3 shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-soft ${index === 0 ? "lg:col-span-2" : ""}`} href={localizedPath(casePublicPath(item), locale)} key={item.slug}>
              <div className={`relative overflow-hidden rounded-[24px] ${index === 0 ? "h-[300px] md:h-[460px]" : "h-[260px]"}`}>
                <Image className="object-cover transition duration-500 group-hover:scale-[1.035]" src={item.image} alt={item.title} fill sizes={index === 0 ? "(min-width: 1024px) 58vw, 100vw" : "(min-width: 1024px) 28vw, 100vw"} />
                <span className="absolute left-4 top-4 rounded-full bg-white/92 px-4 py-2 text-xs font-black text-ink shadow-card backdrop-blur">{item.type.split("·").pop()?.trim()}</span>
              </div>
              <div className="p-3 md:p-4">
                <div className="mb-3 flex items-center gap-2 text-xs font-black text-gold"><MapPin className="h-4 w-4" />{ui.cardTypeLabel}</div>
                <h3 className="text-2xl font-black leading-tight tracking-[-0.04em] text-ink md:text-3xl">{item.title}</h3>
                <p className="mt-3 max-w-[720px] text-sm font-semibold leading-6 text-slate-500 md:text-base md:leading-7">{item.summary}</p>
                <span className="mt-5 inline-flex items-center gap-2 text-sm font-black text-blue-600">{ui.viewCaseLabel} <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" /></span>
              </div>
            </a>
          ))}
        </div>
      </section>
    </SiteChrome>
  );
}
