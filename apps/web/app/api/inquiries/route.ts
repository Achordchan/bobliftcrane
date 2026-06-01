import config from "@payload-config";
import { NextResponse, type NextRequest } from "next/server";
import { getPayload } from "payload";

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export async function POST(request: NextRequest) {
  let body: unknown;

  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ code: "invalid-request-body", ok: false }, { status: 400 });
  }

  if (!isInquiryPayload(body)) {
    return NextResponse.json({ code: "required-contact-fields", ok: false }, { status: 400 });
  }

  const payload = await getPayload({ config });
  const sourcePage = request.headers.get("referer") || undefined;
  const sourceUrl = sourcePage ? safeUrl(sourcePage) : null;
  const productId = body.productSlug ? await findProductId(payload, body.productSlug) : undefined;

  const inquiry = await payload.create({
    collection: "inquiries",
    data: {
      name: body.name.trim(),
      email: body.email.trim(),
      phone: cleanOptional(body.phone),
      country: cleanOptional(body.country),
      productInterest: cleanOptional(body.product),
      product: productId,
      quantity: cleanOptional(body.quantity),
      message: cleanOptional(body.message),
      locale: cleanOptional(body.locale) || request.cookies.get("boblift-locale")?.value || "en",
      sourcePage,
      sourcePath: cleanOptional(body.sourcePath) || sourceUrl?.pathname,
      utm: {
        source: cleanOptional(body.utmSource) || sourceUrl?.searchParams.get("utm_source") || undefined,
        medium: cleanOptional(body.utmMedium) || sourceUrl?.searchParams.get("utm_medium") || undefined,
        campaign: cleanOptional(body.utmCampaign) || sourceUrl?.searchParams.get("utm_campaign") || undefined,
        term: cleanOptional(body.utmTerm) || sourceUrl?.searchParams.get("utm_term") || undefined,
        content: cleanOptional(body.utmContent) || sourceUrl?.searchParams.get("utm_content") || undefined,
      },
      ipAddress: clientIp(request),
      userAgent: request.headers.get("user-agent") || undefined,
      submittedAt: new Date().toISOString(),
      priority: "normal",
      status: "new",
    },
  });

  const notification = await notifyInquiry(payload, inquiry.id, {
    ...body,
    locale: cleanOptional(body.locale) || request.cookies.get("boblift-locale")?.value || "en",
    sourcePage,
    sourcePath: cleanOptional(body.sourcePath) || sourceUrl?.pathname,
    productId,
  });

  await payload.update({
    collection: "inquiries",
    id: inquiry.id,
    data: {
      notificationStatus: notification.status,
      notifiedAt: notification.notifiedAt,
      notificationError: notification.error,
    },
  });

  return NextResponse.json({ id: inquiry.id, ok: true });
}

type IncomingInquiryPayload = {
  name: string;
  email: string;
  phone?: string;
  country?: string;
  product?: string;
  productSlug?: string;
  quantity?: string;
  message?: string;
  locale?: string;
  sourcePath?: string;
  utmSource?: string;
  utmMedium?: string;
  utmCampaign?: string;
  utmTerm?: string;
  utmContent?: string;
};

type InquiryNotificationPayload = IncomingInquiryPayload & {
  locale: string;
  productId?: number | string;
  sourcePage?: string;
  sourcePath?: string;
};

type InquiryNotificationSettings = {
  email?: string | null;
  inquiryNotificationEnabled?: boolean | null;
  inquiryNotificationRecipients?: string | null;
  inquiryNotificationSubject?: string | null;
  inquiryNotificationIntro?: string | null;
  inquiryNotificationWebhookEnvName?: string | null;
};

type NotificationResult = {
  status: "sent" | "skipped" | "failed";
  notifiedAt?: string;
  error?: string;
};

function isInquiryPayload(value: unknown): value is IncomingInquiryPayload {
  if (!value || typeof value !== "object") return false;
  const data = value as Record<string, unknown>;
  return typeof data.name === "string" && data.name.trim().length > 0 && typeof data.email === "string" && emailPattern.test(data.email.trim());
}

function cleanOptional(value: unknown) {
  return typeof value === "string" && value.trim() ? value.trim() : undefined;
}

async function findProductId(payload: Awaited<ReturnType<typeof getPayload>>, value: string) {
  const slug = normalizeProductSlug(value);
  const legacyPath = value.startsWith("/") ? value : `/${value}`;
  const result = await payload.find({
    collection: "products",
    depth: 0,
    limit: 1,
    where: {
      or: [
        { slug: { equals: slug } },
        { legacyPath: { equals: legacyPath } },
      ],
    },
  });
  return result.docs[0]?.id;
}

function normalizeProductSlug(value: string) {
  return value.trim().replace(/^\/+/, "").replace(/\.html?$/i, "");
}

function safeUrl(value: string) {
  try {
    return new URL(value);
  } catch {
    return null;
  }
}

function clientIp(request: NextRequest) {
  return request.headers.get("x-forwarded-for")?.split(",")[0]?.trim() || request.headers.get("x-real-ip") || undefined;
}

async function notifyInquiry(
  payload: Awaited<ReturnType<typeof getPayload>>,
  inquiryId: number | string,
  data: InquiryNotificationPayload,
): Promise<NotificationResult> {
  const settings = await readInquiryNotificationSettings(payload, data.locale);
  if (settings.inquiryNotificationEnabled === false) {
    return { status: "skipped", notifiedAt: new Date().toISOString() };
  }

  const recipients = parseRecipients(settings.inquiryNotificationRecipients || settings.email || "");
  const webhookUrl = webhookUrlFromSettings(settings);
  if (recipients.length === 0 && !webhookUrl) {
    return { status: "skipped", notifiedAt: new Date().toISOString(), error: "No inquiry notification recipients or webhook URL configured." };
  }

  const message = buildInquiryNotificationMessage(inquiryId, data, settings);

  try {
    if (recipients.length > 0) {
      await sendPayloadEmail(payload, {
        to: recipients,
        subject: message.subject,
        text: message.text,
        html: message.html,
      });
    }

    if (webhookUrl) {
      await fetch(webhookUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          id: inquiryId,
          name: data.name,
          email: data.email,
          phone: data.phone,
          country: data.country,
          product: data.product,
          productSlug: data.productSlug,
          productId: data.productId,
          quantity: data.quantity,
          message: data.message,
          locale: data.locale,
          sourcePage: data.sourcePage,
          sourcePath: data.sourcePath,
        }),
      });
    }

    return { status: "sent", notifiedAt: new Date().toISOString() };
  } catch (error) {
    return {
      status: "failed",
      notifiedAt: new Date().toISOString(),
      error: error instanceof Error ? error.message : String(error),
    };
  }
}

async function readInquiryNotificationSettings(payload: Awaited<ReturnType<typeof getPayload>>, locale: string): Promise<InquiryNotificationSettings> {
  try {
    return await payload.findGlobal({
      slug: "site-settings",
      depth: 0,
      fallbackLocale: "en",
      locale: locale as "en",
    }) as InquiryNotificationSettings;
  } catch {
    return {};
  }
}

function parseRecipients(value: string) {
  return value
    .split(/[\n,;]/)
    .map((item) => item.trim())
    .filter((item) => emailPattern.test(item));
}

function webhookUrlFromSettings(settings: InquiryNotificationSettings) {
  const envName = settings.inquiryNotificationWebhookEnvName?.trim() || "INQUIRY_WEBHOOK_URL";
  const url = process.env[envName]?.trim();
  return url && /^https?:\/\//i.test(url) ? url : "";
}

function buildInquiryNotificationMessage(inquiryId: number | string, data: InquiryNotificationPayload, settings: InquiryNotificationSettings) {
  const product = cleanOptional(data.product) || cleanOptional(data.productSlug) || "General inquiry";
  const subject = template(settings.inquiryNotificationSubject || "New BOB-LIFT inquiry: {product}", {
    product,
    name: data.name,
    country: data.country || "",
  });
  const lines = [
    settings.inquiryNotificationIntro || "A new inquiry has been saved in the BOB-LIFT admin. Please review and follow up.",
    "",
    `Inquiry ID: ${inquiryId}`,
    `Name: ${data.name}`,
    `Email: ${data.email}`,
    `Phone / WhatsApp: ${data.phone || "-"}`,
    `Country / Region: ${data.country || "-"}`,
    `Product Interest: ${product}`,
    `Quantity: ${data.quantity || "-"}`,
    `Locale: ${data.locale}`,
    `Source Path: ${data.sourcePath || "-"}`,
    `Source Page: ${data.sourcePage || "-"}`,
    "",
    "Project Details:",
    data.message || "-",
  ];
  const text = lines.join("\n");
  const html = `<pre style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; white-space: pre-wrap; line-height: 1.6;">${escapeHtml(text)}</pre>`;

  return { subject, text, html };
}

async function sendPayloadEmail(
  payload: Awaited<ReturnType<typeof getPayload>>,
  message: { to: string[]; subject: string; text: string; html: string },
) {
  const mailer = payload as unknown as {
    sendEmail?: (args: { to: string | string[]; subject: string; text: string; html: string }) => Promise<unknown>;
  };

  if (!mailer.sendEmail) throw new Error("Payload email sender is not available.");
  await mailer.sendEmail(message);
}

function template(value: string, variables: Record<string, string>) {
  return value.replace(/\{(\w+)\}/g, (_, key: string) => variables[key] ?? "");
}

function escapeHtml(value: string) {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}
