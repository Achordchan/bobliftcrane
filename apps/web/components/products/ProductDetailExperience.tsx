import { ProductHero, AnchorNav, SideAnchorNav } from "@/components/products/ProductDetailHeroNav";
import { CatalogCard, OverviewCard, ProductGallery, TechnicalSpecs } from "@/components/products/ProductDetailPanels";
import { ApplicationsSection, FaqSection, QuoteCta } from "@/components/products/ProductDetailSupport";
import type { ProductDetailModel } from "@/components/products/ProductDetailTypes";
import { defaultProductUiContent, type ProductDetailUiContent } from "@/lib/product-ui";

export type { ProductDetailModel, ProductDetailSpec } from "@/components/products/ProductDetailTypes";

export function ProductDetailExperience({
  product,
  ui = defaultProductUiContent.detail,
}: {
  product: ProductDetailModel;
  ui?: ProductDetailUiContent;
}) {
  const catalogRequestSeparator = product.inquiryUrl.includes("?") ? "&" : "?";
  const catalogDocumentHref = product.documents?.[0]?.href;
  const catalogHref = catalogDocumentHref ?? `${product.inquiryUrl}${catalogRequestSeparator}request=catalog`;
  const catalogActionLabel = catalogDocumentHref ? ui.downloadCatalogLabel : ui.requestPdfLabel;
  const showSideNav = ui.sectionVisibility.sideNav;
  const hasApplications = product.applications.length > 0;

  return (
    <div className="bg-[#F8FAFC] text-ink">
      <ProductHero product={product} catalogActionLabel={catalogActionLabel} catalogHref={catalogHref} ui={ui} />
      {ui.sectionVisibility.anchorNav ? <AnchorNav hasApplications={hasApplications} ui={ui} /> : null}

      <section className={showSideNav ? "page-shell grid grid-cols-1 gap-6 py-7 xl:grid-cols-[220px_minmax(0,1fr)] 2xl:grid-cols-[250px_minmax(0,1fr)]" : "page-shell grid grid-cols-1 gap-6 py-7"}>
        {showSideNav ? <SideAnchorNav hasApplications={hasApplications} ui={ui} /> : null}

        <div className="min-w-0 space-y-6">
          {ui.sectionVisibility.overview || ui.sectionVisibility.features || ui.sectionVisibility.technical ? (
            <div className="grid grid-cols-1 gap-6 xl:grid-cols-[1.05fr_0.95fr]">
              {ui.sectionVisibility.overview || ui.sectionVisibility.features ? <OverviewCard product={product} ui={ui} /> : null}
              {ui.sectionVisibility.technical ? <TechnicalSpecs specs={product.technicalSpecs} ui={ui} /> : null}
            </div>
          ) : null}

          {ui.sectionVisibility.gallery || ui.sectionVisibility.catalog ? (
            <div className="grid grid-cols-1 gap-6 2xl:grid-cols-[minmax(0,1.52fr)_minmax(430px,0.78fr)] 2xl:items-start">
              {ui.sectionVisibility.gallery ? <ProductGallery product={product} ui={ui} /> : null}
              {ui.sectionVisibility.catalog ? <CatalogCard product={product} catalogActionLabel={catalogActionLabel} catalogHref={catalogHref} ui={ui} /> : null}
            </div>
          ) : null}

          {ui.sectionVisibility.applications && hasApplications ? <ApplicationsSection applications={product.applications} ui={ui} /> : null}
          {ui.sectionVisibility.faq ? <FaqSection faqs={product.faqs} ui={ui} /> : null}
          {ui.sectionVisibility.quote ? <QuoteCta product={product} ui={ui} /> : null}
        </div>
      </section>
    </div>
  );
}
