"use client";

import Image from "next/image";
import { type ReactNode, useEffect, useState } from "react";
import { ArrowRight, ChevronLeft, ChevronRight } from "lucide-react";

import { defaultHomeHeroSlides, defaultHomeHeroUiContent, type HomeHeroSlide, type HomeHeroUiContent } from "@/lib/home-defaults";

export function Hero({ slides = defaultHomeHeroSlides, ui = defaultHomeHeroUiContent }: { slides?: HomeHeroSlide[]; ui?: HomeHeroUiContent }) {
  const heroSlides = slides.length > 0 ? slides : defaultHomeHeroSlides;
  const [activeIndex, setActiveIndex] = useState(0);
  const activeSlide = heroSlides[activeIndex] ?? heroSlides[0];

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
    <section className="relative min-h-[430px] overflow-hidden bg-[#d9eef9] md:min-h-[560px]" aria-label={ui.ariaLabel}>
      <div className="absolute inset-0">
        {heroSlides.map((slide, index) => (
          <picture className="absolute inset-0" key={`${slide.title}-${index}`}>
            {slide.mobileImage && <source media="(max-width: 767px)" srcSet={slide.mobileImage} />}
            <Image
              alt={slide.title}
              className={`object-cover object-center transition duration-700 ease-out ${activeIndex === index ? "opacity-100 scale-100" : "opacity-0 scale-[1.015]"}`}
              fill
              priority={index === 0}
              sizes="100vw"
              src={slide.image}
            />
          </picture>
        ))}
      </div>

      {ui.showOverlay ? (
        <>
          <div className="pointer-events-none absolute inset-0 bg-[linear-gradient(90deg,rgba(8,35,74,0.74)_0%,rgba(8,35,74,0.44)_42%,rgba(8,35,74,0.08)_100%)]" />
          <div className="pointer-events-none absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-[#08234a]/36 to-transparent" />
        </>
      ) : null}

      <div className="page-shell relative z-10 flex min-h-[430px] items-center py-20 md:min-h-[560px] md:py-28">
        <div className="max-w-3xl text-white">
          {activeSlide?.eyebrow && (
            <p className="text-xs font-black uppercase tracking-[0.28em] text-gold md:text-sm">
              {activeSlide.eyebrow}
            </p>
          )}
          <h1 className="mt-4 text-[clamp(2.4rem,7vw,5.8rem)] font-black leading-[0.95] tracking-[-0.065em] drop-shadow-[0_18px_42px_rgba(0,0,0,0.22)]">
            {activeSlide?.title}
          </h1>
          {activeSlide?.description && (
            <p className="mt-5 max-w-2xl text-base font-semibold leading-7 text-white/82 md:text-lg md:leading-8">
              {activeSlide.description}
            </p>
          )}
          {(activeSlide?.primaryCtaLabel || activeSlide?.secondaryCtaLabel) && (
            <div className="mt-8 flex flex-wrap gap-3">
              {activeSlide.primaryCtaLabel && activeSlide.primaryCtaHref && (
                <a className="inline-flex min-h-12 items-center justify-center gap-3 rounded-full bg-gold px-6 text-sm font-black text-navy shadow-[0_18px_44px_rgba(245,154,60,0.24)] transition hover:-translate-y-0.5 hover:bg-white" href={activeSlide.primaryCtaHref}>
                  {activeSlide.primaryCtaLabel}
                  <ArrowRight className="h-4 w-4" />
                </a>
              )}
              {activeSlide.secondaryCtaLabel && activeSlide.secondaryCtaHref && (
                <a className="inline-flex min-h-12 items-center justify-center rounded-full border border-white/50 bg-white/12 px-6 text-sm font-black text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white hover:text-navy" href={activeSlide.secondaryCtaHref}>
                  {activeSlide.secondaryCtaLabel}
                </a>
              )}
            </div>
          )}
        </div>
      </div>

      {ui.showControls || ui.showIndicators ? (
        <div className="absolute inset-x-4 top-1/2 z-20 flex -translate-y-1/2 items-center justify-between md:inset-x-auto md:bottom-12 md:left-1/2 md:top-auto md:-translate-x-1/2 md:translate-y-0 md:justify-start md:gap-2 md:rounded-full md:border md:border-white/45 md:bg-white/18 md:p-2 md:shadow-[0_16px_42px_rgba(8,35,74,0.18)] md:backdrop-blur-md lg:bottom-14">
          {ui.showControls ? (
            <HeroSwitchButton label={ui.previousSlideLabel} onClick={showPreviousSlide}>
              <ChevronLeft className="h-5 w-5 drop-shadow-sm" strokeWidth={3} />
            </HeroSwitchButton>
          ) : null}

          {ui.showIndicators ? (
            <div className="absolute bottom-[-56px] left-1/2 flex -translate-x-1/2 items-center gap-1 rounded-full bg-white/25 px-2 py-1 backdrop-blur-sm md:static md:translate-x-0 md:gap-2 md:bg-transparent md:px-1 md:py-0 md:backdrop-blur-0">
              {heroSlides.map((slide, index) => (
                <button
                  aria-label={formatHeroLabel(ui.slideIndicatorLabelTemplate, slide.title)}
                  aria-current={activeIndex === index}
                  className={`h-1 rounded-full transition-all duration-300 md:h-2 ${activeIndex === index ? "w-5 bg-gold md:w-8" : "w-1 bg-white/85 hover:bg-white md:w-2"}`}
                  key={`${slide.title}-${index}`}
                  onClick={() => showSlide(index)}
                  type="button"
                />
              ))}
            </div>
          ) : null}

          {ui.showControls ? (
            <HeroSwitchButton label={ui.nextSlideLabel} onClick={showNextSlide}>
              <ChevronRight className="h-5 w-5 drop-shadow-sm" strokeWidth={3} />
            </HeroSwitchButton>
          ) : null}
        </div>
      ) : null}
    </section>
  );
}

function formatHeroLabel(template: string, title: string) {
  return template.replaceAll("{title}", title);
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
