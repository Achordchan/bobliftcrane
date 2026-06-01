import Image from "next/image";
import { Building2, Download, FileText, Globe2, Mail, Maximize2, ShieldCheck, SlidersHorizontal, Truck, Wrench } from "lucide-react";

import { formatProductUiText, type ProductDetailUiContent } from "@/lib/product-ui";
import type { ProductDetailModel, ProductDetailSpec } from "@/components/products/ProductDetailTypes";

export function OverviewCard({
  product,
  ui,
}: {
  product: ProductDetailModel;
  ui: ProductDetailUiContent;
}) {
  const featureIcons = [Maximize2, ShieldCheck, Building2, Globe2, Truck];
  const features = product.features?.length
    ? product.features.map((feature, index) => ({
        ...feature,
        icon: featureIcons[index % featureIcons.length],
      }))
    : ui.defaultFeatures.map((feature, index) => ({
        ...feature,
        icon: featureIcons[index % featureIcons.length],
      }));

  return (
    <article
      id="overview"
      className="scroll-mt-24 rounded-[22px] border border-[#E5E7EB] bg-white p-6 shadow-[0_18px_50px_rgba(8,35,74,0.055)] md:p-8"
    >
      {ui.sectionVisibility.overview ? (
        <>
          <h2 className="text-3xl font-black leading-tight tracking-[-0.045em] text-[#0B1B33] md:text-4xl">
            {ui.overviewTitle}
          </h2>
          <p className="mt-4 text-base font-semibold leading-7 text-slate-600">
            {formatProductUiText(ui.overviewTextTemplate, {
              description: product.description,
            })}
          </p>
        </>
      ) : null}

      {ui.sectionVisibility.features ? (
        <div
          id="features"
          className="mt-7 scroll-mt-24 grid grid-cols-1 overflow-hidden rounded-[18px] border border-[#E5E7EB] sm:grid-cols-2"
        >
          {features.map(({ title, text, icon: Icon }) => (
            <div
              className="border-b border-r border-[#E5E7EB] p-5 last:border-b-0 sm:[&:nth-child(even)]:border-r-0"
              key={title}
            >
              <Icon className="h-7 w-7 text-blue-600" />
              <h3 className="mt-3 text-base font-black text-[#0B1B33]">
                {title}
              </h3>
              <p className="mt-1 text-sm font-semibold leading-6 text-slate-500">
                {text}
              </p>
            </div>
          ))}
        </div>
      ) : null}
    </article>
  );
}

export function TechnicalSpecs({
  specs,
  ui,
}: {
  specs: ProductDetailSpec[];
  ui: ProductDetailUiContent;
}) {
  return (
    <article
      id="technical"
      className="scroll-mt-24 rounded-[22px] border border-[#E5E7EB] bg-white p-6 shadow-[0_18px_50px_rgba(8,35,74,0.055)] md:p-8"
    >
      <h2 className="text-3xl font-black tracking-[-0.045em] text-[#0B1B33] md:text-4xl">
        {ui.technicalTitle}
      </h2>
      <dl className="mt-6 divide-y divide-[#E5E7EB]">
        {specs.map((spec) => (
          <div
            className="grid grid-cols-[0.9fr_1fr] gap-4 py-4"
            key={spec.label}
          >
            <dt className="text-sm font-black text-[#0B1B33]">{spec.label}</dt>
            <dd className="text-sm font-black leading-6 text-[#0B3F7D]">
              {spec.value}
            </dd>
          </div>
        ))}
      </dl>
    </article>
  );
}

export function CatalogCard({
  product,
  catalogActionLabel,
  catalogHref,
  ui,
}: {
  product: ProductDetailModel;
  catalogActionLabel: string;
  catalogHref: string;
  ui: ProductDetailUiContent;
}) {
  const documents = product.documents ?? [];
  const showActions = ui.catalogVisibility.downloadButton || ui.catalogVisibility.requestPdfButton;

  return (
    <article
      id="catalog"
      className="scroll-mt-24 rounded-[22px] border border-[#E5E7EB] bg-white p-5 shadow-[0_18px_50px_rgba(8,35,74,0.055)] md:p-6"
    >
      <div className={ui.catalogVisibility.cover ? "grid gap-5 md:grid-cols-[180px_minmax(0,1fr)] 2xl:grid-cols-1" : "grid gap-5"}>
        {ui.catalogVisibility.cover ? (
          <div className="relative min-h-[240px] overflow-hidden rounded-[18px] bg-[radial-gradient(circle_at_70%_28%,#ffffff_0%,#eaf1fb_42%,#dbe5f1_100%)] p-5 shadow-[inset_0_0_0_1px_rgba(11,63,125,0.08)]">
            <div className="relative z-10">
              <p className="text-sm font-black tracking-[0.16em] text-[#0B3F7D]">
                {ui.catalogBrandLabel}
              </p>
              <p className="mt-3 max-w-[210px] text-2xl font-black leading-none tracking-[-0.045em] text-[#0B1B33]">
                {ui.catalogCoverTitle}
              </p>
            </div>
            <span className="pointer-events-none absolute inset-x-[16%] bottom-8 h-12 rounded-full bg-slate-500/20 blur-2xl" />
            <Image
              className="z-0 object-contain object-bottom p-4 pt-20 drop-shadow-[0_20px_22px_rgba(8,35,74,0.18)]"
              src={product.image}
              alt={formatProductUiText(ui.catalogCoverAltTemplate, { product: product.name })}
              fill
              sizes="(min-width: 1536px) 430px, 180px"
            />
          </div>
        ) : null}
        <div className="flex min-w-0 flex-col justify-center">
          <p className="text-xs font-black uppercase tracking-[0.18em] text-blue-700">
            {ui.downloadCenterLabel}
          </p>
          <h2 className="mt-3 text-2xl font-black leading-tight tracking-[-0.04em] text-[#0B1B33] md:text-3xl 2xl:text-2xl">
            {ui.downloadCatalogTitle}
          </h2>
          <p className="mt-3 text-sm font-semibold leading-6 text-slate-600">
            {formatProductUiText(ui.catalogDescriptionTemplate, {
              catalogTitle: product.catalogTitle,
            })}
          </p>
          {showActions ? (
          <div className="mt-5 grid gap-3 sm:grid-cols-2 2xl:grid-cols-1">
            {ui.catalogVisibility.downloadButton ? (
              <a
                className="inline-flex h-12 items-center justify-center gap-2 whitespace-nowrap rounded-[8px] bg-[#0B3F7D] px-5 text-[13px] font-black text-white transition hover:bg-[#0B1B33]"
                href={catalogHref}
              >
                {catalogActionLabel} <Download className="h-4 w-4" />
              </a>
            ) : null}
            {ui.catalogVisibility.requestPdfButton ? (
              <a
                className="inline-flex h-12 items-center justify-center gap-2 whitespace-nowrap rounded-[8px] border border-blue-200 bg-white px-5 text-[13px] font-black text-[#0B3F7D] transition hover:border-blue-400"
                href={product.inquiryUrl}
              >
                {ui.requestPdfLabel} <Mail className="h-4 w-4" />
              </a>
            ) : null}
          </div>
          ) : null}
          {ui.catalogVisibility.extraDocumentLinks && documents.length > 1 && (
            <div className="mt-4 grid gap-2">
              {documents.slice(1).map((document) => (
                <a className="inline-flex items-center justify-between gap-3 rounded-[10px] border border-[#E5E7EB] bg-[#f8fafc] px-4 py-3 text-xs font-black text-[#0B3F7D] transition hover:border-blue-300 hover:bg-white" href={document.href} key={document.href}>
                  <span className="line-clamp-1">{document.title}</span>
                  <Download className="h-4 w-4 shrink-0" />
                </a>
              ))}
            </div>
          )}
        </div>
      </div>
    </article>
  );
}

export function ProductGallery({
  product,
  ui,
}: {
  product: ProductDetailModel;
  ui: ProductDetailUiContent;
}) {
  const [featuredImage, ...galleryImages] = product.gallery ?? [];

  return (
    <article
      id="details"
      className="scroll-mt-24 rounded-[22px] border border-[#E5E7EB] bg-white p-6 shadow-[0_18px_50px_rgba(8,35,74,0.055)]"
    >
      <h2 className="text-3xl font-black tracking-[-0.04em] text-[#0B1B33]">
        {ui.galleryTitle}
      </h2>
      <div className="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-[1.28fr_0.72fr]">
        {featuredImage ? (
          <div className="relative min-h-[360px] overflow-hidden rounded-[18px] border border-[#E5E7EB] bg-[radial-gradient(circle_at_62%_28%,#ffffff_0%,#edf3fb_43%,#dfe7f0_100%)]">
            <Image
              className="object-contain p-7 drop-shadow-[0_24px_28px_rgba(8,35,74,0.16)]"
              src={featuredImage.image}
              alt={featuredImage.label}
              fill
              sizes="(min-width: 1280px) 52vw, 92vw"
            />
            <span className="absolute bottom-5 left-5 rounded-full bg-white/92 px-4 py-2 text-xs font-black text-[#0B1B33] shadow-[0_10px_28px_rgba(8,35,74,0.12)]">
              {featuredImage.label}
            </span>
          </div>
        ) : (
          <div className="relative min-h-[360px] overflow-hidden rounded-[18px] border border-[#E5E7EB] bg-[radial-gradient(circle_at_62%_28%,#ffffff_0%,#edf3fb_43%,#dfe7f0_100%)] p-6">
            <div className="relative z-10 flex h-full flex-col justify-between">
              <div>
                <p className="text-xs font-black uppercase tracking-[0.18em] text-blue-700">
                  {ui.galleryEmptyLabel}
                </p>
                <h3 className="mt-3 max-w-[440px] text-3xl font-black leading-none tracking-[-0.045em] text-[#0B1B33]">
                  {ui.galleryEmptyTitle}
                </h3>
              </div>
              <p className="max-w-[520px] text-sm font-semibold leading-6 text-slate-600">
                {ui.galleryEmptyText}
              </p>
            </div>
            <span className="pointer-events-none absolute right-[12%] top-[20%] h-48 w-48 rounded-full bg-white/70 blur-2xl" />
            <Truck
              className="absolute bottom-8 right-8 h-36 w-36 text-blue-600/18"
              strokeWidth={1.1}
            />
          </div>
        )}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-1">
          {(galleryImages.length > 0
            ? galleryImages
            : ui.defaultGalleryLabels.slice(1).map((label) => ({ label }))
          ).map((item, index) => {
            const image =
              "image" in item && typeof item.image === "string"
                ? item.image
                : null;

            return (
              <div
                className="grid min-h-[118px] grid-cols-[86px_1fr] items-center gap-4 rounded-[16px] border border-[#E5E7EB] bg-[linear-gradient(145deg,#f8fafc_0%,#eef2f7_100%)] p-4"
                key={`${item.label}-${index}`}
              >
                <div className="relative grid h-20 w-20 place-items-center overflow-hidden rounded-[12px] bg-white/80 text-blue-600">
                  {image ? (
                    <Image
                      className="object-cover"
                      src={image}
                      alt={item.label}
                      fill
                      sizes="80px"
                    />
                  ) : (
                    <TechnicalSketch index={index + 1} />
                  )}
                </div>
                <p className="text-sm font-black leading-5 text-[#0B1B33]">
                  {item.label}
                </p>
              </div>
            );
          })}
        </div>
      </div>
    </article>
  );
}

function TechnicalSketch({ index }: { index: number }) {
  const icons = [Truck, Maximize2, ShieldCheck, SlidersHorizontal, Wrench];
  const Icon = icons[index] ?? FileText;
  return <Icon className="h-14 w-14 stroke-[1.35]" />;
}
