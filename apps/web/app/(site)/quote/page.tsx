import { QuotePageContent } from "@/components/site/QuotePageContent";
import { getPageUiContent } from "@/lib/cms-content";
import { localizedSeoMetadata } from "@/lib/seo-metadata";

export const dynamic = "force-dynamic";

export async function generateMetadata() {
  const ui = (await getPageUiContent()).quotePage;
  return localizedSeoMetadata({ title: ui.metaTitle, description: ui.metaDescription, canonicalPath: ui.canonicalPath });
}

export default async function QuotePage() {
  return <QuotePageContent />;
}
