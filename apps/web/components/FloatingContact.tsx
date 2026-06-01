"use client";

import Image from "next/image";
import { Dialog } from "@base-ui/react/dialog";
import { Mail, MessageCircle, PhoneCall, QrCode, Send, X } from "lucide-react";

import { contact as defaultContactSettings, type ContactSettings } from "@/lib/contact";
import { defaultChromeUiContent, type ChromeUiContent } from "@/lib/site-ui";

export function FloatingContact({
  contact = defaultContactSettings,
  chromeUi = defaultChromeUiContent,
}: {
  contact?: ContactSettings;
  chromeUi?: ChromeUiContent;
}) {
  const quickLinks = [
    { label: chromeUi.floatingEmailLabel, href: `mailto:${contact.email}`, icon: Mail, tone: "from-blue-600 to-sky-400", external: false },
    { label: chromeUi.floatingWhatsAppLabel, href: contact.whatsappHref, icon: MessageCircle, tone: "from-emerald-500 to-teal-400", external: true },
    { label: chromeUi.floatingCallLabel, href: contact.phoneHref, icon: PhoneCall, tone: "from-gold to-orange-400", external: false },
  ] as const;

  return (
    <div className="fixed bottom-5 left-4 right-4 z-40 mx-auto flex max-w-[430px] items-center justify-center gap-1.5 rounded-[28px] border border-white/80 bg-white/[0.9] p-2 shadow-[0_24px_70px_rgba(8,35,74,0.18)] backdrop-blur-xl md:bottom-auto md:left-auto md:right-[10px] md:top-1/2 md:mx-0 md:w-auto md:max-w-none md:-translate-y-1/2 md:flex-col md:gap-2">
      <a
        aria-label={chromeUi.floatingQuoteTooltip}
        className="group relative inline-flex h-11 items-center justify-center gap-2 overflow-hidden rounded-2xl bg-navy px-4 text-sm font-black text-white shadow-[0_16px_38px_rgba(8,35,74,0.18)] transition duration-300 hover:-translate-y-1 hover:bg-[#0b2d5b] md:size-12 md:px-0"
        href={contact.quotePath}
      >
        <span className="absolute inset-x-2 top-0 h-px bg-gradient-to-r from-transparent via-gold/80 to-transparent" />
        <span className="relative z-10 md:hidden">{chromeUi.floatingQuoteLabel}</span>
        <Send className="relative z-10 size-4" />
        <span className="pointer-events-none absolute right-[58px] hidden whitespace-nowrap rounded-full bg-navy px-3 py-2 text-xs font-black text-white opacity-0 shadow-card transition duration-300 group-hover:translate-x-[-4px] group-hover:opacity-100 md:block">
          {chromeUi.floatingQuoteTooltip}
        </span>
      </a>

      {quickLinks.map((item) => {
        const Icon = item.icon;

        return (
          <a
            aria-label={item.label}
            className="group relative grid size-11 place-items-center rounded-2xl border border-line bg-white text-ink shadow-[0_12px_30px_rgba(8,35,74,0.1)] transition duration-300 hover:-translate-y-1 hover:border-white hover:text-white hover:shadow-[0_20px_44px_rgba(8,35,74,0.18)] md:size-12"
            href={item.href}
            key={item.label}
            rel={item.external ? "noreferrer" : undefined}
            target={item.external ? "_blank" : undefined}
          >
            <span className={`absolute inset-0 rounded-2xl bg-gradient-to-br ${item.tone} opacity-0 transition duration-300 group-hover:opacity-100`} />
            <Icon className="relative z-10 size-5" />
            <span className="pointer-events-none absolute right-[58px] hidden whitespace-nowrap rounded-full bg-navy px-3 py-2 text-xs font-black text-white opacity-0 shadow-card transition duration-300 group-hover:translate-x-[-4px] group-hover:opacity-100 md:block">
              {item.label}
            </span>
          </a>
        );
      })}

      <Dialog.Root modal="trap-focus">
        <Dialog.Trigger aria-label={chromeUi.floatingWeChatLabel} className="group relative grid size-11 place-items-center rounded-2xl border border-line bg-white text-ink shadow-[0_12px_30px_rgba(8,35,74,0.1)] outline-none transition duration-300 hover:-translate-y-1 hover:border-white hover:text-white hover:shadow-[0_20px_44px_rgba(8,35,74,0.18)] focus-visible:ring-4 focus-visible:ring-gold/20 md:size-12" type="button">
          <span className="absolute inset-0 rounded-2xl bg-[linear-gradient(135deg,#071f42_0%,#1d5fe8_58%,#f59a3c_100%)] opacity-0 transition duration-300 group-hover:opacity-100" />
          <QrCode className="relative z-10 size-5" />
          <span className="pointer-events-none absolute right-[58px] hidden whitespace-nowrap rounded-full bg-navy px-3 py-2 text-xs font-black text-white opacity-0 shadow-card transition duration-300 group-hover:translate-x-[-4px] group-hover:opacity-100 md:block">
            {chromeUi.floatingWeChatLabel}
          </span>
        </Dialog.Trigger>

        <Dialog.Portal>
          <Dialog.Backdrop className="fixed inset-0 z-50 bg-ink/18 backdrop-blur-[2px] transition duration-150 data-[ending-style]:opacity-0 data-[starting-style]:opacity-0" />
          <Dialog.Viewport className="fixed inset-0 z-50 grid place-items-center p-4">
            <Dialog.Popup className="relative w-full max-w-[300px] rounded-[24px] border border-line bg-white p-5 text-center shadow-[0_24px_70px_rgba(8,35,74,0.18)] outline-none transition duration-150 data-[ending-style]:scale-98 data-[ending-style]:opacity-0 data-[starting-style]:scale-98 data-[starting-style]:opacity-0">
              <Dialog.Close aria-label={chromeUi.closeWeChatLabel} className="absolute right-3 top-3 grid size-8 place-items-center rounded-full bg-[#f4f7fb] text-slate-500 transition hover:bg-navy hover:text-white" type="button">
                <X className="size-4" />
              </Dialog.Close>

              <Dialog.Title className="pr-7 text-lg font-black tracking-[-0.03em] text-ink">{chromeUi.weChatTitle}</Dialog.Title>
              <Dialog.Description className="mt-1 text-xs font-semibold text-slate-500">
                {chromeUi.weChatDescriptionTemplate.replace("{wechatId}", contact.wechatId)}
              </Dialog.Description>

              <div className="mx-auto mt-5 w-[210px] rounded-[18px] border border-line bg-white p-2 shadow-[0_14px_38px_rgba(8,35,74,0.08)]">
                <Image className="h-auto w-full rounded-[12px]" src={contact.wechatQr} alt={chromeUi.weChatQrAlt} width={420} height={420} />
              </div>
            </Dialog.Popup>
          </Dialog.Viewport>
        </Dialog.Portal>
      </Dialog.Root>
    </div>
  );
}
