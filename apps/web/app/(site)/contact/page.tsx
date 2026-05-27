import Image from "next/image";
import { Mail, MapPin, MessageCircle, Phone } from "lucide-react";

import { QuoteForm } from "@/components/site/QuoteForm";
import { PageHero } from "@/components/site/PageHero";
import { SiteChrome } from "@/components/site/SiteChrome";
import { contact } from "@/lib/contact";

export const metadata = {
  title: "Contact Us | BOB-LIFT",
  description: "Contact BOB-LIFT by email, phone, WhatsApp or WeChat for crane quotation and technical consultation.",
};

const contactCards = [
  { label: "Email", value: contact.email, href: `mailto:${contact.email}`, icon: Mail },
  { label: "Phone", value: contact.phone, href: contact.phoneHref, icon: Phone },
  { label: "WhatsApp", value: "Start chat", href: contact.whatsappHref, icon: MessageCircle, external: true },
  { label: "Address", value: contact.address, href: "#map", icon: MapPin },
];

export default function ContactPage() {
  return (
    <SiteChrome showFinalCta={false}>
      <PageHero label="Contact Us" title="Talk to BOB-LIFT before choosing a crane." description="For model selection, truck chassis matching, marine installation and export questions, use the direct contact channels or create a structured inquiry draft." image="/images/global-network-blue.jpg" />

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
            <p className="section-label mb-3">WeChat</p>
            <div className="grid grid-cols-[130px_1fr] gap-4 items-center">
              <Image className="rounded-[18px] border border-line bg-white p-2" src={contact.wechatQr} alt="BOB-LIFT WeChat QR code" width={260} height={260} />
              <p className="text-sm font-semibold leading-7 text-slate-600">Scan the QR code or search ID <strong className="text-ink">{contact.wechatId}</strong>. The QR block is kept as a compact card so it does not dominate mobile screens.</p>
            </div>
          </div>
        </div>

        <QuoteForm compact />
      </section>
    </SiteChrome>
  );
}
