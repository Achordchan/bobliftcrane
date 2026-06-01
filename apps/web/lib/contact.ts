export type ContactSettings = {
  email: string;
  phone: string;
  phoneHref: string;
  whatsapp: string;
  whatsappHref: string;
  wechatId: string;
  wechatQr: string;
  address: string;
  contactPath: string;
  quotePath: string;
  inquirySubject: string;
  defaultInquiryText: string;
};

export function phoneToHref(phone: string) {
  const normalized = phone.replace(/[^\d+]/g, "");
  return normalized ? `tel:${normalized}` : "tel:+8615852499415";
}

export function whatsappToHref(whatsapp: string, text: string) {
  const normalized = whatsapp.replace(/\D/g, "");
  const number = normalized || "8615852499415";
  return `https://wa.me/${number}?text=${encodeURIComponent(text)}`;
}

export const defaultContactSettings: ContactSettings = {
  email: "info@bobliftcrane.com",
  phone: "+86 15852499415",
  phoneHref: "tel:+8615852499415",
  whatsapp: "+86 15852499415",
  whatsappHref:
    "https://wa.me/8615852499415?text=Hello%20BOB-LIFT%2C%20I%20would%20like%20to%20request%20a%20lifting%20solution.",
  wechatId: "15852499415",
  wechatQr: "/images/wechat-qr.png",
  address: "Xuzhou, China",
  contactPath: "/contact",
  quotePath: "/quote",
  inquirySubject: "BOB-LIFT quotation request",
  defaultInquiryText: "Hello BOB-LIFT, I would like to request a quotation.",
} as const;

export const contact = defaultContactSettings;
