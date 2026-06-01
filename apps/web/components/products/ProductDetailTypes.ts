import type { ComponentType } from "react";

export type ProductDetailSpec = {
  key?: string;
  label: string;
  value: string;
};

export type ProductDetailFeature = { title: string; text: string };
export type ProductDetailGalleryItem = { image: string; label: string };
export type ProductDetailFaqItem = { question: string; answer: string };
export type ProductDetailDocument = { title: string; href: string };

export type ProductApplicationCard = {
  title: string;
  text: string;
  image: string;
  icon: ComponentType<{ className?: string }>;
};

export type ProductDetailModel = {
  name: string;
  category: string;
  description: string;
  image: string;
  inquiryUrl: string;
  contactUrl: string;
  backUrl: string;
  heroSpecs: ProductDetailSpec[];
  technicalSpecs: ProductDetailSpec[];
  applications: ProductApplicationCard[];
  catalogTitle: string;
  features?: ProductDetailFeature[];
  gallery?: ProductDetailGalleryItem[];
  hotspots?: string[];
  faqs?: ProductDetailFaqItem[];
  documents?: ProductDetailDocument[];
};
