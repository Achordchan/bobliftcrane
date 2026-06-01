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
    <div className="fixed bottom-4 left-1/2 z-40 flex -translate-x-1/2 items-center justify-center gap-1.5 rounded-full border border-white/80 bg-white/[0.88] p-1.5 shadow-[0_16px_44px_rgba(8,35,74,0.14)] backdrop-blur-xl 2xl:bottom-auto 2xl:left-auto 2xl:right-4 2xl:top-1/2 2xl:mx-0 2xl:w-auto 2xl:max-w-none 2xl:-translate-x-0 2xl:-translate-y-1/2 2xl:flex-col 2xl:gap-2 2xl:rounded-[28px] 2xl:p-2 2xl:shadow-[0_24px_70px_rgba(8,35,74,0.18)]">
      <a
        aria-label={chromeUi.floatingQuoteTooltip}
        className="group relative inline-flex h-10 items-center justify-center gap-2 overflow-hidden rounded-full bg-navy px-4 text-sm font-black text-white shadow-[0_12px_28px_rgba(8,35,74,0.18)] transition duration-300 hover:-translate-y-0.5 hover:bg-[#0b2d5b] sm:h-11 2xl:size-12 2xl:rounded-2xl 2xl:px-0"
        href={contact.quotePath}
      >
        <span className="absolute inset-x-2 top-0 h-px bg-gradient-to-r from-transparent via-gold/80 to-transparent" />
        <span className="relative z-10 2xl:hidden">{chromeUi.floatingQuoteLabel}</span>
        <Send className="relative z-10 size-4" />
        <span className="pointer-events-none absolute right-[58px] hidden whitespace-nowrap rounded-full bg-navy px-3 py-2 text-xs font-black text-white opacity-0 shadow-card transition duration-300 group-hover:translate-x-[-4px] group-hover:opacity-100 2xl:block">
          {chromeUi.floatingQuoteTooltip}
        </span>
      </a>

      {quickLinks.map((item) => {
        const Icon = item.icon;

        return (
          <a
            aria-label={item.label}
            className="group relative grid size-10 place-items-center rounded-full border border-line bg-white text-ink shadow-[0_10px_24px_rgba(8,35,74,0.08)] transition duration-300 hover:-translate-y-0.5 hover:border-white hover:text-white hover:shadow-[0_16px_34px_rgba(8,35,74,0.14)] sm:size-11 2xl:size-12 2xl:rounded-2xl"
            href={item.href}
            key={item.label}
            rel={item.external ? "noreferrer" : undefined}
            target={item.external ? "_blank" : undefined}
          >
            <span className={`absolute inset-0 rounded-2xl bg-gradient-to-br ${item.tone} opacity-0 transition duration-300 group-hover:opacity-100`} />
            <Icon className="relative z-10 size-5" />
            <span className="pointer-events-none absolute right-[58px] hidden whitespace-nowrap rounded-full bg-navy px-3 py-2 text-xs font-black text-white opacity-0 shadow-card transition duration-300 group-hover:translate-x-[-4px] group-hover:opacity-100 2xl:block">
              {item.label}
            </span>
          </a>
        );
      })}

      <Dialog.Root modal="trap-focus">
        <Dialog.Trigger aria-label={chromeUi.floatingWeChatLabel} className="group relative grid size-10 place-items-center rounded-full border border-line bg-white text-ink shadow-[0_10px_24px_rgba(8,35,74,0.08)] outline-none transition duration-300 hover:-translate-y-0.5 hover:border-white hover:text-white hover:shadow-[0_16px_34px_rgba(8,35,74,0.14)] focus-visible:ring-4 focus-visible:ring-gold/20 sm:size-11 2xl:size-12 2xl:rounded-2xl" type="button">
          <span className="absolute inset-0 rounded-2xl bg-[linear-gradient(135deg,#071f42_0%,#1d5fe8_58%,#f59a3c_100%)] opacity-0 transition duration-300 group-hover:opacity-100" />
          <QrCode className="relative z-10 size-5" />
          <span className="pointer-events-none absolute right-[58px] hidden whitespace-nowrap rounded-full bg-navy px-3 py-2 text-xs font-black text-white opacity-0 shadow-card transition duration-300 group-hover:translate-x-[-4px] group-hover:opacity-100 2xl:block">
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
