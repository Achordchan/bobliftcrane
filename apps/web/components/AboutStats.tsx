"use client";

import Image from "next/image";
import { useState } from "react";
import { ArrowRight, Award, Building2, CirclePlay, Factory, Globe2, Network, PackageCheck, Ship, Truck, Wrench } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { defaultAboutSectionContent, type AboutSectionContent } from "@/lib/home-defaults";

const iconMap = {
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

export function AboutSection({ content = defaultAboutSectionContent }: { content?: AboutSectionContent }) {
  const [activeIndex, setActiveIndex] = useState(1);
  const gallery = content.gallery.length > 0 ? content.gallery : defaultAboutSectionContent.gallery;
  const activeImage = gallery[Math.min(activeIndex, gallery.length - 1)] ?? gallery[0];
  const showGallery = content.showGallery && gallery.length > 0;

  return (
    <section id="about" className={showGallery ? "page-shell grid grid-cols-1 gap-7 overflow-hidden py-8 md:gap-10 md:py-14 lg:grid-cols-[1fr_0.95fr] lg:items-stretch lg:gap-20" : "page-shell overflow-hidden py-8 md:py-14"}>
      {showGallery ? (
      <div className="flex h-full min-h-0 flex-col">
        <div className="relative h-[220px] overflow-hidden rounded-[22px] shadow-card sm:h-[340px] lg:h-auto lg:min-h-[460px] lg:flex-1">
          <Image className="object-cover" key={activeImage.src} src={activeImage.src} alt={activeImage.alt} fill sizes="50vw" />
          {activeImage.video && (
            activeImage.videoUrl ? (
              <a className="absolute left-1/2 top-1/2 grid size-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white/94 text-gold shadow-[0_18px_50px_rgba(8,35,74,0.18)] ring-1 ring-white/70 transition duration-300 hover:scale-105 hover:bg-white sm:size-20" href={activeImage.videoUrl} target="_blank" rel="noreferrer" aria-label={content.videoLabel}>
                <CirclePlay className="size-7 fill-gold sm:size-10" />
              </a>
            ) : (
              <span className="absolute left-1/2 top-1/2 grid size-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white/94 text-gold shadow-[0_18px_50px_rgba(8,35,74,0.18)] ring-1 ring-white/70 sm:size-20">
                <CirclePlay className="size-7 fill-gold sm:size-10" />
              </span>
            )
          )}
        </div>
        <div className="mt-3 grid grid-cols-5 gap-2 sm:mt-4 sm:gap-3">
          {gallery.map((item, index) => (
            <button
              aria-label={formatAboutLabel(content.galleryThumbnailLabelTemplate, item.alt)}
              className={`relative h-12 overflow-hidden rounded-lg border shadow-card transition duration-200 hover:-translate-y-0.5 sm:h-16 ${activeIndex === index ? "border-blue-500 ring-2 ring-blue-500/20" : "border-white"}`}
              key={item.src}
              onClick={() => setActiveIndex(index)}
              type="button"
            >
              <Image className="object-cover" src={item.src} alt={item.alt} fill sizes="10vw" />
              {item.video && <span className="absolute bottom-1 right-1 rounded-full bg-navy/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-[0.08em] text-white shadow-card sm:bottom-1.5 sm:right-1.5 sm:px-2 sm:text-[9px]">{content.videoLabel}</span>}
            </button>
          ))}
        </div>
      </div>
      ) : null}
      <div className={showGallery ? "pt-0 lg:pt-[25px]" : "mx-auto max-w-5xl"}>
        <p className="section-label mb-3 md:mb-4">{content.label}</p>
        <h2 className="max-w-none text-[clamp(29px,7.1vw,36px)] font-black leading-[1.02] tracking-[-0.06em] text-ink sm:text-4xl sm:leading-tight sm:tracking-[-0.035em]">
          {content.title}
        </h2>
        <p className="mt-4 text-sm leading-7 text-slate-600 sm:mt-6 sm:text-base sm:leading-8">
          {content.text}
        </p>
        {content.showStats ? (
        <div className="mt-6 grid grid-cols-3 gap-2 sm:mt-8 sm:gap-3">
          {content.stats.map(({ value, label, iconName }) => {
            const Icon = iconMap[iconName as keyof typeof iconMap] ?? Award;
            return (
              <Card className="rounded-2xl border-line bg-gradient-to-br from-white to-[#f7faff] py-0 shadow-[0_14px_36px_rgba(8,35,74,0.06)] ring-0" key={label} size="sm">
                <CardContent className="flex h-full flex-col items-center justify-center gap-2 p-3 text-center sm:flex-row sm:justify-start sm:gap-3 sm:p-4 sm:text-left">
                  <span className="grid size-8 shrink-0 place-items-center rounded-full border border-gold/45 bg-gold/8 text-gold sm:size-10">
                    <Icon className="size-4 sm:size-5" />
                  </span>
                  <p>
                    <strong className="block text-xl font-black leading-none text-ink sm:text-2xl">{value}</strong>
                    <span className="mt-1 block text-[10px] font-bold leading-3 text-slate-500 sm:text-xs sm:leading-4">{label}</span>
                  </p>
                </CardContent>
              </Card>
            );
          })}
        </div>
        ) : null}
        {content.showTimeline ? (
        <div className="mt-7 grid grid-cols-2 gap-4 sm:mt-10 sm:grid-cols-4 sm:gap-5 lg:gap-6">
          {content.timeline.map((item) => (
            <div className="relative border-t border-line pt-6" key={item}>
              <span className="absolute -top-2 left-0 h-4 w-4 rounded-full border-4 border-white bg-gold shadow-card" />
              <strong className="block text-sm font-black text-ink">{item.split(" ")[0]}</strong>
              <p className="mt-2 text-xs font-bold leading-5 text-slate-500">{item.replace(/^\S+\s/, "")}</p>
            </div>
          ))}
        </div>
        ) : null}
        {content.showCta ? (
        <a className="mt-9 inline-flex items-center gap-2 text-base font-black text-ink md:text-lg" href={content.ctaHref}>
          {content.ctaLabel} <ArrowRight className="h-5 w-5" />
        </a>
        ) : null}
      </div>
    </section>
  );
}

function formatAboutLabel(template: string, title: string) {
  return template.replaceAll("{title}", title);
}
