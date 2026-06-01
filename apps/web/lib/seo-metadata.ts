import type { Metadata } from "next";

import { localizedPath } from "@/lib/i18n";
import { readyLanguageAlternates } from "@/lib/seo-language-alternates";
import { getRequestLocale } from "@/lib/server-locale";

export async function localizedSeoMetadata({
  title,
  description,
  canonicalPath,
}: {
  title: string;
  description: string;
  canonicalPath: string;
}): Promise<Metadata> {
  const locale = await getRequestLocale();
  const normalizedCanonicalPath = canonicalPath.startsWith("/") ? canonicalPath : `/${canonicalPath}`;

  return {
    title,
    description,
    alternates: {
      canonical: localizedPath(normalizedCanonicalPath, locale),
      languages: await readyLanguageAlternates(normalizedCanonicalPath),
    },
  };
}
