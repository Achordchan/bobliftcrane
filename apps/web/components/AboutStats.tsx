"use client";

import Image from "next/image";
import { useState } from "react";
import { ArrowRight, CirclePlay } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { stats, timeline } from "@/lib/home-data";

const gallery = [
  { src: "/images/truck-mounted-crane.png", alt: "Truck mounted crane" },
  { src: "/images/factory-workshop.jpg", alt: "BOB-LIFT factory video preview", video: true },
  { src: "/images/marine-scene.jpg", alt: "Marine crane project" },
  { src: "/images/telescopic-banner.jpg", alt: "Telescopic boom crane" },
  { src: "/images/knuckle-banner.jpg", alt: "Knuckle boom crane" },
];

export function AboutSection() {
  const [activeIndex, setActiveIndex] = useState(1);
  const activeImage = gallery[activeIndex];

  return (
    <section id="about" className="page-shell grid grid-cols-1 gap-7 overflow-hidden py-8 md:gap-10 md:py-14 lg:grid-cols-[1fr_0.95fr] lg:gap-20">
      <div>
        <div className="relative h-[220px] overflow-hidden rounded-[22px] shadow-card sm:h-[340px] lg:h-[405px]">
          <Image className="object-cover" key={activeImage.src} src={activeImage.src} alt={activeImage.alt} fill sizes="50vw" />
          {activeImage.video && (
            <button className="absolute left-1/2 top-1/2 grid size-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white/94 text-gold shadow-[0_18px_50px_rgba(8,35,74,0.18)] ring-1 ring-white/70 transition duration-300 hover:scale-105 hover:bg-white sm:size-20" type="button">
              <CirclePlay className="size-7 fill-gold sm:size-10" />
            </button>
          )}
        </div>
        <div className="mt-3 grid grid-cols-5 gap-2 sm:mt-4 sm:gap-3">
          {gallery.map((item, index) => (
            <button
              aria-label={`Show ${item.alt}`}
              className={`relative h-12 overflow-hidden rounded-lg border shadow-card transition duration-200 hover:-translate-y-0.5 sm:h-16 ${activeIndex === index ? "border-blue-500 ring-2 ring-blue-500/20" : "border-white"}`}
              key={item.src}
              onClick={() => setActiveIndex(index)}
              type="button"
            >
              <Image className="object-cover" src={item.src} alt={item.alt} fill sizes="10vw" />
              {item.video && <span className="absolute bottom-1 right-1 rounded-full bg-navy/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-[0.08em] text-white shadow-card sm:bottom-1.5 sm:right-1.5 sm:px-2 sm:text-[9px]">Video</span>}
            </button>
          ))}
        </div>
      </div>
      <div className="pt-0 lg:pt-[25px]">
        <p className="section-label mb-3 md:mb-4">About BOB-LIFT</p>
        <h2 className="max-w-none text-[clamp(29px,7.1vw,36px)] font-black leading-[1.02] tracking-[-0.06em] text-ink sm:text-4xl sm:leading-tight sm:tracking-[-0.035em]">
          Engineering Excellence. Powered by People.
        </h2>
        <p className="mt-4 text-sm leading-7 text-slate-600 sm:mt-6 sm:text-base sm:leading-8">
          BOB-LIFT is a global manufacturer of advanced lifting solutions with 35+ years of experience. We combine innovation, precision and passion to deliver cranes that exceed expectations worldwide.
        </p>
        <div className="mt-6 grid grid-cols-3 gap-2 sm:mt-8 sm:gap-3">
          {stats.map(({ value, label, icon: Icon }) => (
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
          ))}
        </div>
        <div className="mt-7 grid grid-cols-2 gap-4 sm:mt-10 sm:grid-cols-4 sm:gap-5 lg:gap-6">
          {timeline.map((item) => (
            <div className="relative border-t border-line pt-6" key={item}>
              <span className="absolute -top-2 left-0 h-4 w-4 rounded-full border-4 border-white bg-gold shadow-card" />
              <strong className="block text-sm font-black text-ink">{item.split(" ")[0]}</strong>
              <p className="mt-2 text-xs font-bold leading-5 text-slate-500">{item.replace(/^\S+\s/, "")}</p>
            </div>
          ))}
        </div>
        <a className="mt-9 inline-flex items-center gap-2 text-sm font-black text-ink" href="/about">
          More About Us <ArrowRight className="h-4 w-4" />
        </a>
      </div>
    </section>
  );
}
