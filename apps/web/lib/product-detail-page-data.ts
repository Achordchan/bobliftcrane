import { Building2, Construction, Ship, Tractor, Truck, Wrench } from "lucide-react";
import type { ComponentType } from "react";

import type { ProductDetailModel, ProductDetailSpec } from "@/components/products/ProductDetailExperience";
import { localizedPath } from "@/lib/i18n";
import { formatProductUiText, type ProductDetailUiContent } from "@/lib/product-ui";
import type { Product, ProductApplication, ProductApplicationOption } from "@/types/product-listing";

type ApplicationInfo = {
  title: string;
  text: string;
  image: string;
  icon: ComponentType<{ className?: string }>;
};

const applicationIconByName: Record<string, ComponentType<{ className?: string }>> = {
  construction: Construction,
  truck: Truck,
  building: Building2,
  ship: Ship,
  tractor: Tractor,
  wrench: Wrench,
};

export function productDetailFromListing(product: Product, ui: ProductDetailUiContent, locale: string): ProductDetailModel {
  const model = product.model ?? modelFromName(product.name);
  const applicationOptions = ui.applicationCards;
  const reviewStatus = parameterReviewLabel(product.parameterReviewStatus, ui);
  const sourceSpecs = product.specs.length > 0 ? product.specs : [detailSpec("specificationStatus", reviewStatus, ui)];
  const heroSpecs = sourceSpecs.slice(0, boundedCount(ui.maxHeroSpecs, 1, 8));

  return {
    name: product.name,
    category: product.category,
    description: product.shortDescription,
    image: product.image,
    inquiryUrl: product.inquiryUrl,
    contactUrl: localizedPath("/contact", locale),
    backUrl: localizedPath("/products", locale),
    catalogTitle: formatProductUiText(ui.catalogTitleTemplate, { model, product: product.name }),
    heroSpecs: heroSpecs.length > 0 ? heroSpecs : [detailSpec("specificationStatus", reviewStatus, ui)],
    technicalSpecs: [
      detailSpec("model", model, ui),
      ...sourceSpecs,
      detailSpec("parameterReview", reviewStatus, ui),
    ],
    applications: applicationCards(product.applications, applicationOptions, ui.maxApplicationCards),
  };
}

export function applicationCardFromCms(card: { title: string; text: string; image: string; iconName: string }): ApplicationInfo {
  return {
    title: card.title,
    text: card.text,
    image: card.image,
    icon: applicationIconByName[card.iconName] ?? Construction,
  };
}

function parameterReviewLabel(status: Product["parameterReviewStatus"], ui: ProductDetailUiContent) {
  if (status === "verified-from-source") return ui.reviewStatusLabels.verifiedFromSource;
  if (status === "in-review") return ui.reviewStatusLabels.inReview;
  if (status === "changes-required") return ui.reviewStatusLabels.changesRequired;
  return ui.reviewStatusLabels.needsReview;
}

function detailSpec(key: string, value: string, ui: ProductDetailUiContent): ProductDetailSpec {
  return { key, label: ui.specLabels[key] ?? key, value };
}

function modelFromName(name: string) {
  return name.match(/BOB-LIFT\s+([^\s]+)/i)?.[1] ?? name.split(" ")[0] ?? "BOB-LIFT";
}

function applicationCards(applications: ProductApplication[], applicationOptions: ProductApplicationOption[], maxCards: number): ApplicationInfo[] {
  const optionByValue = new Map(applicationOptions.map((option) => [option.value, option]));
  const selected = applications.flatMap((application) => {
    const option = optionByValue.get(application);
    return option ? [applicationCardFromOption(option)] : [];
  });
  return selected.slice(0, boundedCount(maxCards, 1, 8));
}

function applicationCardFromOption(option: ProductApplicationOption): ApplicationInfo {
  return {
    title: option.title || option.label,
    text: option.text,
    image: option.image,
    icon: applicationIconByName[option.iconName] ?? Construction,
  };
}

function boundedCount(value: number, min: number, max: number) {
  return Number.isInteger(value) ? Math.min(max, Math.max(min, value)) : min;
}
