import Image from "next/image";
import { ArrowRight, ChevronRight, Mail, MapPin, Phone } from "lucide-react";

import { Button } from "@/components/ui/button";
import { contact as defaultContactSettings, type ContactSettings } from "@/lib/contact";
import type { ChromeBrandingContent } from "@/lib/cms-site-settings";
import { defaultFinalCtaContent, defaultFooterContent, type FinalCtaContent, type FooterContent, type FooterLink } from "@/lib/home-defaults";
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
  const footerGroupsClassName = `grid border-b border-[#dbe6f2] md:border-b-0 md:border-l md:border-[#dbe6f2] md:pl-8 xl:pl-0 ${
    mainFooterGroups.length >= 3 ? "md:grid-cols-2 2xl:grid-cols-3" : "md:grid-cols-2"
  }`;

  return (
    <footer className="border-t border-[#d8e3ef] bg-white pb-32 pt-0 md:pb-0">
      <div className="page-shell max-w-[1760px]">
        <div className="grid grid-cols-1 gap-0 py-7 md:grid-cols-[0.92fr_1.08fr] md:gap-y-9 md:py-10 xl:grid-cols-[minmax(230px,0.9fr)_minmax(0,1.65fr)_minmax(260px,0.95fr)] xl:gap-y-0 xl:py-14">
          <div className="border-b border-[#dbe6f2] pb-7 md:border-b-0 md:pr-8 xl:pr-10">
            <Logo
              height={branding?.logoHeight}
              homeLabel={chromeUi?.logoHomeLabel}
              imageAlt={chromeUi?.logoImageAlt}
              src={branding?.logoSrc}
              width={branding?.logoWidth}
            />
            <p className="mt-5 max-w-[34rem] text-sm font-medium leading-6 text-slate-500 md:max-w-[300px] md:text-[15px] md:leading-7">
              {content.intro}
            </p>
            <div className="mt-6 flex flex-wrap gap-3">
              {content.socialLinks.map((item) => (
                <a
                  aria-label={socialPlatform(item).name}
                  className="group grid h-11 w-11 place-items-center rounded-full border border-[#dfe8f2] bg-white shadow-[0_12px_28px_rgba(8,35,74,0.08)] transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-[0_18px_36px_rgba(8,35,74,0.14)]"
                  href={item.href}
                  key={`${item.label}-${item.href}`}
                  rel={isExternalLink(item.href) ? "noreferrer" : undefined}
                  target={isExternalLink(item.href) ? "_blank" : undefined}
                  title={socialPlatform(item).name}
                >
                  <SocialMark link={item} />
                </a>
              ))}
            </div>
          </div>

          <div className={footerGroupsClassName}>
            {mainFooterGroups.map((group) => (
              <div className="border-b border-[#dbe6f2] py-7 last:border-b-0 md:border-b-0 md:py-0 md:pr-8 xl:pl-10" key={group.title}>
                <h3 className="mb-5 text-sm font-black uppercase tracking-[0.14em] text-navy">{group.title}</h3>
                <div className="grid gap-3.5">
                  {group.links.map((link) => (
                    <a className="group flex min-w-0 items-center justify-between gap-4 text-sm font-medium leading-6 text-slate-500 transition hover:text-blue-700 md:text-[15px]" href={link.href} key={link.label}>
                      <span className="min-w-0">{link.label}</span>
                      <ChevronRight className="h-4 w-4 shrink-0 text-slate-400 transition group-hover:translate-x-1 group-hover:text-blue-600" strokeWidth={1.9} />
                    </a>
                  ))}
                </div>
              </div>
            ))}
          </div>

          <div className="pt-7 md:col-span-2 md:border-t md:border-[#dbe6f2] md:pt-8 xl:col-span-1 xl:border-l xl:border-t-0 xl:pl-10 xl:pt-0">
            <h3 className="mb-5 text-sm font-black uppercase tracking-[0.14em] text-navy">{content.contactTitle}</h3>
            <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-1">
              {[
                { icon: Phone, label: contact.phone, href: contact.phoneHref },
                { icon: Mail, label: contact.email, href: `mailto:${contact.email}` },
                { icon: MapPin, label: contact.address, href: contact.contactPath },
              ].map(({ href, icon: Icon, label }) => (
                <a className="group flex min-w-0 items-start gap-3.5 border-b border-[#e5edf6] pb-4 last:border-b-0 last:pb-0 md:border-b-0 md:pb-0 xl:border-b xl:pb-4 xl:last:border-b-0 xl:last:pb-0" href={href} key={label}>
                  <span className="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white text-navy shadow-[0_10px_24px_rgba(8,35,74,0.08)] ring-1 ring-[#e2eaf3] transition group-hover:text-blue-700">
                    <Icon className="h-5 w-5" strokeWidth={1.9} />
                  </span>
                  <span className="min-w-0 break-words text-sm font-medium leading-6 text-slate-600 transition group-hover:text-blue-700 md:text-[15px] md:leading-7">{label}</span>
                </a>
              ))}
            </div>
          </div>
        </div>

        <div className="flex flex-col gap-3 border-t border-[#c9dced] py-5 text-sm font-semibold leading-6 text-slate-500 lg:flex-row lg:items-center lg:justify-between lg:gap-4 lg:py-6">
          <span>{content.copyright}</span>
          <span className="flex flex-wrap gap-x-6 gap-y-2 md:pr-24">
            {content.legalLinks.map((link) => (
              <a className="hover:text-blue-700" href={link.href} key={link.label}>{link.label}</a>
            ))}
          </span>
        </div>
      </div>
    </footer>
  );
}

function SocialMark({ link }: { link: FooterLink }) {
  const platform = socialPlatform(link);

  if (platform.type === "linkedin") {
    return (
      <span aria-hidden className="grid h-6 w-6 place-items-center rounded-md bg-[#0A66C2] text-[13px] font-black leading-none text-white">
        in
      </span>
    );
  }

  if (platform.type === "youtube") {
    return (
      <span aria-hidden className="relative h-[18px] w-[26px] rounded-md bg-[#FF0000]">
        <span className="absolute left-[10px] top-1/2 h-0 w-0 -translate-y-1/2 border-y-[5px] border-l-[8px] border-y-transparent border-l-white" />
      </span>
    );
  }

  if (platform.type === "facebook") {
    return (
      <span aria-hidden className="grid h-6 w-6 place-items-center rounded-full bg-[#1877F2] font-serif text-[19px] font-black leading-none text-white">
        f
      </span>
    );
  }

  return (
    <span aria-hidden className="text-xs font-black uppercase tracking-[-0.02em] text-navy">
      {platform.name.slice(0, 2)}
    </span>
  );
}

function socialPlatform(link: FooterLink) {
  const source = `${link.label} ${link.href}`.toLowerCase();

  if (source.includes("linkedin") || link.label.toLowerCase() === "in") return { type: "linkedin", name: "LinkedIn" };
  if (source.includes("youtube") || source.includes("youtu.be") || link.label.toLowerCase() === "yt") return { type: "youtube", name: "YouTube" };
  if (source.includes("facebook") || source.includes("fb.com") || link.label.toLowerCase() === "fb") return { type: "facebook", name: "Facebook" };

  return { type: "custom", name: link.label };
}

function isExternalLink(href: string) {
  return /^https?:\/\//i.test(href);
}
