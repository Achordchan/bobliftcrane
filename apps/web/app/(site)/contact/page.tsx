import { ContactPageContent } from "@/components/site/ContactPageContent";
import { getPageUiContent } from "@/lib/cms-content";
import { localizedSeoMetadata } from "@/lib/seo-metadata";

export const dynamic = "force-dynamic";

export async function generateMetadata() {
  const ui = (await getPageUiContent()).contactPage;
  return localizedSeoMetadata({ title: ui.metaTitle, description: ui.metaDescription, canonicalPath: ui.canonicalPath });
}

export default async function ContactPage() {
  return <ContactPageContent />;
}
