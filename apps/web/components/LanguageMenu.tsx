"use client";

import { useEffect, useMemo, useState } from "react";
import { Menu } from "@base-ui/react/menu";
import { Check } from "lucide-react";

import { localeFromPathname, localizedPath } from "@/lib/i18n";
import { defaultChromeUiContent, defaultLanguageOptions, type SiteLanguageOption } from "@/lib/site-ui";
import { cn } from "@/lib/utils";

const STORAGE_KEY = "boblift-language";
const COOKIE_KEY = "boblift-locale";

export function LanguageMenu({
  ariaLabel = defaultChromeUiContent.languageMenuAriaLabel,
  options = defaultLanguageOptions,
}: {
  ariaLabel?: string;
  options?: SiteLanguageOption[];
}) {
  const languages = useMemo(() => options.filter((language) => language.enabled !== false), [options]);
  const fallbackLanguage = languages[0] ?? defaultLanguageOptions[0];
  const [current, setCurrent] = useState<SiteLanguageOption>(fallbackLanguage);

  useEffect(() => {
    const savedLocale = localeFromPathname(window.location.pathname) || readCookie(COOKIE_KEY) || window.localStorage.getItem(STORAGE_KEY);
    const savedLanguage = languages.find((language) => language.locale === savedLocale);
    setCurrent(savedLanguage ?? fallbackLanguage);
  }, [fallbackLanguage, languages]);

  function selectLanguage(language: SiteLanguageOption) {
    setCurrent(language);
    window.localStorage.setItem(STORAGE_KEY, language.locale);
    document.cookie = `${COOKIE_KEY}=${language.locale}; path=/; max-age=31536000; SameSite=Lax`;
    const nextPath = localizedPath(`${window.location.pathname}${window.location.search}${window.location.hash}`, language.locale);
    window.location.assign(nextPath);
  }

  return (
    <Menu.Root modal={false}>
      <Menu.Trigger
        aria-label={ariaLabel}
        dir={current.direction}
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
      </Menu.Trigger>

      <Menu.Portal>
        <Menu.Positioner align="end" side="bottom" sideOffset={14} className="z-50 outline-none">
          <Menu.Popup className="w-[min(640px,calc(100vw-32px))] origin-[var(--transform-origin)] overflow-hidden rounded-[28px] border border-line bg-[linear-gradient(145deg,#ffffff_0%,#f6f9ff_100%)] p-2 shadow-[0_28px_80px_rgba(8,35,74,0.18)] outline-none transition duration-200 data-[ending-style]:scale-95 data-[ending-style]:opacity-0 data-[starting-style]:scale-95 data-[starting-style]:opacity-0">
            <div className="grid grid-cols-1 gap-1 sm:grid-cols-2">
              {languages.map((language) => {
                const selected = current.locale === language.locale;

                return (
                  <Menu.Item
                    aria-current={selected ? "true" : undefined}
                    className={cn(
                      "group/item flex cursor-pointer items-center gap-3 rounded-[20px] px-3 py-3 text-left outline-none transition duration-200 data-[highlighted]:bg-white data-[highlighted]:shadow-[0_12px_34px_rgba(8,35,74,0.08)]",
                      selected && "bg-white shadow-[0_10px_28px_rgba(8,35,74,0.07)] ring-1 ring-gold/35",
                    )}
                    key={language.locale}
                    label={language.label}
                    dir={language.direction}
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
                    <Check className={cn("size-4 transition group-data-[highlighted]/item:text-gold", selected ? "text-gold" : "text-transparent")} />
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

function readCookie(name: string) {
  return document.cookie
    .split("; ")
    .find((row) => row.startsWith(`${name}=`))
    ?.split("=")[1];
}
