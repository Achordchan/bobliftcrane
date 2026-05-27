"use client";

import { useEffect, useState } from "react";
import { Menu } from "@base-ui/react/menu";
import { Check, ChevronDown } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

const languages = [
  { locale: "en", label: "English", flag: "/flags/us.svg", market: "United States / Global" },
  { locale: "es", label: "Español", flag: "/flags/es.svg", market: "Spain / LATAM" },
  { locale: "ru", label: "Русский", flag: "/flags/ru.svg", market: "Russia / CIS" },
  { locale: "ar", label: "العربية", flag: "/flags/sa.svg", market: "MENA / Arabic" },
] as const;

type Language = (typeof languages)[number];

const STORAGE_KEY = "boblift-language";

export function LanguageMenu() {
  const [current, setCurrent] = useState<Language>(languages[0]);

  useEffect(() => {
    const savedLocale = window.localStorage.getItem(STORAGE_KEY);
    const savedLanguage = languages.find((language) => language.locale === savedLocale);

    if (savedLanguage) {
      setCurrent(savedLanguage);
    }
  }, []);

  function selectLanguage(language: Language) {
    setCurrent(language);
    window.localStorage.setItem(STORAGE_KEY, language.locale);
  }

  return (
    <Menu.Root modal={false}>
      <Menu.Trigger
        aria-label="Change language"
        className={({ open }) =>
          cn(
            "group flex h-11 w-11 items-center justify-center rounded-full border border-line bg-white p-0 text-left shadow-[0_10px_28px_rgba(8,35,74,0.07)] transition duration-300 hover:-translate-y-0.5 hover:border-gold/50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gold/20 md:h-12 md:w-[164px] md:justify-start md:gap-3 md:px-4",
            open && "-translate-y-0.5 border-gold/60 shadow-[0_18px_38px_rgba(8,35,74,0.13)]",
          )
        }
        type="button"
      >
        <span
          aria-label={`${current.label} flag`}
          className="block size-7 shrink-0 overflow-hidden rounded-full bg-[#eef4fb] bg-cover bg-center shadow-inner ring-1 ring-line transition group-hover:bg-white md:size-8"
          style={{ backgroundImage: `url(${current.flag})` }}
        />
        <strong className="hidden min-w-0 flex-1 whitespace-nowrap text-sm font-black text-ink md:block">{current.label}</strong>
        <ChevronDown className="hidden size-4 text-slate-400 transition duration-300 group-hover:text-gold group-data-[popup-open]:rotate-180 group-data-[popup-open]:text-gold md:block" />
      </Menu.Trigger>

      <Menu.Portal>
        <Menu.Positioner align="end" side="bottom" sideOffset={14} className="z-50 outline-none">
          <Menu.Popup className="w-[min(320px,calc(100vw-32px))] origin-[var(--transform-origin)] overflow-hidden rounded-[28px] border border-line bg-[linear-gradient(145deg,#ffffff_0%,#f6f9ff_100%)] p-2 shadow-[0_28px_80px_rgba(8,35,74,0.18)] outline-none transition duration-200 data-[ending-style]:scale-95 data-[ending-style]:opacity-0 data-[starting-style]:scale-95 data-[starting-style]:opacity-0">
            <div className="rounded-[22px] bg-navy px-5 py-4 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.14)]">
              <p className="text-[10px] font-black uppercase tracking-[0.24em] text-gold">Region</p>
              <p className="mt-1 text-sm font-black">Select Language</p>
            </div>

            <div className="mt-2 flex flex-col gap-1">
              {languages.map((language) => {
                const selected = current.locale === language.locale;

                return (
                  <Menu.Item
                    aria-current={selected ? "true" : undefined}
                    className={cn(
                      "group/item flex cursor-pointer items-center gap-3 rounded-[20px] px-3 py-3 text-left outline-none transition duration-200 data-[highlighted]:bg-white data-[highlighted]:shadow-[0_12px_34px_rgba(8,35,74,0.08)]",
                      selected && "bg-white shadow-[0_10px_28px_rgba(8,35,74,0.07)]",
                    )}
                    key={language.locale}
                    label={language.label}
                    onClick={() => selectLanguage(language)}
                  >
                    <span
                      aria-label={`${language.label} flag`}
                      className="block size-11 shrink-0 overflow-hidden rounded-full bg-[#edf4ff] bg-cover bg-center shadow-inner ring-1 ring-line transition group-data-[highlighted]/item:bg-white"
                      style={{ backgroundImage: `url(${language.flag})` }}
                    />
                    <span className="min-w-0 flex-1">
                      <span className="block text-sm font-black text-ink">{language.label}</span>
                      <span className="mt-0.5 block truncate text-xs font-semibold text-slate-500">{language.market}</span>
                    </span>
                    {selected ? (
                      <Badge className="h-6 rounded-full bg-gold px-2.5 text-[10px] font-black uppercase tracking-[0.12em] text-white">
                        Current
                      </Badge>
                    ) : (
                      <Check className="size-4 text-transparent transition group-data-[highlighted]/item:text-gold" />
                    )}
                  </Menu.Item>
                );
              })}
            </div>
          </Menu.Popup>
        </Menu.Positioner>
      </Menu.Portal>
    </Menu.Root>
  );
}
