import Image from "next/image";
import { ArrowRight, Mail, MapPin, Phone } from "lucide-react";
import { Button } from "@/components/ui/button";
import { contact } from "@/lib/contact";
import { footerGroups } from "@/lib/home-data";
import { Logo } from "./Logo";

export function FinalCta() {
  return (
    <section id="quote" className="page-shell my-12 overflow-hidden rounded-[34px] border border-white bg-navy shadow-[0_34px_100px_rgba(8,35,74,0.18)] md:my-16 lg:rounded-[44px]">
      <div className="relative min-h-[300px]">
        <Image className="object-cover" src="/images/cta-lift-photo.png" alt="" fill sizes="(min-width: 3840px) 3200px, (min-width: 1921px) 83vw, 1600px" />
        <div className="absolute inset-0 bg-[linear-gradient(90deg,rgba(7,31,66,0.94)_0%,rgba(7,31,66,0.72)_42%,rgba(7,31,66,0.12)_100%)]" />
        <div className="relative flex min-h-[300px] items-center px-6 py-8 text-white md:px-12 lg:px-16">
          <div className="max-w-[620px]">
            <p className="mb-4 text-[11px] font-black uppercase tracking-[0.26em] text-gold">
              Project Consultation
            </p>
            <h2 className="text-[clamp(34px,9vw,44px)] font-black leading-none tracking-[-0.055em] sm:text-[54px] lg:text-[64px]">
              Need a Lifting Solution?
            </h2>
            <div className="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center">
            <Button nativeButton={false} render={<a href="/quote" />} className="h-[52px] rounded-full bg-gold px-8 text-sm font-black text-white shadow-[0_18px_44px_rgba(245,154,60,0.28)] hover:-translate-y-0.5 hover:bg-gold">
                Request a Quote <ArrowRight data-icon="inline-end" />
              </Button>
              <a className="text-sm font-bold text-white/[0.78] transition hover:text-white" href={`mailto:${contact.email}`}>
                {contact.email}
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export function Footer() {
  return (
    <footer className="border-t border-line bg-[linear-gradient(180deg,#ffffff_0%,#f7fbff_100%)] pb-32 pt-8 md:py-12">
      <div className="page-shell grid grid-cols-2 gap-x-5 gap-y-7 lg:grid-cols-[1.4fr_repeat(5,1fr)] lg:gap-10">
        <div className="col-span-2 rounded-[28px] border border-line bg-white/86 p-5 shadow-[0_18px_50px_rgba(8,35,74,0.06)] lg:col-span-1 lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none">
          <Logo />
          <p className="mt-4 max-w-[300px] text-sm leading-7 text-slate-500 lg:mt-6 lg:max-w-[250px]">
            Engineered lifting solutions for global projects. Built to lift. Built to last.
          </p>
          <div className="mt-5 flex gap-2 text-xs font-black text-ink lg:mt-6 lg:gap-3">
            {["in", "yt", "fb"].map((item) => (
              <span className="grid h-8 min-w-8 place-items-center rounded-full border border-line bg-white px-2 shadow-[0_8px_22px_rgba(8,35,74,0.05)] lg:h-auto lg:min-w-0 lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none" key={item}>
                {item}
              </span>
            ))}
          </div>
        </div>
        {footerGroups.map((group) => (
          <div className="min-w-0" key={group.title}>
            <h3 className="mb-3 text-xs font-black uppercase tracking-[0.08em] text-ink lg:mb-5 lg:text-sm lg:tracking-normal">{group.title}</h3>
            <div className="space-y-2 lg:space-y-3">
              {group.links.map((link) => (
                <a className="line-clamp-2 block text-xs font-semibold leading-5 text-slate-500 hover:text-gold lg:text-sm lg:font-medium" href={link.href} key={link.label}>
                  {link.label}
                </a>
              ))}
            </div>
          </div>
        ))}
        <div className="col-span-2 rounded-[24px] border border-line bg-white/86 p-4 shadow-[0_18px_50px_rgba(8,35,74,0.06)] lg:col-span-1 lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none">
          <h3 className="mb-4 text-xs font-black uppercase tracking-[0.08em] text-ink lg:mb-5 lg:text-sm lg:tracking-normal">Contact Us</h3>
          <p className="mb-3 flex gap-3 text-xs leading-5 text-slate-500 lg:text-sm"><Phone className="h-4 w-4 shrink-0" /> {contact.phone}</p>
          <p className="mb-3 flex gap-3 text-xs leading-5 text-slate-500 lg:text-sm"><Mail className="h-4 w-4 shrink-0" /> {contact.email}</p>
          <p className="flex gap-3 text-xs leading-5 text-slate-500 lg:text-sm"><MapPin className="h-4 w-4 shrink-0" /> {contact.address}</p>
        </div>
      </div>
      <div className="page-shell mt-8 flex flex-col gap-2 border-t border-line pt-5 text-[11px] font-semibold leading-5 text-slate-500 md:mt-10 md:flex-row md:gap-3 md:pt-6 md:text-xs">
        <span>© 2026 BOB-LIFT. All Rights Reserved.</span>
        <span className="md:mx-auto">Privacy Policy&nbsp;&nbsp;|&nbsp;&nbsp;Terms of Use&nbsp;&nbsp;|&nbsp;&nbsp;Sitemap</span>
      </div>
    </footer>
  );
}
