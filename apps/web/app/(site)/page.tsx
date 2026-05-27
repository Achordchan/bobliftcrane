import { AboutSection } from "@/components/AboutStats";
import { Hero } from "@/components/Hero";
import { FeaturedProducts, ProductFamilies } from "@/components/ProductSections";
import { SiteChrome } from "@/components/site/SiteChrome";
import { Solutions } from "@/components/Solutions";
import { StoriesNews } from "@/components/StoriesNews";
import { TrustBar } from "@/components/TrustBar";

export default function HomePage() {
  return (
    <SiteChrome>
      <Hero />
      <TrustBar />
      <ProductFamilies />
      <FeaturedProducts />
      <Solutions />
      <AboutSection />
      <StoriesNews />
    </SiteChrome>
  );
}
