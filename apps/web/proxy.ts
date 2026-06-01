import type { NextRequest } from "next/server";
import { NextResponse } from "next/server";

import { defaultLocale, localeFromPathname, stripLocaleFromPathname } from "@/lib/i18n";

const localeHeader = "x-boblift-locale";
const localeCookie = "boblift-locale";

export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  if (shouldSkipI18n(pathname)) {
    return NextResponse.next();
  }

  const prefixedLocale = localeFromPathname(pathname);
  const requestHeaders = new Headers(request.headers);
  const locale = prefixedLocale ?? defaultLocale;

  requestHeaders.set(localeHeader, locale);

  if (!prefixedLocale) {
    return NextResponse.next({ request: { headers: requestHeaders } });
  }

  const rewriteUrl = request.nextUrl.clone();
  rewriteUrl.pathname = stripLocaleFromPathname(pathname);

  const response = NextResponse.rewrite(rewriteUrl, { request: { headers: requestHeaders } });
  response.cookies.set(localeCookie, locale, {
    maxAge: 60 * 60 * 24 * 365,
    path: "/",
    sameSite: "lax",
  });
  return response;
}

function shouldSkipI18n(pathname: string) {
  return (
    pathname.startsWith("/admin") ||
    pathname.startsWith("/api") ||
    pathname.startsWith("/_next") ||
    pathname.startsWith("/images") ||
    pathname.startsWith("/flags") ||
    pathname === "/favicon.ico" ||
    pathname === "/robots.txt" ||
    pathname === "/sitemap.xml"
  );
}

export const config = {
  matcher: ["/((?!admin|api|_next|images|flags|favicon.ico|robots.txt|sitemap.xml|.*\\.(?:png|jpg|jpeg|webp|svg|ico|css|js|woff|woff2)$).*)"],
};
