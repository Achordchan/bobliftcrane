"use client";

import { useMemo, useState } from "react";
import { CheckCircle2, Mail, MessageCircle, Send } from "lucide-react";

import { contact } from "@/lib/contact";
import { allProductEntries } from "@/lib/product-catalog";

type FormState = {
  name: string;
  email: string;
  phone: string;
  country: string;
  product: string;
  quantity: string;
  message: string;
};

const initialState: FormState = {
  name: "",
  email: "",
  phone: "",
  country: "",
  product: "",
  quantity: "",
  message: "",
};

export function QuoteForm({ compact = false }: { compact?: boolean }) {
  const [form, setForm] = useState<FormState>(initialState);
  const [draftReady, setDraftReady] = useState(false);
  const productOptions = useMemo(() => allProductEntries().slice(0, 18), []);

  const inquiryText = useMemo(() => {
    const lines = [
      "Hello BOB-LIFT, I would like to request a quotation.",
      `Name: ${form.name || "-"}`,
      `Email: ${form.email || "-"}`,
      `Phone / WhatsApp: ${form.phone || "-"}`,
      `Country: ${form.country || "-"}`,
      `Product: ${form.product || "Not decided yet"}`,
      `Quantity: ${form.quantity || "-"}`,
      `Project details: ${form.message || "-"}`,
    ];
    return lines.join("\n");
  }, [form]);

  const mailHref = `mailto:${contact.email}?subject=${encodeURIComponent("BOB-LIFT quotation request")}&body=${encodeURIComponent(inquiryText)}`;
  const whatsappHref = `https://wa.me/8615852499415?text=${encodeURIComponent(inquiryText)}`;

  function updateField(field: keyof FormState, value: string) {
    setDraftReady(false);
    setForm((current) => ({ ...current, [field]: value }));
  }

  return (
    <form
      className={`rounded-[28px] border border-line bg-white/94 shadow-[0_30px_90px_rgba(8,35,74,0.1)] ${compact ? "p-4 md:p-5" : "p-5 md:p-8"}`}
      onSubmit={(event) => {
        event.preventDefault();
        setDraftReady(true);
      }}
    >
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="section-label mb-3">Request Quote</p>
          <h2 className="text-2xl font-black leading-tight tracking-[-0.04em] text-ink md:text-4xl">Tell us your lifting requirement.</h2>
        </div>
        <span className="hidden rounded-2xl bg-navy px-4 py-2 text-xs font-black text-white md:inline-flex">24h reply</span>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <Field label="Full Name" value={form.name} onChange={(value) => updateField("name", value)} required />
        <Field label="Email" type="email" value={form.email} onChange={(value) => updateField("email", value)} required />
        <Field label="Phone / WhatsApp" value={form.phone} onChange={(value) => updateField("phone", value)} />
        <Field label="Country / Region" value={form.country} onChange={(value) => updateField("country", value)} />
        <label className="block md:col-span-2">
          <span className="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500">Product Interest</span>
          <select
            className="h-12 w-full rounded-2xl border border-line bg-[#f8fbff] px-4 text-sm font-bold text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100"
            value={form.product}
            onChange={(event) => updateField("product", event.target.value)}
          >
            <option value="">Not decided yet</option>
            {productOptions.map(({ product }) => (
              <option key={product.model} value={`${product.model} ${product.name.replace(product.model, "").trim()}`}>
                {product.model} · {product.name.replace(product.model, "").trim()}
              </option>
            ))}
          </select>
        </label>
        <Field label="Expected Quantity" value={form.quantity} onChange={(value) => updateField("quantity", value)} />
        <label className="block md:col-span-2">
          <span className="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500">Project Details</span>
          <textarea
            className="min-h-[140px] w-full resize-y rounded-2xl border border-line bg-[#f8fbff] px-4 py-3 text-sm font-semibold leading-6 text-ink outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100"
            placeholder="Load capacity, working radius, truck chassis, destination port, deadline..."
            value={form.message}
            onChange={(event) => updateField("message", event.target.value)}
          />
        </label>
      </div>

      <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <button className="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-navy px-7 text-sm font-black text-white shadow-[0_18px_42px_rgba(8,35,74,0.18)] transition hover:-translate-y-0.5 hover:bg-[#0b2d5b]" type="submit">
          Create Inquiry Draft <Send className="h-4 w-4" />
        </button>
        <a className="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-line bg-white px-7 text-sm font-black text-ink transition hover:-translate-y-0.5 hover:border-gold" href={mailHref}>
          Send Email <Mail className="h-4 w-4" />
        </a>
        <a className="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-7 text-sm font-black text-emerald-700 transition hover:-translate-y-0.5" href={whatsappHref} rel="noreferrer" target="_blank">
          WhatsApp <MessageCircle className="h-4 w-4" />
        </a>
      </div>

      {draftReady && (
        <div className="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-semibold leading-6 text-emerald-800">
          <p className="flex items-center gap-2 font-black"><CheckCircle2 className="h-4 w-4" /> Inquiry draft is ready.</p>
          <p className="mt-1">Use Email or WhatsApp to send it through your preferred app.</p>
        </div>
      )}
    </form>
  );
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
