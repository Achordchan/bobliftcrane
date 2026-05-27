"use client";

import { Check, ChevronDown } from "lucide-react";
import { usePathname } from "next/navigation";

import { Button } from "@/components/ui/button";
import { navItems } from "@/lib/home-data";
import { LanguageMenu } from "./LanguageMenu";
import { Logo } from "./Logo";
import { MobileNavMenu } from "./MobileNavMenu";

export function Header() {
  const pathname = usePathname();

  return (
    <header id="top" className="relative z-20">
      <div className="bg-navy px-4 py-3 text-center text-[11px] font-semibold leading-5 text-white/85 md:text-xs">
        <span className="text-gold">Global Lifting Solutions.</span> Exported to 80+ Countries Worldwide.
        <a className="ml-3 text-gold md:ml-5" href="/about">Learn More +</a>
      </div>
      <div className="border-b border-line bg-white/96 shadow-[0_10px_34px_rgba(8,35,74,0.05)] backdrop-blur">
        <div className="page-shell flex h-[78px] items-center justify-between gap-3 lg:h-[92px] lg:gap-8">
          <Logo />
          <nav className="hidden items-center rounded-full border border-line bg-[#f7faff]/90 p-1.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.9)] lg:flex" aria-label="Main navigation">
            {navItems.map((item) => {
              const active = item.href === "/" ? pathname === "/" : item.href !== "/#application" && pathname.startsWith(item.href);
              return (
                <a
                  className={`group flex h-10 items-center gap-1.5 rounded-full px-4 text-sm font-black transition duration-300 ${active ? "bg-white text-ink shadow-[0_8px_22px_rgba(8,35,74,0.08)]" : "text-slate-600 hover:bg-white hover:text-ink hover:shadow-[0_8px_22px_rgba(8,35,74,0.06)]"}`}
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
            <LanguageMenu />
            <MobileNavMenu />
            <Button nativeButton={false} render={<a href="/quote" />} className="group hidden h-12 w-[164px] rounded-full bg-navy px-4 text-sm font-black text-white shadow-[0_16px_34px_rgba(8,35,74,0.24)] hover:-translate-y-0.5 hover:bg-navy lg:inline-flex">
              <span>Request Quote</span>
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
