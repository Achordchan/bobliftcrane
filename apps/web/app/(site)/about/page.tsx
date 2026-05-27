import Image from "next/image";
import { ArrowRight } from "lucide-react";

import { PageHero } from "@/components/site/PageHero";
import { SiteChrome } from "@/components/site/SiteChrome";
import { stats, timeline } from "@/lib/home-data";

export const metadata = {
  title: "About BOB-LIFT | BOB-LIFT",
  description: "Learn about BOB-LIFT manufacturing, export capability and engineering experience.",
};

export default function AboutPage() {
  return (
    <SiteChrome>
      <PageHero label="About BOB-LIFT" title="Engineering lifting equipment for global projects since 1989." description="Learn how BOB-LIFT combines manufacturing capability, export experience and service readiness for truck mounted cranes, marine cranes and custom lifting equipment." image="/images/factory-workshop.jpg">
        <a className="btn-primary rounded-full" href="/contact">Contact Team <ArrowRight className="h-4 w-4" /></a>
        <a className="btn-white rounded-full" href="/products">View Products</a>
      </PageHero>

      <section className="page-shell grid grid-cols-1 gap-8 py-10 md:py-16 lg:grid-cols-[0.95fr_1.05fr] lg:gap-14">
        <div className="grid grid-cols-2 gap-3 md:gap-5">
          {["/images/truck-mounted-crane.png", "/images/marine-scene.jpg", "/images/global-network-blue.jpg", "/images/telescopic-banner.jpg"].map((src, index) => (
            <div className={`relative overflow-hidden rounded-[26px] border border-line bg-white shadow-card ${index === 1 ? "row-span-2 min-h-[360px]" : "min-h-[180px]"}`} key={src}>
              <Image className={src.endsWith(".png") ? "object-contain p-5" : "object-cover"} src={src} alt="BOB-LIFT capability" fill sizes="(min-width: 1024px) 24vw, 50vw" />
            </div>
          ))}
        </div>

        <div className="flex flex-col justify-center">
          <p className="section-label mb-4">Company Strength</p>
          <h2 className="text-[34px] font-black leading-none tracking-[-0.05em] text-ink md:text-[58px]">Manufacturing, export and service in one team.</h2>
          <p className="mt-5 text-base font-semibold leading-8 text-slate-600">BOB-LIFT focuses on truck mounted cranes, marine cranes and practical lifting equipment for international customers, with clear product information and direct engineering communication before quotation.</p>
          <div className="mt-7 grid grid-cols-3 gap-3">
            {stats.map(({ value, label, icon: Icon }) => (
              <div className="rounded-2xl border border-line bg-[#f8fbff] p-4 text-center" key={label}>
                <Icon className="mx-auto h-5 w-5 text-gold" />
                <strong className="mt-3 block text-2xl font-black text-ink">{value}</strong>
                <span className="mt-1 block text-[11px] font-bold leading-4 text-slate-500">{label}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)] py-10 md:py-16">
        <div className="page-shell">
          <p className="section-label mb-4">Development Timeline</p>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
            {timeline.map((item) => (
              <div className="relative rounded-[26px] border border-line bg-white p-5 shadow-card" key={item}>
                <span className="absolute right-5 top-5 h-3 w-3 rounded-full bg-gold shadow-card" />
                <strong className="block text-3xl font-black text-ink">{item.split(" ")[0]}</strong>
                <p className="mt-3 text-sm font-semibold leading-6 text-slate-500">{item.replace(/^\S+\s/, "")}</p>
              </div>
            ))}
          </div>
        </div>
      </section>
    </SiteChrome>
  );
}
