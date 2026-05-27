import Image from "next/image";
import { ArrowRight, MapPin } from "lucide-react";

import { PageHero } from "@/components/site/PageHero";
import { SiteChrome } from "@/components/site/SiteChrome";
import { caseStudies } from "@/lib/site-content";

export const metadata = {
  title: "Case Center | BOB-LIFT",
  description: "BOB-LIFT customer cases for marine operations, material handling and aerial work platform applications.",
};

export default function CasesPage() {
  return (
    <SiteChrome>
      <PageHero label="Case Center" title="Project stories with real working conditions." description="Browse customer cases across marine operations, material handling and aerial work applications, then discuss a similar lifting solution with BOB-LIFT." image="/images/marine-scene.jpg">
        <a className="btn-primary rounded-full" href="/quote">Plan My Project <ArrowRight className="h-4 w-4" /></a>
        <a className="btn-white rounded-full" href="#case-list">View Cases</a>
      </PageHero>

      <section id="case-list" className="page-shell py-10 md:py-16">
        <div className="mb-8 max-w-[760px]">
          <p className="section-label mb-3">Field Evidence</p>
          <h2 className="text-[34px] font-black leading-none tracking-[-0.05em] text-ink md:text-[58px]">From lifting challenge to equipment match.</h2>
        </div>

        <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
          {caseStudies.map((item, index) => (
            <a className={`group overflow-hidden rounded-[30px] border border-line bg-white p-3 shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-soft ${index === 0 ? "lg:col-span-2" : ""}`} href={`/cases/${item.slug}`} key={item.slug}>
              <div className={`relative overflow-hidden rounded-[24px] ${index === 0 ? "h-[300px] md:h-[460px]" : "h-[260px]"}`}>
                <Image className="object-cover transition duration-500 group-hover:scale-[1.035]" src={item.image} alt={item.title} fill sizes={index === 0 ? "(min-width: 1024px) 58vw, 100vw" : "(min-width: 1024px) 28vw, 100vw"} />
                <span className="absolute left-4 top-4 rounded-full bg-white/92 px-4 py-2 text-xs font-black text-ink shadow-card backdrop-blur">{item.type.split("·").pop()?.trim()}</span>
              </div>
              <div className="p-3 md:p-4">
                <div className="mb-3 flex items-center gap-2 text-xs font-black text-gold"><MapPin className="h-4 w-4" />Customer Case</div>
                <h3 className="text-2xl font-black leading-tight tracking-[-0.04em] text-ink md:text-3xl">{item.title}</h3>
                <p className="mt-3 max-w-[720px] text-sm font-semibold leading-6 text-slate-500 md:text-base md:leading-7">{item.summary}</p>
                <span className="mt-5 inline-flex items-center gap-2 text-sm font-black text-blue-600">View Case <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" /></span>
              </div>
            </a>
          ))}
        </div>
      </section>
    </SiteChrome>
  );
}
