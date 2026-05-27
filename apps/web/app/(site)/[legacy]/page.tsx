import { notFound, redirect } from "next/navigation";

import { getProductEntryByLegacySegment, productDetailPath } from "@/lib/product-catalog";
import { getArticleByLegacySegment } from "@/lib/site-content";

type PageProps = { params: Promise<{ legacy: string }> };

export default async function LegacyRoutePage({ params }: PageProps) {
  const { legacy } = await params;

  if (legacy === "message.html" || legacy === "contact-us" || legacy === "contact-us.html") {
    redirect("/quote");
  }

  const productEntry = getProductEntryByLegacySegment(legacy);
  if (productEntry) redirect(productDetailPath(productEntry.product));

  const article = getArticleByLegacySegment(legacy);
  if (article) redirect(`/news/${article.slug}`);

  notFound();
}
