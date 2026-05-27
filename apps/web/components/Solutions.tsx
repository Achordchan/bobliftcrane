import Image from "next/image";
import { ArrowRight } from "lucide-react";
import { industries } from "@/lib/home-data";

export function Solutions() {
  return (
    <section id="application" className="page-shell py-8 md:py-14">
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-[260px_1fr] lg:gap-10">
        <div>
          <p className="section-label mb-3 md:mb-4">Solutions for Every Industry</p>
          <h2 className="max-w-none text-[clamp(27px,6.8vw,34px)] font-black leading-[1.02] tracking-[-0.055em] text-ink sm:text-3xl sm:leading-tight sm:tracking-[-0.03em]">
            Lifting Solutions That Build Stronger Futures.
          </h2>
          <a className="mt-4 inline-flex items-center gap-2 text-sm font-black text-ink lg:mt-9" href="#application">View All Applications <ArrowRight className="h-4 w-4" /></a>
        </div>
        <div className="-mx-4 overflow-x-auto px-4 pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:mx-0 sm:overflow-visible sm:px-0 [&::-webkit-scrollbar]:hidden">
          <div className="grid auto-cols-[78%] grid-flow-col gap-4 sm:grid-flow-row sm:grid-cols-2 sm:gap-5 lg:grid-cols-5">
            {industries.map(({ title, image, icon: Icon }) => (
              <article className="card-hover overflow-hidden rounded-[24px] border border-line bg-white p-3 text-left shadow-card sm:rounded-2xl sm:p-4 sm:text-center" key={title}>
                <div className="flex items-center gap-3 sm:block">
                  <span className="grid size-10 shrink-0 place-items-center rounded-2xl border border-line bg-[#f7fbff] text-slate-500 sm:mx-auto sm:size-auto sm:border-0 sm:bg-transparent">
                    <Icon className="h-5 w-5 sm:h-8 sm:w-8" />
                  </span>
                  <p className="text-sm font-black text-ink sm:mt-4 sm:text-xs sm:font-bold sm:text-slate-400">{title}</p>
                </div>
                <div className="relative mt-3 h-32 overflow-hidden rounded-[18px] sm:mt-4 sm:h-28 sm:rounded-xl">
                  <Image className="object-cover" src={image} alt={title} fill sizes="(min-width: 1024px) 16vw, 78vw" />
                </div>
              </article>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
