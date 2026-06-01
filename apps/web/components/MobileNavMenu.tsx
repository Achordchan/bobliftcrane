"use client";

import { Menu as BaseMenu } from "@base-ui/react/menu";
import { ArrowRight, Check, Menu as MenuIcon } from "lucide-react";

import { navItems as defaultNavItems, productNavGroups as defaultProductNavGroups, type NavItem, type ProductNavGroup } from "@/lib/home-data";
import { defaultChromeUiContent, type ChromeUiContent } from "@/lib/site-ui";
import { cn } from "@/lib/utils";

export function MobileNavMenu({
  navItems = defaultNavItems,
  productNavGroups = defaultProductNavGroups,
  quotePath = "/quote",
  chromeUi = defaultChromeUiContent,
}: {
  navItems?: NavItem[];
  productNavGroups?: ProductNavGroup[];
  quotePath?: string;
  chromeUi?: ChromeUiContent;
}) {
  return (
    <BaseMenu.Root modal={false}>
      <BaseMenu.Trigger
        aria-label={chromeUi.mobileMenuAriaLabel}
        className={({ open }) =>
          cn(
            "grid h-11 w-11 place-items-center rounded-full border border-navy/10 bg-navy text-white shadow-[0_16px_34px_rgba(8,35,74,0.22)] transition duration-300 hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-blue-500/20 lg:hidden",
            open && "-translate-y-0.5 bg-[#0b2d5b]",
          )
        }
        type="button"
      >
        <MenuIcon className="h-5 w-5" strokeWidth={2.4} />
      </BaseMenu.Trigger>

      <BaseMenu.Portal>
        <BaseMenu.Positioner align="end" side="bottom" sideOffset={14} className="z-50 outline-none">
          <BaseMenu.Popup className="max-h-[min(78vh,680px)] w-[min(340px,calc(100vw-32px))] origin-[var(--transform-origin)] overflow-y-auto overscroll-contain rounded-[28px] border border-line bg-white p-2 shadow-[0_28px_80px_rgba(8,35,74,0.18)] outline-none transition duration-200 data-[ending-style]:scale-95 data-[ending-style]:opacity-0 data-[starting-style]:scale-95 data-[starting-style]:opacity-0">
            <div className="rounded-[22px] bg-[linear-gradient(135deg,#071f42_0%,#0b3a78_100%)] px-5 py-4 text-white">
              <p className="text-[10px] font-black uppercase tracking-[0.24em] text-gold">{chromeUi.mobileMenuEyebrow}</p>
              <p className="mt-1 text-lg font-black">{chromeUi.mobileMenuTitle}</p>
            </div>

            <div className="mt-2 grid gap-1">
              {navItems.map((item) => (
                <div key={item.label}>
                  <BaseMenu.Item
                    className="group flex cursor-pointer items-center justify-between rounded-[18px] px-4 py-3 text-sm font-black text-ink outline-none transition duration-200 data-[highlighted]:bg-[#f7fbff] data-[highlighted]:text-blue-700"
                    label={item.label}
                    render={<a href={item.href} />}
                  >
                    {item.label}
                    <ArrowRight className="h-4 w-4 text-slate-300 transition group-data-[highlighted]:translate-x-0.5 group-data-[highlighted]:text-blue-600" />
                  </BaseMenu.Item>
                  {item.hasMenu && item.href.startsWith("/products") && (
                    <div className="mx-2 mb-2 rounded-[20px] border border-line bg-[#f8fbff] p-3" data-product-nav="mobile">
                      <p className="px-2 pb-2 text-[10px] font-black uppercase tracking-[0.18em] text-gold">{chromeUi.mobileProductCategoriesLabel}</p>
                      <div className="grid gap-3">
                        {productNavGroups.map((group, groupIndex) => (
                          <div key={`${group.title}-${group.href}-${groupIndex}`}>
                            <a className="block rounded-2xl bg-white px-3 py-2 text-xs font-black text-ink shadow-[0_8px_20px_rgba(8,35,74,0.05)]" href={group.href}>
                              {group.title}
                            </a>
                            <div className="mt-1 grid gap-1 pl-2">
                              {group.links.map((link, linkIndex) => (
                                <a className="rounded-xl px-3 py-1.5 text-[11px] font-bold leading-4 text-slate-500 hover:bg-white hover:text-blue-700" href={link.href} key={`${link.label}-${link.href}-${linkIndex}`}>
                                  {link.label}
                                </a>
                              ))}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              ))}
            </div>

            <BaseMenu.Item
              className="mt-2 flex cursor-pointer items-center justify-center gap-2 rounded-[20px] bg-navy px-5 py-3 text-sm font-black text-white outline-none shadow-[0_16px_34px_rgba(8,35,74,0.2)] transition duration-200 data-[highlighted]:bg-[#0b2d5b]"
              label={chromeUi.requestQuoteLabel}
              render={<a href={quotePath} />}
            >
              {chromeUi.requestQuoteLabel}
              <span className="grid h-6 w-6 place-items-center rounded-full bg-white/12">
                <Check className="h-4 w-4" />
              </span>
            </BaseMenu.Item>
          </BaseMenu.Popup>
        </BaseMenu.Positioner>
      </BaseMenu.Portal>
    </BaseMenu.Root>
  );
}
