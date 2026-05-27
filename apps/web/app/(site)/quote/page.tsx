import { ArrowRight, FileText, MailCheck, MessageCircle } from "lucide-react";

import { QuoteForm } from "@/components/site/QuoteForm";
import { PageHero } from "@/components/site/PageHero";
import { SiteChrome } from "@/components/site/SiteChrome";

export const metadata = {
  title: "Request Quote | BOB-LIFT",
  description: "Send BOB-LIFT your lifting requirement and prepare a quotation inquiry by email or WhatsApp.",
};

export default function QuotePage() {
  return (
    <SiteChrome showFinalCta={false}>
      <PageHero label="Request Quote" title="Request a tailored BOB-LIFT quotation." description="Share your product interest, destination country, contact details and working conditions. The team can then match a practical lifting solution for your project." image="/images/cta-lift-photo.png" />

      <section className="page-shell grid grid-cols-1 gap-8 py-10 md:py-16 lg:grid-cols-[0.72fr_1.28fr] lg:gap-12">
        <aside className="rounded-[30px] border border-line bg-[linear-gradient(180deg,#071f42_0%,#0b3a78_100%)] p-6 text-white shadow-[0_34px_100px_rgba(8,35,74,0.16)] md:p-8 lg:sticky lg:top-8 lg:h-fit">
          <p className="text-[11px] font-black uppercase tracking-[0.24em] text-gold">Quote Workflow</p>
          <h2 className="mt-4 text-3xl font-black leading-tight tracking-[-0.04em] md:text-4xl">Fill once, send through your preferred channel.</h2>
          <div className="mt-7 grid gap-4">
            {[
              { icon: FileText, title: "Describe requirement", text: "Model, quantity, load and working radius." },
              { icon: MailCheck, title: "Create inquiry draft", text: "Your details become a clean quotation message." },
              { icon: MessageCircle, title: "Send by Email or WhatsApp", text: "You control the final sending channel." },
            ].map(({ icon: Icon, title, text }) => (
              <div className="rounded-2xl border border-white/10 bg-white/8 p-4" key={title}>
                <Icon className="h-5 w-5 text-gold" />
                <h3 className="mt-3 text-base font-black">{title}</h3>
                <p className="mt-1 text-sm font-semibold leading-6 text-white/70">{text}</p>
              </div>
            ))}
          </div>
          <a className="mt-7 inline-flex items-center gap-2 text-sm font-black text-gold" href="/products">Need to choose a model first? <ArrowRight className="h-4 w-4" /></a>
        </aside>

        <QuoteForm />
      </section>
    </SiteChrome>
  );
}
