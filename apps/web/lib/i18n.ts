export const defaultLocale = "en";

export const supportedLocaleValues = [
  "en",
  "es",
  "ru",
  "ar",
  "pt",
  "fr",
  "de",
  "tr",
  "id",
  "vi",
  "th",
  "hi",
  "zh",
  "ko",
] as const;

export type SiteLocale = (typeof supportedLocaleValues)[number];

export const supportedLocales = new Set<string>(supportedLocaleValues);
export const rtlLocales = new Set<string>(["ar"]);

export function isSupportedLocale(value: string | null | undefined): value is SiteLocale {
  return Boolean(value && supportedLocales.has(value));
}

export function localeFromPathname(pathname: string): SiteLocale | null {
  const firstSegment = pathname.split("/").filter(Boolean)[0];
  return isSupportedLocale(firstSegment) ? firstSegment : null;
}

export function stripLocaleFromPathname(pathname: string): string {
  const normalizedPathname = pathname.startsWith("/") ? pathname : `/${pathname || ""}`;
  const locale = localeFromPathname(normalizedPathname);
  if (!locale) return normalizedPathname || "/";

  const stripped = normalizedPathname.replace(new RegExp(`^/${locale}(?=/|$)`), "") || "/";
  return stripped.startsWith("/") ? stripped : `/${stripped}`;
}

export function localizedPath(path: string, locale: string | null | undefined): string {
  const [pathAndQuery, hash = ""] = path.split("#", 2);
  const [pathname, query = ""] = pathAndQuery.split("?", 2);
  const cleanPathname = stripLocaleFromPathname(pathname || "/");
  const prefix = isSupportedLocale(locale) && locale !== defaultLocale ? `/${locale}` : "";
  const nextPath = `${prefix}${cleanPathname === "/" ? "" : cleanPathname}` || "/";
  return `${nextPath}${query ? `?${query}` : ""}${hash ? `#${hash}` : ""}`;
}

export function languageAlternates(path: string, locales: readonly SiteLocale[] = supportedLocaleValues): Record<string, string> {
  return Object.fromEntries(locales.map((locale) => [locale, localizedPath(path, locale)]));
}
