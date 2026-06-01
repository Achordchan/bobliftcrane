"use client";

import { useEffect, useMemo, useState } from "react";
import { CheckCircle2, Mail, MessageCircle, Send } from "lucide-react";

import { contact as defaultContactSettings, type ContactSettings, whatsappToHref } from "@/lib/contact";
import { defaultPageUiContent, type QuoteFormUiContent } from "@/lib/site-ui";

type FormState = {
  name: string;
  email: string;
  phone: string;
  country: string;
  product: string;
  productSlug: string;
  quantity: string;
  message: string;
};

type QuoteFieldName = keyof QuoteFormUiContent["fieldControls"];

const initialState: FormState = {
  name: "",
  email: "",
  phone: "",
  country: "",
  product: "",
  productSlug: "",
  quantity: "",
  message: "",
};

export type QuoteProductOption = {
  key: string;
  label: string;
  value: string;
  slug?: string;
};

export function QuoteForm({
  compact = false,
  contact = defaultContactSettings,
  productOptions,
  ui = defaultPageUiContent.quoteForm,
}: {
  compact?: boolean;
  contact?: ContactSettings;
  productOptions?: QuoteProductOption[];
  ui?: QuoteFormUiContent;
}) {
  const [form, setForm] = useState<FormState>(initialState);
  const [draftReady, setDraftReady] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState("");
  const displayedProductOptions = productOptions ?? [];
  const enabledButtons = {
    save: ui.buttonChannels.save !== false,
    email: ui.buttonChannels.email !== false,
    whatsapp: ui.buttonChannels.whatsapp !== false,
  };
  if (!enabledButtons.save && !enabledButtons.email && !enabledButtons.whatsapp) enabledButtons.save = true;

  useEffect(() => {
    const product = new URLSearchParams(window.location.search).get("product");
    const slug = new URLSearchParams(window.location.search).get("productSlug");
    if (product || slug) setForm((current) => ({ ...current, product: product || current.product, productSlug: slug || current.productSlug }));
  }, []);

  const inquiryText = useMemo(() => {
    const labels = ui.inquiryLineLabels;
    const lines = [contact.defaultInquiryText];
    if (isFieldVisible("name")) lines.push(`${labels.name}: ${form.name || "-"}`);
    if (isFieldVisible("email")) lines.push(`${labels.email}: ${form.email || "-"}`);
    if (isFieldVisible("phone")) lines.push(`${labels.phone}: ${form.phone || "-"}`);
    if (isFieldVisible("country")) lines.push(`${labels.country}: ${form.country || "-"}`);
    if (isFieldVisible("product")) lines.push(`${labels.product}: ${form.product || ui.fields.productNotDecided}`);
    if (isFieldVisible("quantity")) lines.push(`${labels.quantity}: ${form.quantity || "-"}`);
    if (isFieldVisible("message")) lines.push(`${labels.message}: ${form.message || "-"}`);
    return lines.join("\n");
  }, [contact.defaultInquiryText, enabledButtons.save, form, ui.fieldControls, ui.fields.productNotDecided, ui.inquiryLineLabels]);

  const mailHref = `mailto:${contact.email}?subject=${encodeURIComponent(contact.inquirySubject)}&body=${encodeURIComponent(inquiryText)}`;
  const whatsappHref = whatsappToHref(contact.whatsapp, inquiryText);

  function updateField(field: keyof FormState, value: string) {
    setDraftReady(false);
    setSubmitError("");
    setForm((current) => ({ ...current, [field]: value }));
  }

  function isFieldVisible(field: QuoteFieldName) {
    if (enabledButtons.save && (field === "name" || field === "email")) return true;
    return ui.fieldControls[field]?.visible !== false;
  }

  function isFieldRequired(field: QuoteFieldName) {
    if (enabledButtons.save && (field === "name" || field === "email")) return true;
    return ui.fieldControls[field]?.required === true;
  }

  async function submitInquiry() {
    setIsSubmitting(true);
    setSubmitError("");

    try {
      const response = await fetch("/api/inquiries", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          ...form,
          locale: readCookie("boblift-locale") || "en",
          sourcePath: window.location.pathname,
          ...utmPayload(),
        }),
      });

      if (!response.ok) {
        const result = (await response.json().catch(() => null)) as { code?: string } | null;
        throw new Error(messageForInquiryError(result?.code, ui));
      }

      setDraftReady(true);
    } catch (error) {
      setSubmitError(error instanceof Error ? error.message : ui.messages.errorFallback);
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <form
      className={`rounded-[28px] border border-line bg-white/94 shadow-[0_30px_90px_rgba(8,35,74,0.1)] ${compact ? "p-4 md:p-5" : "p-5 md:p-8"}`}
      onSubmit={async (event) => {
        event.preventDefault();
        if (!enabledButtons.save) return;
        await submitInquiry();
      }}
    >
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="section-label mb-3">{ui.label}</p>
          <h2 className="text-2xl font-black leading-tight tracking-[-0.04em] text-ink md:text-4xl">{ui.title}</h2>
        </div>
        <span className="hidden rounded-2xl bg-navy px-4 py-2 text-xs font-black text-white md:inline-flex">{ui.badge}</span>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        {isFieldVisible("name") ? <Field label={ui.fields.name} value={form.name} onChange={(value) => updateField("name", value)} required={isFieldRequired("name")} /> : null}
        {isFieldVisible("email") ? <Field label={ui.fields.email} type="email" value={form.email} onChange={(value) => updateField("email", value)} required={isFieldRequired("email")} /> : null}
        {isFieldVisible("phone") ? <Field label={ui.fields.phone} value={form.phone} onChange={(value) => updateField("phone", value)} required={isFieldRequired("phone")} /> : null}
        {isFieldVisible("country") ? <Field label={ui.fields.country} value={form.country} onChange={(value) => updateField("country", value)} required={isFieldRequired("country")} /> : null}
        {isFieldVisible("product") ? (
          <label className="block md:col-span-2">
            <span className="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500">{ui.fields.product}</span>
            <select
              className="h-12 w-full rounded-2xl border border-line bg-[#f8fbff] px-4 text-sm font-bold text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100"
              required={isFieldRequired("product")}
              value={form.product}
              onChange={(event) => {
                const selected = displayedProductOptions.find((option) => option.value === event.target.value);
                setDraftReady(false);
                setSubmitError("");
                setForm((current) => ({ ...current, product: event.target.value, productSlug: selected?.slug ?? "" }));
              }}
            >
              <option value="">{ui.fields.productNotDecided}</option>
              {displayedProductOptions.map((option) => (
                <option key={option.key} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>
        ) : null}
        {isFieldVisible("quantity") ? <Field label={ui.fields.quantity} value={form.quantity} onChange={(value) => updateField("quantity", value)} required={isFieldRequired("quantity")} /> : null}
        {isFieldVisible("message") ? (
          <label className="block md:col-span-2">
            <span className="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500">{ui.fields.message}</span>
            <textarea
              className="min-h-[140px] w-full resize-y rounded-2xl border border-line bg-[#f8fbff] px-4 py-3 text-sm font-semibold leading-6 text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100"
              placeholder={ui.fields.messagePlaceholder}
              required={isFieldRequired("message")}
              value={form.message}
              onChange={(event) => updateField("message", event.target.value)}
            />
          </label>
        ) : null}
      </div>

      <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        {enabledButtons.save ? (
          <button className="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-navy px-7 text-sm font-black text-white shadow-[0_18px_42px_rgba(8,35,74,0.18)] transition hover:-translate-y-0.5 hover:bg-[#0b2d5b] disabled:cursor-not-allowed disabled:opacity-65 disabled:hover:translate-y-0" disabled={isSubmitting} type="submit">
            {isSubmitting ? ui.buttons.saving : ui.buttons.save} <Send className="h-4 w-4" />
          </button>
        ) : null}
        {enabledButtons.email ? (
          <a className="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-line bg-white px-7 text-sm font-black text-ink transition hover:-translate-y-0.5 hover:border-gold" href={mailHref}>
            {ui.buttons.email} <Mail className="h-4 w-4" />
          </a>
        ) : null}
        {enabledButtons.whatsapp ? (
          <a className="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-7 text-sm font-black text-emerald-700 transition hover:-translate-y-0.5" href={whatsappHref} rel="noreferrer" target="_blank">
            {ui.buttons.whatsapp} <MessageCircle className="h-4 w-4" />
          </a>
        ) : null}
      </div>

      {submitError && (
        <div className="mt-5 rounded-2xl border border-red-100 bg-red-50 p-4 text-sm font-semibold leading-6 text-red-800">
          {submitError}
        </div>
      )}

      {draftReady && (
        <div className="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-semibold leading-6 text-emerald-800">
          <p className="flex items-center gap-2 font-black"><CheckCircle2 className="h-4 w-4" /> {ui.messages.successTitle}</p>
          <p className="mt-1">{ui.messages.successText}</p>
        </div>
      )}
    </form>
  );
}

function messageForInquiryError(code: string | undefined, ui: QuoteFormUiContent) {
  if (code === "invalid-request-body") return ui.messages.invalidPayload;
  if (code === "required-contact-fields") return ui.messages.requiredFields;
  return ui.messages.errorFallback;
}

function Field({ label, value, onChange, type = "text", required = false }: { label: string; value: string; onChange: (value: string) => void; type?: string; required?: boolean }) {
  return (
    <label className="block">
      <span className="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500">{label}</span>
      <input
        className="h-12 w-full rounded-2xl border border-line bg-[#f8fbff] px-4 text-sm font-bold text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100"
        required={required}
        type={type}
        value={value}
        onChange={(event) => onChange(event.target.value)}
      />
    </label>
  );
}

function readCookie(name: string) {
  return document.cookie
    .split("; ")
    .find((row) => row.startsWith(`${name}=`))
    ?.split("=")[1];
}

function utmPayload() {
  const params = new URLSearchParams(window.location.search);
  return {
    utmSource: params.get("utm_source") || undefined,
    utmMedium: params.get("utm_medium") || undefined,
    utmCampaign: params.get("utm_campaign") || undefined,
    utmTerm: params.get("utm_term") || undefined,
    utmContent: params.get("utm_content") || undefined,
  };
}
