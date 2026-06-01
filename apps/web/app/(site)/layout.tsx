import type { Metadata } from "next";
import "../globals.css";
import { Geist } from "next/font/google";
import { getPageUiContent } from "@/lib/cms-content";
import { rtlLocales } from "@/lib/i18n";
import { getRequestLocale } from "@/lib/server-locale";
import { cn } from "@/lib/utils";

const geist = Geist({ subsets: ["latin"], variable: "--font-sans" });

export async function generateMetadata(): Promise<Metadata> {
  const { seoFallbacks } = await getPageUiContent();
  return {
    title: seoFallbacks.siteTitle,
    description: seoFallbacks.siteDescription,
  };
}

export default async function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  const lang = await getRequestLocale();

  return (
    <html lang={lang} dir={rtlLocales.has(lang) ? "rtl" : "ltr"} className={cn("font-sans", geist.variable)}>
      <body>{children}</body>
    </html>
  );
}
