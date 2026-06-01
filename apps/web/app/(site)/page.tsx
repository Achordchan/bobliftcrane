import { AboutSection } from "@/components/AboutStats";
import { Hero } from "@/components/Hero";
import { FeaturedProducts, ProductFamilies } from "@/components/ProductSections";
import { SiteChrome } from "@/components/site/SiteChrome";
import { Solutions } from "@/components/Solutions";
import { StoriesNews } from "@/components/StoriesNews";
import { TrustBar } from "@/components/TrustBar";
import { getCmsHomeSeo, getHomeContent } from "@/lib/cms-content";
import { localizedSeoMetadata } from "@/lib/seo-metadata";
import { getRequestLocale } from "@/lib/server-locale";

export const dynamic = "force-dynamic";

export async function generateMetadata() {
  const seo = await getCmsHomeSeo();
  return localizedSeoMetadata(seo);
}

export default async function HomePage() {
  const [homeContent, locale] = await Promise.all([getHomeContent(), getRequestLocale()]);

  return (
    <SiteChrome showFinalCta={homeContent.sectionVisibility.finalCta}>
      {homeContent.sectionVisibility.hero ? <Hero slides={homeContent.heroSlides} ui={homeContent.heroUi} /> : null}
      {homeContent.sectionVisibility.trustBar ? <TrustBar content={homeContent.trustBar} /> : null}
      {homeContent.sectionVisibility.productSection ? <ProductFamilies catalog={homeContent.productCatalog} section={homeContent.productSection} /> : null}
      {homeContent.sectionVisibility.featuredProducts ? <FeaturedProducts products={homeContent.featuredProducts} section={homeContent.productSection} /> : null}
      {homeContent.sectionVisibility.applicationSection ? <Solutions content={homeContent.applicationSection} /> : null}
      {homeContent.sectionVisibility.aboutSection ? <AboutSection content={homeContent.aboutSection} /> : null}
      {homeContent.sectionVisibility.newsSection ? <StoriesNews articles={homeContent.articles} content={homeContent.newsSection} locale={locale} /> : null}
    </SiteChrome>
  );
}
