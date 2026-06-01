import { CheckCircle2, Clock3, Flame, Wrench } from "lucide-react";
import type { ComponentType } from "react";

import { defaultProductUiContent, productStatusLabel, type ProductListingUiContent } from "@/lib/product-ui";
import type { ProductStatus } from "@/types/product-listing";

type BadgeConfig = {
  icon: ComponentType<{ className?: string; strokeWidth?: number }>;
  wrapper: string;
  iconColor: string;
};

const statusConfig: Record<ProductStatus, BadgeConfig> = {
  "in-stock": {
    icon: CheckCircle2,
    wrapper: "text-emerald-700",
    iconColor: "text-emerald-600",
  },
  "best-seller": {
    icon: CheckCircle2,
    wrapper: "text-blue-700",
    iconColor: "text-blue-600",
  },
  customizable: {
    icon: Wrench,
    wrapper: "text-indigo-700",
    iconColor: "text-indigo-600",
  },
  "pre-order": {
    icon: Clock3,
    wrapper: "text-orange-700",
    iconColor: "text-orange-600",
  },
  "In Stock": {
    icon: CheckCircle2,
    wrapper: "text-emerald-700",
    iconColor: "text-emerald-600",
  },
  "Best Seller": {
    icon: CheckCircle2,
    wrapper: "text-blue-700",
    iconColor: "text-blue-600",
  },
  Customizable: {
    icon: Wrench,
    wrapper: "text-indigo-700",
    iconColor: "text-indigo-600",
  },
  "Pre-Order": {
    icon: Clock3,
    wrapper: "text-orange-700",
    iconColor: "text-orange-600",
  },
};

export function ProductBadge({ status, className = "", ui = defaultProductUiContent.listing }: { status: ProductStatus; className?: string; ui?: ProductListingUiContent }) {
  const config = statusConfig[status] ?? statusConfig.customizable;
  const Icon = status === "best-seller" || status === "Best Seller" ? Flame : config.icon;
  const iconColor = status === "best-seller" || status === "Best Seller" ? "text-red-500" : config.iconColor;

  return (
    <span
      className={`inline-flex items-center gap-2 rounded-full bg-white/92 py-2 pl-3 pr-5 text-sm font-black leading-none shadow-[0_14px_32px_rgba(8,35,74,0.12)] ring-1 ring-[#E5E7EB] backdrop-blur ${config.wrapper} ${className}`}
    >
      <Icon className={`h-5 w-5 ${iconColor}`} strokeWidth={2.2} />
      {productStatusLabel(status, ui)}
    </span>
  );
}
