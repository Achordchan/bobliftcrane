"use client";

import Image from "next/image";
import { type ReactNode, useEffect, useState } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";

const heroSlides = [
  { title: "Marine Crane", image: "/images/marine-scene.jpg" },
  { title: "Truck Mounted Crane", image: "/images/telescopic-banner.jpg" },
  { title: "Knuckle Boom Crane", image: "/images/knuckle-banner.jpg" },
];

export function Hero() {
  const [activeIndex, setActiveIndex] = useState(0);

  useEffect(() => {
    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (prefersReducedMotion) return;

    const timer = window.setInterval(() => {
      setActiveIndex((index) => (index + 1) % heroSlides.length);
    }, 5600);

    return () => window.clearInterval(timer);
  }, []);

  function showSlide(index: number) {
    setActiveIndex((index + heroSlides.length) % heroSlides.length);
  }

  function showPreviousSlide() {
    setActiveIndex((index) => (index - 1 + heroSlides.length) % heroSlides.length);
  }

  function showNextSlide() {
    setActiveIndex((index) => (index + 1) % heroSlides.length);
  }

  return (
    <section className="relative h-[41.667vw] min-h-[178px] overflow-hidden bg-[#d9eef9] md:h-[max(430px,41.667vw)]" aria-label="Featured lifting solutions">
      <div className="absolute inset-0">
        {heroSlides.map((slide, index) => (
          <Image
            alt={slide.title}
            className={`object-cover object-center transition duration-700 ease-out ${activeIndex === index ? "opacity-100 scale-100" : "opacity-0 scale-[1.015]"}`}
            fill
            key={slide.title}
            priority={index === 0}
            sizes="100vw"
            src={slide.image}
          />
        ))}
      </div>

      <div className="pointer-events-none absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-[#08234a]/28 to-transparent" />

      <div className="absolute inset-x-4 top-1/2 z-20 flex -translate-y-1/2 items-center justify-between md:inset-x-auto md:bottom-12 md:left-1/2 md:top-auto md:-translate-x-1/2 md:translate-y-0 md:justify-start md:gap-2 md:rounded-full md:border md:border-white/45 md:bg-white/18 md:p-2 md:shadow-[0_16px_42px_rgba(8,35,74,0.18)] md:backdrop-blur-md lg:bottom-14">
        <HeroSwitchButton label="Previous banner" onClick={showPreviousSlide}>
          <ChevronLeft className="h-5 w-5 drop-shadow-sm" strokeWidth={3} />
        </HeroSwitchButton>

        <div className="absolute bottom-[-56px] left-1/2 flex -translate-x-1/2 items-center gap-1 rounded-full bg-white/25 px-2 py-1 backdrop-blur-sm md:static md:translate-x-0 md:gap-2 md:bg-transparent md:px-1 md:py-0 md:backdrop-blur-0">
          {heroSlides.map((slide, index) => (
            <button
              aria-label={`Show ${slide.title}`}
              aria-current={activeIndex === index}
              className={`h-1 rounded-full transition-all duration-300 md:h-2 ${activeIndex === index ? "w-5 bg-gold md:w-8" : "w-1 bg-white/85 hover:bg-white md:w-2"}`}
              key={slide.title}
              onClick={() => showSlide(index)}
              type="button"
            />
          ))}
        </div>

        <HeroSwitchButton label="Next banner" onClick={showNextSlide}>
          <ChevronRight className="h-5 w-5 drop-shadow-sm" strokeWidth={3} />
        </HeroSwitchButton>
      </div>
    </section>
  );
}

function HeroSwitchButton({ children, label, onClick }: { children: ReactNode; label: string; onClick: () => void }) {
  return (
    <button
      aria-label={label}
      className="grid h-7 w-7 place-items-center rounded-2xl border border-blue-100 bg-white/95 text-[#08234a] shadow-[0_14px_34px_rgba(8,35,74,0.14)] backdrop-blur transition duration-300 hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700 hover:shadow-[0_18px_42px_rgba(37,99,235,0.16)] md:h-10 md:w-10"
      onClick={onClick}
      type="button"
    >
      {children}
    </button>
  );
}
