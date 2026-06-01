import Image from "next/image";
import { ArrowRight, Building2, Factory, Network, Ship, Truck, Wrench } from "lucide-react";
import { defaultApplicationSectionContent, type ApplicationSectionContent } from "@/lib/home-defaults";

const iconMap = {
  building: Building2,
  factory: Factory,
  network: Network,
  ship: Ship,
  truck: Truck,
  wrench: Wrench,
};

export function Solutions({ content = defaultApplicationSectionContent }: { content?: ApplicationSectionContent }) {
  return (
    <section id="application" className="page-shell py-8 md:py-14">
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-[320px_1fr] lg:gap-12">
        <div>
          <p className="section-label mb-3 md:mb-4">{content.label}</p>
          <h2 className="max-w-none text-[clamp(30px,6.8vw,38px)] font-black leading-[1.02] tracking-[-0.055em] text-ink sm:text-4xl sm:leading-tight sm:tracking-[-0.03em]">
            {content.title}
          </h2>
          {content.showCta ? (
            <a className="mt-4 inline-flex items-center gap-2 text-base font-black text-ink lg:mt-9" href={content.ctaHref}>{content.ctaLabel} <ArrowRight className="h-4 w-4" /></a>
          ) : null}
        </div>
        <div className="-mx-4 overflow-x-auto px-4 pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:mx-0 sm:overflow-visible sm:px-0 [&::-webkit-scrollbar]:hidden">
          <div className="grid auto-cols-[78%] grid-flow-col gap-4 sm:grid-flow-row sm:grid-cols-2 sm:gap-5 lg:grid-cols-5">
            {content.items.map(({ title, image, iconName }) => {
              const Icon = iconMap[iconName as keyof typeof iconMap] ?? Factory;
              return (
                <article className="card-hover overflow-hidden rounded-[24px] border border-line bg-white p-4 text-left shadow-card sm:rounded-2xl sm:p-5 sm:text-center" key={title}>
                  <div className="flex items-center gap-3 sm:block">
                    {content.showIcons ? (
                      <span className="grid size-10 shrink-0 place-items-center rounded-2xl border border-line bg-[#f7fbff] text-slate-500 sm:mx-auto sm:size-auto sm:border-0 sm:bg-transparent">
                        <Icon className="h-5 w-5 sm:h-8 sm:w-8" />
                      </span>
                    ) : null}
                    <p className="text-base font-black text-ink sm:mt-4 sm:font-bold sm:text-slate-500">{title}</p>
                  </div>
                  {content.showImages ? (
                    <div className="relative mt-3 h-36 overflow-hidden rounded-[18px] sm:mt-4 sm:h-32 sm:rounded-xl">
                      <Image className="object-cover" src={image} alt={title} fill sizes="(min-width: 1024px) 16vw, 78vw" />
                    </div>
                  ) : null}
                </article>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
}
