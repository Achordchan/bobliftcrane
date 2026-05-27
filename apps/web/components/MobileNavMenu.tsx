"use client";

import { Menu as BaseMenu } from "@base-ui/react/menu";
import { ArrowRight, Check, Menu as MenuIcon } from "lucide-react";

import { navItems } from "@/lib/home-data";
import { cn } from "@/lib/utils";

export function MobileNavMenu() {
  return (
    <BaseMenu.Root modal={false}>
      <BaseMenu.Trigger
        aria-label="Open mobile navigation"
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
          <BaseMenu.Popup className="w-[min(340px,calc(100vw-32px))] origin-[var(--transform-origin)] overflow-hidden rounded-[28px] border border-line bg-white p-2 shadow-[0_28px_80px_rgba(8,35,74,0.18)] outline-none transition duration-200 data-[ending-style]:scale-95 data-[ending-style]:opacity-0 data-[starting-style]:scale-95 data-[starting-style]:opacity-0">
            <div className="rounded-[22px] bg-[linear-gradient(135deg,#071f42_0%,#0b3a78_100%)] px-5 py-4 text-white">
              <p className="text-[10px] font-black uppercase tracking-[0.24em] text-gold">Navigation</p>
              <p className="mt-1 text-lg font-black">BOB-LIFT Menu</p>
            </div>

            <div className="mt-2 grid gap-1">
              {navItems.map((item) => (
                <BaseMenu.Item
                  className="group flex cursor-pointer items-center justify-between rounded-[18px] px-4 py-3 text-sm font-black text-ink outline-none transition duration-200 data-[highlighted]:bg-[#f7fbff] data-[highlighted]:text-blue-700"
                  key={item.label}
                  label={item.label}
                  render={<a href={item.href} />}
                >
                  {item.label}
                  <ArrowRight className="h-4 w-4 text-slate-300 transition group-data-[highlighted]:translate-x-0.5 group-data-[highlighted]:text-blue-600" />
                </BaseMenu.Item>
              ))}
            </div>

            <BaseMenu.Item
              className="mt-2 flex cursor-pointer items-center justify-center gap-2 rounded-[20px] bg-navy px-5 py-3 text-sm font-black text-white outline-none shadow-[0_16px_34px_rgba(8,35,74,0.2)] transition duration-200 data-[highlighted]:bg-[#0b2d5b]"
              label="Request Quote"
              render={<a href="/quote" />}
            >
              Request Quote
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
