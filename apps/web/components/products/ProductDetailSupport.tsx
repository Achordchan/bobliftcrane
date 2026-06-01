import Image from "next/image";
import { ArrowRight, ChevronDown, Mail } from "lucide-react";

import type { ProductDetailUiContent } from "@/lib/product-ui";
import type { ProductApplicationCard, ProductDetailFaqItem, ProductDetailModel } from "@/components/products/ProductDetailTypes";

export function ApplicationsSection({
  applications,
  ui,
}: {
  applications: ProductApplicationCard[];
  ui: ProductDetailUiContent;
}) {
  return (
    <section id="applications" className="scroll-mt-24">
      <h2 className="mb-4 text-3xl font-black tracking-[-0.04em] text-[#0B1B33]">
        {ui.applicationsTitle}
      </h2>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        {applications.map(({ title, text, image, icon: Icon }) => (
          <article
            className="overflow-hidden rounded-[18px] border border-[#E5E7EB] bg-white shadow-[0_18px_50px_rgba(8,35,74,0.055)]"
            key={title}
          >
            <div className="relative h-36">
              <Image
                className="object-cover"
                src={image}
                alt={title}
                fill
                sizes="25vw"
              />
            </div>
            <div className="relative p-5 pt-8">
              <span className="absolute -top-7 left-5 grid h-14 w-14 place-items-center rounded-full border border-blue-100 bg-white text-blue-600 shadow-[0_12px_30px_rgba(8,35,74,0.12)]">
                <Icon className="h-7 w-7" />
              </span>
              <h3 className="text-base font-black text-[#0B1B33]">{title}</h3>
              <p className="mt-1 text-sm font-semibold leading-6 text-slate-500">
                {text}
              </p>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}

export function FaqSection({
  faqs,
  ui,
}: {
  faqs?: ProductDetailFaqItem[];
  ui: ProductDetailUiContent;
}) {
  const items = faqs?.length ? faqs : ui.defaultFaqs;

  return (
    <section id="faq" className="scroll-mt-24">
      <h2 className="mb-4 text-3xl font-black tracking-[-0.04em] text-[#0B1B33]">
        {ui.faqTitle}
      </h2>
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {items.map((item) => (
          <details
            className="group rounded-[14px] border border-[#E5E7EB] bg-white px-5 py-4 shadow-[0_12px_36px_rgba(8,35,74,0.04)]"
            key={item.question}
          >
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-black text-[#0B1B33]">
              {item.question}
              <ChevronDown className="h-4 w-4 transition group-open:rotate-180" />
            </summary>
            <p className="mt-3 text-sm font-semibold leading-6 text-slate-500">
              {item.answer}
            </p>
          </details>
        ))}
      </div>
    </section>
  );
}

export function QuoteCta({
  product,
  ui,
}: {
  product: ProductDetailModel;
  ui: ProductDetailUiContent;
}) {
  const showButtons = ui.quoteVisibility.requestQuoteButton || ui.quoteVisibility.contactEngineerButton;

  return (
    <section
      id="quote"
      className="scroll-mt-24 overflow-hidden rounded-[22px] bg-[linear-gradient(135deg,#071f42_0%,#0B3F7D_100%)] p-7 text-white shadow-[0_30px_90px_rgba(8,35,74,0.2)] md:p-9"
    >
      <div className="grid grid-cols-1 items-center gap-6 lg:grid-cols-[1fr_auto]">
        <div className="flex items-start gap-5">
          <div className="hidden h-20 w-20 shrink-0 place-items-center rounded-[22px] border border-white/18 bg-white/10 md:grid">
            <Mail className="h-10 w-10" />
          </div>
          <div>
            <h2 className="text-3xl font-black tracking-[-0.04em] md:text-4xl">
              {ui.quoteTitle}
            </h2>
            <p className="mt-3 max-w-3xl text-sm font-semibold leading-7 text-white/78">
              {ui.quoteText}
            </p>
          </div>
        </div>
        {showButtons ? (
          <div className="flex flex-wrap gap-3">
            {ui.quoteVisibility.requestQuoteButton ? (
              <a
                className="inline-flex h-14 items-center justify-center gap-3 rounded-[8px] bg-white px-7 text-sm font-black text-[#0B3F7D] transition hover:-translate-y-0.5"
                href={product.inquiryUrl}
              >
                {ui.requestQuoteLabel} <ArrowRight className="h-4 w-4" />
              </a>
            ) : null}
            {ui.quoteVisibility.contactEngineerButton ? (
              <a
                className="inline-flex h-14 items-center justify-center gap-3 rounded-[8px] border border-white/35 px-7 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-white/10"
                href={product.contactUrl}
              >
                {ui.contactEngineerLabel} <Mail className="h-4 w-4" />
              </a>
            ) : null}
          </div>
        ) : null}
      </div>
    </section>
  );
}
