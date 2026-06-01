import Image from "next/image";
import { ArrowRight, Award, Building2, Factory, Globe2, Network, PackageCheck, Ship, Truck, Wrench } from "lucide-react";
import type { ComponentType } from "react";

import { PageHero } from "@/components/site/PageHero";
import { SiteChrome } from "@/components/site/SiteChrome";
import { getAboutPageContent } from "@/lib/cms-pages";
import { getPageUiContent } from "@/lib/cms-content";
import { localizedPath } from "@/lib/i18n";
import { localizedSeoMetadata } from "@/lib/seo-metadata";
import { getRequestLocale } from "@/lib/server-locale";

export async function generateMetadata() {
  const ui = (await getPageUiContent()).aboutPage;
  return localizedSeoMetadata({ title: ui.metaTitle, description: ui.metaDescription, canonicalPath: ui.canonicalPath });
}

export const dynamic = "force-dynamic";

const iconMap: Record<string, ComponentType<{ className?: string }>> = {
  award: Award,
  building: Building2,
  factory: Factory,
  globe: Globe2,
  network: Network,
  "package-check": PackageCheck,
  ship: Ship,
  truck: Truck,
  wrench: Wrench,
};

export default async function AboutPage() {
  const [content, pageUi, locale] = await Promise.all([getAboutPageContent(), getPageUiContent(), getRequestLocale()]);
  const ui = pageUi.aboutPage;
  const gallery = content.gallery.slice(0, ui.galleryImageCount);

  return (
    <SiteChrome>
      <PageHero label={content.label} title={content.title} description={content.text} image={gallery[0]?.src ?? "/images/factory-workshop.jpg"}>
        <a className="btn-primary rounded-full" href={localizedPath("/contact", locale)}>{ui.primaryCtaLabel} <ArrowRight className="h-4 w-4" /></a>
        <a className="btn-white rounded-full" href={localizedPath("/products", locale)}>{ui.secondaryCtaLabel}</a>
      </PageHero>

      <section className="page-shell grid grid-cols-1 gap-8 py-10 md:py-16 lg:grid-cols-[0.95fr_1.05fr] lg:gap-14">
        <div className="grid grid-cols-2 gap-3 md:gap-5">
          {gallery.map((item, index) => (
            <div className={`relative overflow-hidden rounded-[26px] border border-line bg-white shadow-card ${index === 1 ? "row-span-2 min-h-[360px]" : "min-h-[180px]"}`} key={item.src}>
              <Image className={item.src.endsWith(".png") ? "object-contain p-5" : "object-cover"} src={item.src} alt={item.alt} fill sizes="(min-width: 1024px) 24vw, 50vw" />
            </div>
          ))}
        </div>

        <div className="flex flex-col justify-center">
          <p className="section-label mb-4">{ui.strengthLabel}</p>
          <h2 className="text-[34px] font-black leading-none tracking-[-0.05em] text-ink md:text-[58px]">{ui.strengthTitle}</h2>
          <p className="mt-5 text-base font-semibold leading-8 text-slate-600">{content.text}</p>
          <div className="mt-7 grid grid-cols-3 gap-3">
            {content.stats.map(({ value, label, iconName }) => {
              const Icon = iconMap[iconName ?? "award"] ?? Award;
              return (
                <div className="rounded-2xl border border-line bg-[#f8fbff] p-4 text-center" key={label}>
                  <Icon className="mx-auto h-5 w-5 text-gold" />
                  <strong className="mt-3 block text-2xl font-black text-ink">{value}</strong>
                  <span className="mt-1 block text-[11px] font-bold leading-4 text-slate-500">{label}</span>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      <section className="bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)] py-10 md:py-16">
        <div className="page-shell">
          <p className="section-label mb-4">{ui.timelineLabel}</p>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
            {content.timeline.map((item) => (
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
