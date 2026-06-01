import Image from "next/image";
import { ArrowRight, ChevronRight, Mail, MapPin, Phone } from "lucide-react";
import { Button } from "@/components/ui/button";
import { contact as defaultContactSettings, type ContactSettings } from "@/lib/contact";
import type { ChromeBrandingContent } from "@/lib/cms-site-settings";
import { defaultFinalCtaContent, defaultFooterContent, type FinalCtaContent, type FooterContent } from "@/lib/home-defaults";
import type { ChromeUiContent } from "@/lib/site-ui";
import { Logo } from "./Logo";

export function FinalCta({
  contact = defaultContactSettings,
  content = defaultFinalCtaContent,
}: {
  contact?: ContactSettings;
  content?: FinalCtaContent;
}) {
  return (
    <section id="quote" className="mx-auto my-10 w-[calc(100%_-_32px)] overflow-hidden rounded-[32px] border border-[#d6e2ef] bg-navy shadow-[0_30px_90px_rgba(8,35,74,0.16)] md:my-16 md:w-[calc(100%_-_96px)] lg:rounded-[34px]">
      <div className="relative min-h-[270px] md:min-h-[330px]">
        <Image className="object-cover object-center" src={content.image} alt="" fill sizes="(min-width: 3840px) 3200px, (min-width: 1921px) 83vw, 1600px" />
        <div className="absolute inset-0 bg-[linear-gradient(90deg,rgba(7,31,66,0.98)_0%,rgba(7,31,66,0.88)_28%,rgba(7,31,66,0.46)_58%,rgba(247,251,255,0.18)_100%)]" />
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_78%_18%,rgba(255,255,255,0.36),transparent_32%)]" />
        <div className="relative flex min-h-[270px] items-center px-6 py-8 text-white md:min-h-[330px] md:px-12 lg:px-16">
          <div className="max-w-none">
            <p className="mb-5 text-[11px] font-black uppercase tracking-[0.34em] text-white/90">
              {content.label}
            </p>
            <h2 className="max-w-none whitespace-nowrap font-serif text-[clamp(34px,5.2vw,76px)] font-semibold leading-[0.95] tracking-[-0.045em]">
              {content.title}
            </h2>
            <div className="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-8">
              <Button nativeButton={false} render={<a href={content.buttonHref} />} className="h-[58px] rounded-full bg-gold px-8 text-base font-black text-white shadow-[0_18px_44px_rgba(245,154,60,0.3)] hover:-translate-y-0.5 hover:bg-gold">
                {content.buttonLabel} <ArrowRight data-icon="inline-end" />
              </Button>
              <span className="hidden h-10 w-px bg-white/30 sm:block" />
              <a className="inline-flex items-center gap-3 text-base font-semibold text-white/90 transition hover:text-white" href={`mailto:${contact.email}`}>
                <Mail className="h-5 w-5" strokeWidth={1.8} />
                {contact.email}
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export function Footer({
  contact = defaultContactSettings,
  content = defaultFooterContent,
  branding,
  chromeUi,
}: {
  contact?: ContactSettings;
  content?: FooterContent;
  branding?: ChromeBrandingContent;
  chromeUi?: Pick<ChromeUiContent, "logoHomeLabel" | "logoImageAlt">;
}) {
  const mainFooterGroups = content.groups.filter((group) => group.links.length > 0);

  return (
    <footer className="border-t border-[#d8e3ef] bg-white pb-32 pt-0 md:pb-0">
      <div className="page-shell">
        <div className="grid grid-cols-1 gap-0 py-8 md:py-10 lg:grid-cols-[1.15fr_minmax(0,2.8fr)_1.2fr] lg:py-14">
          <div className="border-b border-[#dbe6f2] pb-8 lg:border-b-0 lg:pr-10">
            <Logo
              height={branding?.logoHeight}
              homeLabel={chromeUi?.logoHomeLabel}
              imageAlt={chromeUi?.logoImageAlt}
              src={branding?.logoSrc}
              width={branding?.logoWidth}
            />
            <p className="mt-5 max-w-[300px] text-base font-medium leading-8 text-slate-500">
              {content.intro}
            </p>
            <div className="mt-7 flex gap-3 text-sm font-black text-navy">
              {content.socialLinks.map((item) => (
                <a className="grid h-11 w-11 place-items-center rounded-full border border-[#dfe8f2] bg-white text-sm shadow-[0_12px_28px_rgba(8,35,74,0.08)]" href={item.href} key={item.label}>
                  {item.label}
                </a>
              ))}
            </div>
          </div>

          <div className="grid border-b border-[#dbe6f2] md:grid-cols-2 md:border-b-0 lg:border-l lg:border-[#dbe6f2] xl:grid-cols-3">
            {mainFooterGroups.map((group) => (
              <div className="border-b border-[#dbe6f2] py-8 md:border-b-0 md:pr-8 lg:py-0 lg:pl-10" key={group.title}>
                <h3 className="mb-6 text-sm font-black uppercase tracking-[0.14em] text-navy">{group.title}</h3>
                <div className="grid gap-4">
                  {group.links.map((link) => (
                    <a className="group flex min-w-0 items-center justify-between gap-4 text-base font-medium leading-6 text-slate-500 transition hover:text-blue-700" href={link.href} key={link.label}>
                      <span className="min-w-0">{link.label}</span>
                      <ChevronRight className="h-4 w-4 shrink-0 text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-600" strokeWidth={1.9} />
                    </a>
                  ))}
                </div>
              </div>
            ))}
          </div>

          <div className="pt-8 lg:border-l lg:pl-10 lg:pt-0">
            <h3 className="mb-6 text-sm font-black uppercase tracking-[0.14em] text-navy">{content.contactTitle}</h3>
            <div className="grid gap-4">
              {[
                { icon: Phone, label: contact.phone, href: contact.phoneHref },
                { icon: Mail, label: contact.email, href: `mailto:${contact.email}` },
                { icon: MapPin, label: contact.address, href: contact.contactPath },
              ].map(({ href, icon: Icon, label }) => (
                <a className="group flex items-start gap-4 border-b border-[#e5edf6] pb-4 last:border-b-0 last:pb-0" href={href} key={label}>
                  <span className="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white text-navy shadow-[0_10px_24px_rgba(8,35,74,0.08)] ring-1 ring-[#e2eaf3] transition group-hover:text-blue-700">
                    <Icon className="h-5 w-5" strokeWidth={1.9} />
                  </span>
                  <span className="text-base font-medium leading-7 text-slate-600 transition group-hover:text-blue-700">{label}</span>
                </a>
              ))}
            </div>
          </div>
        </div>

        <div className="flex flex-col gap-4 border-t border-[#c9dced] py-6 text-sm font-semibold leading-6 text-slate-500 lg:flex-row lg:items-center lg:justify-between">
          <span>{content.copyright}</span>
          <span className="flex flex-wrap gap-x-7 gap-y-2">
            {content.legalLinks.map((link) => (
              <a className="hover:text-blue-700" href={link.href} key={link.label}>{link.label}</a>
            ))}
          </span>
        </div>
      </div>
    </footer>
  );
}
