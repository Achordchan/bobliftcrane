import Image from "next/image";
import { Mail, MapPin, MessageCircle, Phone } from "lucide-react";

import { contact as defaultContactSettings } from "@/lib/contact";
import { getContactContent, getPageUiContent, getProductListingContent } from "@/lib/cms-content";
import { PageHero } from "./PageHero";
import { QuoteForm } from "./QuoteForm";
import { SiteChrome } from "./SiteChrome";

export async function ContactPageContent() {
  const [contact, listingContent, pageUi] = await Promise.all([getContactContent(), getProductListingContent(), getPageUiContent()]);
  const safeContact = contact ?? defaultContactSettings;
  const ui = pageUi.contactPage;
  const productOptions = limitProductOptions(listingContent.products, pageUi.quoteForm.productOptionLimit).map((product) => ({
    key: product.id,
    label: product.name,
    value: product.name,
    slug: product.slug,
  }));
  const contactCards = [
    { label: ui.cardLabels.email, value: safeContact.email, href: `mailto:${safeContact.email}`, icon: Mail },
    { label: ui.cardLabels.phone, value: safeContact.phone, href: safeContact.phoneHref, icon: Phone },
    { label: ui.cardLabels.whatsapp, value: ui.whatsappValue, href: safeContact.whatsappHref, icon: MessageCircle, external: true },
    { label: ui.cardLabels.address, value: safeContact.address, href: "#map", icon: MapPin },
  ];

  return (
    <SiteChrome showFinalCta={false}>
      <PageHero label={ui.heroLabel} title={ui.heroTitle} description={ui.heroDescription} image={ui.heroImage} />

      <section className="page-shell grid grid-cols-1 gap-8 py-10 md:py-16 lg:grid-cols-[0.82fr_1.18fr] lg:gap-12">
        <div>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {contactCards.map(({ label, value, href, icon: Icon, external }) => (
              <a className="card-hover rounded-[26px] border border-line bg-white p-5 shadow-card" href={href} key={label} rel={external ? "noreferrer" : undefined} target={external ? "_blank" : undefined}>
                <span className="grid h-12 w-12 place-items-center rounded-2xl bg-navy text-white"><Icon className="h-5 w-5" /></span>
                <p className="mt-5 text-xs font-black uppercase tracking-[0.18em] text-slate-400">{label}</p>
                <p className="mt-2 break-words text-lg font-black text-ink">{value}</p>
              </a>
            ))}
          </div>

          <div id="map" className="mt-5 overflow-hidden rounded-[28px] border border-line bg-[#f8fbff] p-5 shadow-card">
            <p className="section-label mb-3">{ui.weChatTitle}</p>
            <div className="grid items-center gap-4 sm:grid-cols-[130px_1fr]">
              <Image className="rounded-[18px] border border-line bg-white p-2" src={safeContact.wechatQr} alt={ui.weChatQrAlt} width={260} height={260} />
              <p className="text-sm font-semibold leading-7 text-slate-600">{ui.weChatDescriptionTemplate.replace("{wechatId}", safeContact.wechatId)}</p>
            </div>
          </div>
        </div>

        <QuoteForm compact contact={safeContact} productOptions={productOptions} ui={pageUi.quoteForm} />
      </section>
    </SiteChrome>
  );
}

function limitProductOptions<T>(products: T[], limit: number) {
  if (!Number.isInteger(limit) || limit <= 0) return products;
  return products.slice(0, limit);
}
