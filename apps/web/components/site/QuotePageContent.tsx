import { ArrowRight, FileText, MailCheck, MessageCircle } from "lucide-react";

import { getContactContent, getPageUiContent, getProductListingContent } from "@/lib/cms-content";
import { localizedPath } from "@/lib/i18n";
import { getRequestLocale } from "@/lib/server-locale";
import { PageHero } from "./PageHero";
import { QuoteForm } from "./QuoteForm";
import { SiteChrome } from "./SiteChrome";

const workflowIcons = {
  "file-text": FileText,
  "mail-check": MailCheck,
  "message-circle": MessageCircle,
};

export async function QuotePageContent() {
  const [contact, listingContent, pageUi, locale] = await Promise.all([getContactContent(), getProductListingContent(), getPageUiContent(), getRequestLocale()]);
  const ui = pageUi.quotePage;
  const productOptions = limitProductOptions(listingContent.products, pageUi.quoteForm.productOptionLimit).map((product) => ({
    key: product.id,
    label: product.name,
    value: product.name,
    slug: product.slug,
  }));

  return (
    <SiteChrome showFinalCta={false}>
      <PageHero label={ui.heroLabel} title={ui.heroTitle} description={ui.heroDescription} image={ui.heroImage} />

      <section className="page-shell grid grid-cols-1 gap-8 py-10 md:py-16 lg:grid-cols-[0.72fr_1.28fr] lg:gap-12">
        <aside className="rounded-[30px] border border-line bg-[linear-gradient(180deg,#071f42_0%,#0b3a78_100%)] p-6 text-white shadow-[0_34px_100px_rgba(8,35,74,0.16)] md:p-8 lg:sticky lg:top-8 lg:h-fit">
          <p className="text-[11px] font-black uppercase tracking-[0.24em] text-gold">{ui.workflowLabel}</p>
          <h2 className="mt-4 text-3xl font-black leading-tight tracking-[-0.04em] md:text-4xl">{ui.workflowTitle}</h2>
          <div className="mt-7 grid gap-4">
            {ui.workflowItems.map(({ iconName, title, text }) => {
              const Icon = workflowIcons[iconName] ?? FileText;
              return (
                <div className="rounded-2xl border border-white/10 bg-white/8 p-4" key={title}>
                  <Icon className="h-5 w-5 text-gold" />
                  <h3 className="mt-3 text-base font-black">{title}</h3>
                  <p className="mt-1 text-sm font-semibold leading-6 text-white/70">{text}</p>
                </div>
              );
            })}
          </div>
          <a className="mt-7 inline-flex items-center gap-2 text-sm font-black text-gold" href={localizedPath(ui.productLinkHref, locale)}>{ui.productLinkLabel} <ArrowRight className="h-4 w-4" /></a>
        </aside>

        <QuoteForm contact={contact} productOptions={productOptions} ui={pageUi.quoteForm} />
      </section>
    </SiteChrome>
  );
}

function limitProductOptions<T>(products: T[], limit: number) {
  if (!Number.isInteger(limit) || limit <= 0) return products;
  return products.slice(0, limit);
}
