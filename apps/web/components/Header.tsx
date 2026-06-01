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
    <header id="top" className="relative z-20">
      <div className="bg-navy px-4 py-3 text-center text-[11px] font-semibold leading-5 text-white/85 md:text-xs">
        <span className="text-gold">{topBar.highlight}</span> {topBar.text}
        <a className="ml-3 text-gold md:ml-5" href={topBar.linkHref}>{topBar.linkLabel}</a>
      </div>
      <div className="border-b border-line bg-white/96 shadow-[0_10px_34px_rgba(8,35,74,0.05)] backdrop-blur">
        <div className="page-shell flex h-[78px] items-center justify-between gap-3 lg:h-[92px] lg:gap-8">
          <Logo
            height={branding.logoHeight}
            homeHref={navItems.find((item) => item.href === "/" || /^\/[a-z]{2}$/.test(item.href))?.href ?? "/"}
            homeLabel={chromeUi.logoHomeLabel}
            imageAlt={chromeUi.logoImageAlt}
            src={branding.logoSrc}
            width={branding.logoWidth}
          />
          <nav className="hidden items-center rounded-full border border-line bg-[#f7faff]/90 p-1.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.9)] lg:flex" aria-label={chromeUi.mainNavigationLabel}>
            {navItems.map((item) => {
              const active = item.href === "/" ? pathname === "/" : pathname.startsWith(item.href);
              const navLinkClassName = `group flex h-10 items-center gap-1.5 rounded-full px-4 text-sm font-black transition duration-300 ${active ? "bg-white text-ink shadow-[0_8px_22px_rgba(8,35,74,0.08)]" : "text-slate-600 hover:bg-white hover:text-ink hover:shadow-[0_8px_22px_rgba(8,35,74,0.06)]"}`;

              if (item.hasMenu && item.href.startsWith("/products")) {
                return (
                  <div className="group/products relative" key={item.label} onBlur={scheduleCloseProductsMenu} onFocus={openProductsMenu} onMouseEnter={openProductsMenu} onMouseLeave={scheduleCloseProductsMenu}>
                    <a aria-haspopup="true" className={navLinkClassName} href={item.href}>
                      {item.label}
                      <ChevronDown className={cn("h-3.5 w-3.5 transition group-hover/products:rotate-180 group-hover/products:text-gold", productsMenuOpen && "rotate-180 text-gold")} />
                    </a>
                    <div
                      className={cn(
                        "fixed left-1/2 top-[112px] z-50 w-[min(1860px,calc(100vw-96px))] -translate-x-1/2 pt-5 transition duration-200 ease-out",
                        productsMenuOpen ? "pointer-events-auto visible translate-y-0 opacity-100" : "pointer-events-none invisible translate-y-3 opacity-0",
                      )}
                      onMouseEnter={openProductsMenu}
                      onMouseLeave={scheduleCloseProductsMenu}
                    >
                      <div className="relative overflow-hidden rounded-[38px] border border-[#d8e7f7] bg-white shadow-[0_34px_96px_rgba(8,35,74,0.14)] ring-1 ring-white/80" data-product-nav="desktop">
                        <div className="pointer-events-none absolute inset-0 opacity-[0.075]">
                          <Image className="object-cover object-center saturate-75 blur-[1px]" src={branding.productMenuBackgroundImage} alt="" fill sizes="1900px" />
                        </div>
                        <div className="pointer-events-none absolute inset-0 bg-[linear-gradient(110deg,rgba(255,255,255,0.98)_0%,rgba(255,255,255,0.94)_36%,rgba(247,251,255,0.9)_68%,rgba(255,255,255,0.96)_100%)]" />
                        <div className="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-blue-200/22 blur-3xl" />
                        <div className="pointer-events-none absolute -bottom-28 right-8 h-80 w-80 rounded-full bg-sky-100/70 blur-3xl" />

                        <div className="relative grid grid-cols-2 items-start gap-y-8 p-7 xl:grid-cols-3 xl:gap-0 xl:p-9 2xl:p-10">
                          {productNavGroups.map((group, groupIndex) => {
                            const groupIconName = normalizeProductIconName(group.iconName, "attachments");

                            return (
                              <div
                                className={cn(
                                  "relative px-5 py-4 xl:px-8 xl:py-5",
                                  groupIndex < 2 && "xl:border-r xl:border-[#dbe8f5]",
                                  groupIndex === 2 && "col-span-2 xl:col-span-1",
                                )}
                                key={`${group.title}-${group.href}-${groupIndex}`}
                              >
                                <a className="group/title flex items-center gap-5" href={group.href}>
                                  <span className="grid h-[92px] w-[92px] shrink-0 place-items-center rounded-[28px] bg-[#eef6ff] text-blue-700 transition group-hover/title:bg-blue-50">
                                    <ProductLineIcon className="h-[66px] w-[66px]" name={groupIconName} />
                                  </span>
                                  <span>
                                    <span className="block text-[24px] font-black leading-tight tracking-[-0.04em] text-navy transition group-hover/title:text-blue-700 2xl:text-[26px]">
                                      {group.title}
                                    </span>
                                    <span className="mt-2 block text-base font-semibold leading-6 text-slate-500">
                                      {group.description}
                                    </span>
                                  </span>
                                </a>

                                <div className="mt-7 grid gap-3">
                                  {group.links.map((link, linkIndex) => {
                                    const linkIconName = normalizeProductIconName(link.iconName, "truck-crane");
                                    const highlighted = groupIndex === 0 && linkIndex === 0;

                                    return (
                                      <a
                                        className={cn(
                                          "group/item relative flex min-h-[88px] items-center gap-5 rounded-[22px] border border-transparent bg-transparent px-4 py-3 text-navy transition duration-200 ease-out before:absolute before:left-0 before:top-4 before:h-[calc(100%-32px)] before:w-1 before:rounded-r-full before:bg-transparent before:transition hover:border-blue-100 hover:bg-[#f2f8ff] hover:shadow-[0_14px_28px_rgba(28,115,223,0.08)] hover:before:bg-blue-500 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-500/15",
                                          highlighted && "border-blue-100 bg-[#f2f8ff] shadow-[0_14px_28px_rgba(37,99,235,0.08)] before:bg-blue-600",
                                        )}
                                        href={link.href}
                                        key={`${link.label}-${link.href}-${linkIndex}`}
                                      >
                                        <span
                                          className={cn(
                                            "grid h-[72px] w-[72px] shrink-0 place-items-center rounded-[22px] bg-[#f3f7fc] text-navy/75 transition duration-200 group-hover/item:bg-white",
                                            highlighted && "bg-white text-blue-700",
                                          )}
                                        >
                                          <ProductLineIcon className="h-[54px] w-[54px]" name={linkIconName} />
                                        </span>
                                        <span className="min-w-0 flex-1 text-[18px] font-black leading-[1.16] tracking-[-0.025em] text-navy 2xl:text-[20px]">
                                          {link.label}
                                        </span>
                                        <ChevronDown className="h-5 w-5 shrink-0 -rotate-90 text-blue-600 transition duration-200 group-hover/item:translate-x-1" strokeWidth={2.1} />
                                      </a>
                                    );
                                  })}
                                </div>
                              </div>
                            );
                          })}
                        </div>

                        <div className="relative px-7 pb-7 xl:px-9 xl:pb-9">
                          <div className="flex flex-wrap items-center justify-between gap-5 rounded-[24px] bg-[#f4f8fd]/90 px-6 py-5">
                            <div className="flex min-w-[300px] items-center gap-5">
                              <span className="grid h-[72px] w-[72px] shrink-0 place-items-center rounded-[22px] bg-white text-navy">
                                <ProductLineIcon className="h-12 w-12" name="catalog" />
                              </span>
                              <span>
                                <span className="block text-[20px] font-black leading-tight text-navy">{chromeUi.productCatalogTitle}</span>
                                <span className="mt-1 block text-sm font-semibold text-slate-500">{chromeUi.productCatalogDescription}</span>
                              </span>
                            </div>
                            <div className="flex flex-wrap items-center gap-x-7 gap-y-3 text-base font-black text-navy">
                              <a className="inline-flex items-center gap-4 text-navy transition hover:text-blue-700" href={`mailto:${contact.email}`}>
                                <span className="grid h-12 w-12 place-items-center rounded-full border border-[#d8e7f7] bg-white text-navy">
                                  <Mail className="h-5 w-5" strokeWidth={2} />
                                </span>
                                {contact.email}
                              </a>
                              <a className="inline-flex items-center gap-4 text-navy transition hover:text-blue-700" href={contact.whatsappHref}>
                                <span className="grid h-12 w-12 place-items-center rounded-full border border-[#d8e7f7] bg-white text-navy">
                                  <Phone className="h-5 w-5" strokeWidth={2} />
                                </span>
                                {contact.phone}
                              </a>
                            </div>
                            <a className="group/catalog inline-flex h-14 shrink-0 items-center gap-4 rounded-[16px] bg-blue-700 px-7 text-base font-black text-white shadow-[0_14px_30px_rgba(37,99,235,0.18)] transition hover:-translate-y-0.5 hover:bg-blue-800 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-500/15" href={productNavGroups[0]?.href ?? "/products"}>
                              {chromeUi.viewProductCatalogLabel}
                              <span className="grid h-8 w-8 place-items-center rounded-full border border-white/60 text-white transition group-hover/catalog:translate-x-0.5">
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
          <div className="flex items-center gap-2 md:gap-3">
            <LanguageMenu ariaLabel={chromeUi.languageMenuAriaLabel} options={languageOptions} />
            <MobileNavMenu chromeUi={chromeUi} navItems={navItems} productNavGroups={productNavGroups} quotePath={contact.quotePath} />
            <Button nativeButton={false} render={<a href={contact.quotePath} />} className="group hidden h-12 min-w-[11.75rem] rounded-full bg-navy px-5 text-sm font-black text-white shadow-[0_16px_34px_rgba(8,35,74,0.24)] hover:-translate-y-0.5 hover:bg-navy xl:inline-flex">
              <span>{chromeUi.requestQuoteLabel}</span>
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
