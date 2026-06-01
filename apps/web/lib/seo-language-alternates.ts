import config from "@payload-config";
import { getPayload } from "payload";

import { defaultLocale, isSupportedLocale, languageAlternates, type SiteLocale } from "@/lib/i18n";
import { defaultLanguageOptions } from "@/lib/site-ui";

type LanguageOptionRecord = {
  locale?: string | null;
  enabled?: boolean | null;
  translationStatus?: string | null;
  completionPercent?: number | null;
  sortOrder?: number | null;
};

const fallbackReadyLocales = defaultLanguageOptions
  .filter((item) => readyLocale(item.locale, item.enabled, item.translationStatus, item.completionPercent))
  .sort((a, b) => a.sortOrder - b.sortOrder)
  .map((item) => item.locale as SiteLocale);

export async function readyLanguageAlternates(path: string): Promise<Record<string, string>> {
  const locales = await getReadyLocales();
  return languageAlternates(path, locales);
}

export async function getReadyLocales(): Promise<SiteLocale[]> {
  try {
    const payload = await getPayload({ config });
    const settings = await payload.findGlobal({ slug: "site-settings", depth: 0, fallbackLocale: "en", locale: "en" });
    const languageOptions = Array.isArray((settings as { languageOptions?: unknown }).languageOptions)
      ? ((settings as { languageOptions: LanguageOptionRecord[] }).languageOptions)
      : [];
    const locales = languageOptions
      .filter((item) => readyLocale(item?.locale, item?.enabled, item?.translationStatus, item?.completionPercent))
      .sort((a, b) => (a.sortOrder ?? 100) - (b.sortOrder ?? 100))
      .map((item) => item.locale as SiteLocale);

    return withDefaultLocale(locales.length > 0 ? locales : fallbackReadyLocales);
  } catch {
    return withDefaultLocale(fallbackReadyLocales);
  }
}

function readyLocale(locale: string | null | undefined, enabled: boolean | null | undefined, status: string | null | undefined, completionPercent: number | null | undefined): boolean {
  if (!isSupportedLocale(locale) || enabled === false) return false;
  if (locale === defaultLocale) return true;
  return status === "ready" && typeof completionPercent === "number" && completionPercent >= 95;
}

function withDefaultLocale(locales: SiteLocale[]): SiteLocale[] {
  const unique = new Set<SiteLocale>([defaultLocale, ...locales]);
  return Array.from(unique);
}
