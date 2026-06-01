import { cookies, headers } from "next/headers";
import { cache } from "react";

import { defaultLocale, isSupportedLocale, type SiteLocale } from "@/lib/i18n";
import { getReadyLocales } from "@/lib/seo-language-alternates";

const localeHeader = "x-boblift-locale";
const localeCookie = "boblift-locale";

export const getRequestLocale = cache(async (): Promise<SiteLocale> => {
  const requestedLocale = await getRequestedLocale();
  if (requestedLocale === defaultLocale) return defaultLocale;

  try {
    const readyLocales = await getReadyLocales();
    return readyLocales.includes(requestedLocale) ? requestedLocale : defaultLocale;
  } catch {
    return defaultLocale;
  }
});

async function getRequestedLocale(): Promise<SiteLocale> {
  try {
    const headerStore = await headers();
    const locale = headerStore.get(localeHeader);
    if (isSupportedLocale(locale)) return locale;
  } catch {
    // Static build paths do not always have a request header store.
  }

  try {
    const cookieStore = await cookies();
    const locale = cookieStore.get(localeCookie)?.value;
    if (isSupportedLocale(locale)) return locale;
  } catch {
    // Cookie access can fail during static analysis; fall back to English.
  }

  return defaultLocale;
}
