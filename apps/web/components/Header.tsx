"use client";

import { ArrowRight, Check, ChevronDown, Mail, Phone } from "lucide-react";
import Image from "next/image";
import { usePathname } from "next/navigation";
import { useEffect, useRef, useState } from "react";

import { Button } from "@/components/ui/button";
import { contact as defaultContactSettings, type ContactSettings } from "@/lib/contact";
import { defaultHeaderTopBarContent, type HeaderTopBarContent } from "@/lib/home-defaults";
import { navItems as defaultNavItems, productNavGroups as defaultProductNavGroups, type NavItem, type ProductNavGroup } from "@/lib/home-data";
import { defaultChromeUiContent, defaultLanguageOptions, type ChromeUiContent, type SiteLanguageOption } from "@/lib/site-ui";
import { cn } from "@/lib/utils";
import { LanguageMenu } from "./LanguageMenu";
import { Logo } from "./Logo";
import { MobileNavMenu } from "./MobileNavMenu";

type ProductIconName =
  | "truck-crane"
  | "marine-crane"
  | "attachments"
  | "knuckle-truck"
  | "aerial-platform"
  | "knuckle-marine"
  | "telescopic-marine"
  | "pickup-crane"
  | "forklift-jib"
  | "mini-dumper"
  | "other-crane"
  | "catalog";

type HeaderBranding = {
  logoSrc: string;
  logoWidth: number;
  logoHeight: number;
  productMenuBackgroundImage: string;
};

function normalizeProductIconName(value: string | undefined, fallback: ProductIconName): ProductIconName {
  return value && value in productIconSources ? (value as ProductIconName) : fallback;
}

const productIconSources: Record<ProductIconName, string> = {
  "truck-crane": "/images/mega-menu-icons/truck-crane.png",
  "marine-crane": "/images/mega-menu-icons/marine-crane.png",
  attachments: "/images/mega-menu-icons/attachments.png",
  "knuckle-truck": "/images/mega-menu-icons/knuckle-truck.png",
  "aerial-platform": "/images/mega-menu-icons/aerial-platform.png",
  "knuckle-marine": "/images/mega-menu-icons/knuckle-marine.png",
  "telescopic-marine": "/images/mega-menu-icons/telescopic-marine.png",
  "pickup-crane": "/images/mega-menu-icons/pickup-crane.png",
  "forklift-jib": "/images/mega-menu-icons/forklift-jib.png",
  "mini-dumper": "/images/mega-menu-icons/mini-dumper.png",
  "other-crane": "/images/mega-menu-icons/other-crane.png",
  catalog: "/images/mega-menu-icons/catalog.png",
};

const productMenuImageSources = ["/images/mega-menu-backgrounds/truck-menu-bg.jpg", "/images/mega-menu-backgrounds/marine-menu-bg.jpg", "/images/knuckle-banner.jpg"];
const craneByTonHref = "/products#crane-by-ton";

function ProductLineIcon({ className, name }: { className?: string; name: ProductIconName }) {
  return (
    <Image
      alt=""
      aria-hidden="true"
      className={cn("object-contain transition duration-200", className)}
      height={96}
      src={productIconSources[name]}
      width={96}
    />
  );
}

function productMenuImage(index: number, fallback: string) {
  return productMenuImageSources[index] ?? fallback;
}

export function Header({
  contact = defaultContactSettings,
  navItems = defaultNavItems,
  productNavGroups = defaultProductNavGroups,
  topBar = defaultHeaderTopBarContent,
  languageOptions = defaultLanguageOptions,
  chromeUi = defaultChromeUiContent,
  branding = {
    logoSrc: "/images/bob-lift-logo.png",
    logoWidth: 188,
    logoHeight: 91,
    productMenuBackgroundImage: "/images/telescopic-banner.jpg",
  },
}: {
  contact?: ContactSettings;
  navItems?: NavItem[];
  productNavGroups?: ProductNavGroup[];
  topBar?: HeaderTopBarContent;
  languageOptions?: SiteLanguageOption[];
  chromeUi?: ChromeUiContent;
  branding?: HeaderBranding;
}) {
  const pathname = usePathname();
  const [productsMenuOpen, setProductsMenuOpen] = useState(false);
  const productsMenuCloseTimer = useRef<number | null>(null);

  function openProductsMenu() {
    if (productsMenuCloseTimer.current) {
      window.clearTimeout(productsMenuCloseTimer.current);
      productsMenuCloseTimer.current = null;
    }
    setProductsMenuOpen(true);
  }

  function scheduleCloseProductsMenu() {
    if (productsMenuCloseTimer.current) {
      window.clearTimeout(productsMenuCloseTimer.current);
    }
    productsMenuCloseTimer.current = window.setTimeout(() => {
      setProductsMenuOpen(false);
      productsMenuCloseTimer.current = null;
    }, 220);
  }

  useEffect(() => () => {
    if (productsMenuCloseTimer.current) {
      window.clearTimeout(productsMenuCloseTimer.current);
    }
  }, []);

  return (
    <header id="top" className="relative z-[200]">
      <div className="bg-navy px-4 py-3 text-center text-[11px] font-semibold leading-5 text-white/85 md:text-xs">
        <span className="text-gold">{topBar.highlight}</span> {topBar.text}
        <a className="ml-3 text-gold md:ml-5" href={topBar.linkHref}>{topBar.linkLabel}</a>
      </div>
      <div className="border-b border-line bg-white/96 shadow-[0_10px_34px_rgba(8,35,74,0.05)] backdrop-blur">
        <div className="page-shell flex h-[78px] items-center justify-between gap-3 lg:h-[92px] lg:gap-4 2xl:gap-8">
          <Logo
            height={branding.logoHeight}
            homeHref={navItems.find((item) => item.href === "/" || /^\/[a-z]{2}$/.test(item.href))?.href ?? "/"}
            homeLabel={chromeUi.logoHomeLabel}
            imageAlt={chromeUi.logoImageAlt}
            src={branding.logoSrc}
            width={branding.logoWidth}
          />
          <nav className="hidden min-w-0 items-center rounded-full border border-line bg-[#f7faff]/90 p-1.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.9)] xl:flex" aria-label={chromeUi.mainNavigationLabel}>
            {navItems.map((item) => {
              const active = item.href === "/" ? pathname === "/" : pathname.startsWith(item.href);
              const navLinkClassName = `group flex h-10 shrink-0 items-center gap-1 whitespace-nowrap rounded-full px-2.5 text-[12px] font-black leading-none transition duration-300 min-[1400px]:gap-1.5 min-[1400px]:px-3 min-[1400px]:text-[13px] 2xl:px-4 2xl:text-sm ${active ? "bg-white text-ink shadow-[0_8px_22px_rgba(8,35,74,0.08)]" : "text-slate-600 hover:bg-white hover:text-ink hover:shadow-[0_8px_22px_rgba(8,35,74,0.06)]"}`;

              if (item.hasMenu && item.href.startsWith("/products")) {
                return (
                  <div className="group/products relative" key={item.label} onBlur={scheduleCloseProductsMenu} onFocus={openProductsMenu} onMouseEnter={openProductsMenu} onMouseLeave={scheduleCloseProductsMenu}>
                    <a aria-haspopup="true" className={navLinkClassName} href={item.href}>
                      {item.label}
                      <ChevronDown className={cn("h-3.5 w-3.5 transition group-hover/products:rotate-180 group-hover/products:text-gold", productsMenuOpen && "rotate-180 text-gold")} />
                    </a>
                    <div
                      className={cn(
                        "fixed left-1/2 top-[112px] z-[210] w-[min(1560px,calc(100vw-80px))] -translate-x-1/2 pt-4 transition duration-200 ease-out",
                        productsMenuOpen ? "pointer-events-auto visible translate-y-0 opacity-100" : "pointer-events-none invisible translate-y-3 opacity-0",
                      )}
                      onMouseEnter={openProductsMenu}
                      onMouseLeave={scheduleCloseProductsMenu}
                    >
                      <div className="relative max-h-[calc(100vh-132px)] overflow-hidden rounded-[30px] border border-[#d8e7f7] bg-white shadow-[0_30px_80px_rgba(8,35,74,0.14)] ring-1 ring-white/80" data-product-nav="desktop">
                        <div className="pointer-events-none absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.98)_0%,rgba(247,251,255,0.94)_100%)]" />

                        <div className="relative grid max-h-[calc(100vh-280px)] grid-cols-3 items-stretch gap-4 overflow-y-auto p-4 pr-3 2xl:max-h-[calc(100vh-250px)] 2xl:gap-6 2xl:p-6 2xl:pr-5">
                          {productNavGroups.map((group, groupIndex) => {
                            const groupIconName = normalizeProductIconName(group.iconName, "attachments");
                            const featuredCard = groupIndex < 2;
                            const hasCraneByTonLink = group.links.some((link) => link.href.includes("crane-by-ton") || link.label.trim().toLowerCase() === "crane by ton");

                            return (
                              <div
                                className={cn(
                                  "relative flex h-full min-h-[500px] flex-col overflow-hidden rounded-[26px] border border-[#dbe8f5] bg-white/92 shadow-[0_18px_48px_rgba(8,35,74,0.08)] 2xl:min-h-[590px]",
                                  featuredCard ? "p-0" : "p-5 2xl:p-6",
                                )}
                                key={`${group.title}-${group.href}-${groupIndex}`}
                              >
                                {featuredCard ? (
                                  <a className="group/title relative block h-[clamp(260px,19vw,310px)] w-full shrink-0 overflow-hidden border-b border-[#dbe8f5] bg-[#f4f8fd] 2xl:h-[clamp(300px,17vw,340px)]" href={group.href}>
                                    <Image className="object-cover object-center" src={productMenuImage(groupIndex, branding.productMenuBackgroundImage)} alt="" fill sizes="(min-width: 1536px) 520px, 42vw" />
                                    <div className="absolute inset-0 bg-[linear-gradient(115deg,rgba(255,255,255,0.96)_0%,rgba(255,255,255,0.78)_42%,rgba(255,255,255,0.18)_100%)]" />
                                    <div className="absolute left-4 top-4 flex items-start gap-3 2xl:left-6 2xl:top-6 2xl:gap-4">
                                      <span className="grid h-[52px] w-[52px] shrink-0 place-items-center rounded-[18px] bg-blue-700 text-white shadow-[0_16px_34px_rgba(37,99,235,0.26)] min-[1700px]:h-16 min-[1700px]:w-16 2xl:h-[68px] 2xl:w-[68px]">
                                        <ProductLineIcon className="h-8 w-8 brightness-0 invert min-[1700px]:h-11 min-[1700px]:w-11 2xl:h-11 2xl:w-11" name={groupIconName} />
                                      </span>
                                      <span className="min-w-0 max-w-[300px] pt-1">
                                        <span className="block text-[clamp(18px,1.12vw,26px)] font-black leading-tight tracking-[-0.045em] text-navy transition group-hover/title:text-blue-700">
                                          {group.title}
                                        </span>
                                        <span className="mt-1.5 block text-[clamp(12px,0.72vw,15px)] font-semibold leading-5 text-slate-600">
                                          {group.description}
                                        </span>
                                        <span className="mt-3 block h-1 w-12 rounded-full bg-blue-600" />
                                      </span>
                                    </div>
                                  </a>
                                ) : (
                                  <a className="group/title flex items-center gap-4" href={group.href}>
                                    <span className="grid h-14 w-14 shrink-0 place-items-center rounded-[18px] bg-blue-700 text-white shadow-[0_16px_34px_rgba(37,99,235,0.24)] min-[1700px]:h-16 min-[1700px]:w-16 2xl:h-[72px] 2xl:w-[72px]">
                                      <ProductLineIcon className="h-9 w-9 brightness-0 invert min-[1700px]:h-11 min-[1700px]:w-11 2xl:h-12 2xl:w-12" name={groupIconName} />
                                    </span>
                                    <span className="min-w-0">
                                      <span className="block text-[clamp(18px,1vw,22px)] font-black leading-tight tracking-[-0.04em] text-navy transition group-hover/title:text-blue-700">
                                        {group.title}
                                      </span>
                                      <span className="mt-1.5 block text-[clamp(13px,0.76vw,15px)] font-semibold leading-5 text-slate-500">
                                        {group.description}
                                      </span>
                                      <span className="mt-3 block h-1 w-10 rounded-full bg-blue-600" />
                                    </span>
                                  </a>
                                )}

                                <div className={cn("grid flex-1 content-start gap-0", featuredCard ? "p-3.5 2xl:p-5" : "mt-4 2xl:mt-5")}>
                                  {group.links.map((link, linkIndex) => {
                                    const linkIconName = normalizeProductIconName(link.iconName, "truck-crane");
                                    const highlighted = groupIndex === 0 && linkIndex === 0;

                                    return (
                                      <a
                                        className={cn(
                                          "group/item relative flex min-h-[50px] items-center gap-3 border-b border-[#e2ebf5] px-1 py-2 text-navy transition duration-200 ease-out last:border-b-0 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-500/15 2xl:min-h-[58px] 2xl:gap-4 2xl:py-2.5",
                                          highlighted && "text-blue-700",
                                        )}
                                        href={link.href}
                                        key={`${link.label}-${link.href}-${linkIndex}`}
                                      >
                                        <span
                                          className={cn(
                                            "grid h-10 w-10 shrink-0 place-items-center rounded-[12px] bg-[#f3f7fc] text-navy/75 transition duration-200 group-hover/item:bg-blue-50 2xl:h-12 2xl:w-12 2xl:rounded-[14px]",
                                            highlighted && "bg-blue-50 text-blue-700",
                                          )}
                                        >
                                          <ProductLineIcon className="h-7 w-7 2xl:h-8 2xl:w-8" name={linkIconName} />
                                        </span>
                                        <span className={cn("min-w-0 flex-1 font-black leading-[1.16] tracking-[-0.025em]", groupIndex === 2 ? "text-[clamp(12px,0.72vw,15px)] 2xl:text-[clamp(14px,0.82vw,17px)]" : "text-[clamp(13px,0.8vw,16px)] 2xl:text-[clamp(14px,0.86vw,17px)]")}>
                                          {link.label}
                                        </span>
                                        <ChevronDown className="h-4 w-4 shrink-0 -rotate-90 text-blue-600 transition duration-200 group-hover/item:translate-x-1" strokeWidth={2.2} />
                                      </a>
                                    );
                                  })}
                                  {featuredCard && !hasCraneByTonLink && (
                                    <a
                                      className="group/item relative flex min-h-[50px] items-center gap-3 border-b border-[#e2ebf5] px-1 py-2 text-navy transition duration-200 ease-out last:border-b-0 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-500/15 2xl:min-h-[58px] 2xl:gap-4 2xl:py-2.5"
                                      href={craneByTonHref}
                                    >
                                      <span className="grid h-10 w-10 shrink-0 place-items-center rounded-[12px] bg-[#f3f7fc] text-navy/75 transition duration-200 group-hover/item:bg-blue-50 2xl:h-12 2xl:w-12 2xl:rounded-[14px]">
                                        <ProductLineIcon className="h-7 w-7 2xl:h-8 2xl:w-8" name="catalog" />
                                      </span>
                                      <span className="min-w-0 flex-1 text-[clamp(13px,0.8vw,16px)] font-black leading-[1.16] tracking-[-0.025em] 2xl:text-[clamp(14px,0.86vw,17px)]">
                                        Crane By ton
                                      </span>
                                      <ChevronDown className="h-4 w-4 shrink-0 -rotate-90 text-blue-600 transition duration-200 group-hover/item:translate-x-1" strokeWidth={2.2} />
                                    </a>
                                  )}
                                </div>
                              </div>
                            );
                          })}
                        </div>

                        <div className="relative border-t border-[#dbe8f5] bg-[#f4f8fd]/94 px-5 py-3.5 2xl:px-7">
                          <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex min-w-[260px] items-center gap-4">
                              <span className="grid h-12 w-12 shrink-0 place-items-center rounded-[16px] bg-white text-navy">
                                <ProductLineIcon className="h-8 w-8" name="catalog" />
                              </span>
                              <span>
                                <span className="block text-[17px] font-black leading-tight text-navy">{chromeUi.productCatalogTitle}</span>
                                <span className="mt-1 block text-sm font-semibold text-slate-500">{chromeUi.productCatalogDescription}</span>
                              </span>
                            </div>
                            <div className="flex flex-wrap items-center gap-x-5 gap-y-3 text-sm font-black text-navy 2xl:text-base">
                              <a className="inline-flex items-center gap-4 text-navy transition hover:text-blue-700" href={`mailto:${contact.email}`}>
                                <span className="grid h-10 w-10 place-items-center rounded-full border border-[#d8e7f7] bg-white text-navy">
                                  <Mail className="h-5 w-5" strokeWidth={2} />
                                </span>
                                {contact.email}
                              </a>
                              <a className="inline-flex items-center gap-4 text-navy transition hover:text-blue-700" href={contact.whatsappHref}>
                                <span className="grid h-10 w-10 place-items-center rounded-full border border-[#d8e7f7] bg-white text-navy">
                                  <Phone className="h-5 w-5" strokeWidth={2} />
                                </span>
                                {contact.phone}
                              </a>
                            </div>
                            <a className="group/catalog inline-flex h-12 shrink-0 items-center gap-3 rounded-[14px] bg-blue-700 px-6 text-sm font-black text-white shadow-[0_14px_30px_rgba(37,99,235,0.18)] transition hover:-translate-y-0.5 hover:bg-blue-800 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-500/15 2xl:text-base" href={productNavGroups[0]?.href ?? "/products"}>
                              {chromeUi.viewProductCatalogLabel}
                              <span className="grid h-7 w-7 place-items-center rounded-full border border-white/60 text-white transition group-hover/catalog:translate-x-0.5">
                                <ArrowRight className="h-4 w-4 -rotate-45" strokeWidth={2.1} />
                              </span>
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                );
              }

              return (
                <a
                  className={navLinkClassName}
                  href={item.href}
                  key={item.label}
                >
                  {item.label}
                  {item.hasMenu && <ChevronDown className="h-3.5 w-3.5 transition group-hover:text-gold" />}
                </a>
              );
            })}
          </nav>
          <div className="flex items-center gap-2 md:gap-3 xl:gap-2 2xl:gap-3">
            <LanguageMenu ariaLabel={chromeUi.languageMenuAriaLabel} options={languageOptions} />
            <MobileNavMenu chromeUi={chromeUi} navItems={navItems} productNavGroups={productNavGroups} quotePath={contact.quotePath} />
            <Button nativeButton={false} render={<a href={contact.quotePath} />} className="group hidden h-12 min-w-[6.75rem] rounded-full bg-navy px-4 text-sm font-black text-white shadow-[0_16px_34px_rgba(8,35,74,0.24)] hover:-translate-y-0.5 hover:bg-navy xl:inline-flex 2xl:min-w-[11.75rem] 2xl:px-5">
              <span className="2xl:hidden">{chromeUi.shortRequestQuoteLabel}</span>
              <span className="hidden 2xl:inline">{chromeUi.requestQuoteLabel}</span>
              <span className="grid h-6 w-6 place-items-center rounded-full bg-white/12 text-white transition md:h-7 md:w-7">
                <Check data-icon="inline-end" />
              </span>
            </Button>
          </div>
        </div>
      </div>
    </header>
  );
}
