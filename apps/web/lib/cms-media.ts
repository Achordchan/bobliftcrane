import type { Media } from "@/payload-types";

export function mediaUrl(value: unknown, size?: "thumbnail" | "card" | "hero") {
  if (!value || typeof value === "number") return null;
  const media = value as Media;
  return (size ? media.sizes?.[size]?.url : null) ?? media.url ?? null;
}
