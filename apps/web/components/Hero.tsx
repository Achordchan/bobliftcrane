"use client";

import Image from "next/image";
import { type ReactNode, useEffect, useState } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";

import { defaultHomeHeroSlides, defaultHomeHeroUiContent, type HomeHeroSlide, type HomeHeroUiContent } from "@/lib/home-defaults";

export function Hero({ slides = defaultHomeHeroSlides, ui = defaultHomeHeroUiContent }: { slides?: HomeHeroSlide[]; ui?: HomeHeroUiContent }) {
  const heroSlides = slides.length > 0 ? slides : defaultHomeHeroSlides;
  const [activeIndex, setActiveIndex] = useState(0);

  useEffect(() => {
    if (!ui.autoplay || heroSlides.length <= 1) return;

    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (prefersReducedMotion) return;

    const timer = window.setInterval(() => {
      setActiveIndex((index) => (index + 1) % heroSlides.length);
    }, ui.autoplayIntervalMs);

    return () => window.clearInterval(timer);
  }, [heroSlides.length, ui.autoplay, ui.autoplayIntervalMs]);

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
    <section className="relative aspect-[12/5] w-full overflow-hidden bg-white" aria-label={ui.ariaLabel}>
      <div className="absolute inset-0 h-full w-full">
        {heroSlides.map((slide, index) => (
          <picture className="absolute inset-0 block h-full w-full" key={`${slide.image}-${index}`}>
            {slide.mobileImage && <source media="(max-width: 767px)" srcSet={slide.mobileImage} />}
            <Image
              alt=""
              className={`h-full w-full object-contain object-center transition duration-700 ease-out ${activeIndex === index ? "opacity-100 scale-100" : "opacity-0"}`}
              fill
              priority={index === 0}
              sizes="100vw"
              src={slide.image}
            />
          </picture>
        ))}
      </div>

      {ui.showControls ? (
        <>
          <div className="pointer-events-auto absolute left-4 top-1/2 z-20 -translate-y-1/2 md:left-8 lg:left-10">
            <HeroSwitchButton label={ui.previousSlideLabel} onClick={showPreviousSlide}>
              <ChevronLeft className="h-5 w-5 drop-shadow-sm" strokeWidth={3} />
            </HeroSwitchButton>
          </div>
          <div className="pointer-events-auto absolute right-4 top-1/2 z-20 -translate-y-1/2 md:right-8 lg:right-10">
            <HeroSwitchButton label={ui.nextSlideLabel} onClick={showNextSlide}>
              <ChevronRight className="h-5 w-5 drop-shadow-sm" strokeWidth={3} />
            </HeroSwitchButton>
          </div>
        </>
      ) : null}

      {ui.showIndicators ? (
        <div className="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-1 rounded-full border border-white/35 bg-white/40 px-2.5 py-1.5 shadow-[0_10px_28px_rgba(8,35,74,0.16)] backdrop-blur-sm md:bottom-8 md:gap-2 md:px-3 md:py-2 lg:bottom-10">
          {heroSlides.map((slide, index) => (
            <button
              aria-label={formatHeroLabel(ui.slideIndicatorLabelTemplate, index + 1)}
              aria-current={activeIndex === index}
              className={`h-1.5 rounded-full transition-all duration-300 md:h-2 ${activeIndex === index ? "w-7 bg-gold md:w-9" : "w-1.5 bg-white/90 hover:bg-white md:w-2"}`}
              key={`${slide.image}-${index}-mobile`}
              onClick={() => showSlide(index)}
              type="button"
            />
          ))}
        </div>
      ) : null}
    </section>
  );
}

function formatHeroLabel(template: string, index: number) {
  const normalizedTemplate = template.includes("{index}") ? template : "Show banner {index}";
  return normalizedTemplate.replaceAll("{index}", String(index));
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
