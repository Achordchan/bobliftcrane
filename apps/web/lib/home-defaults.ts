export type HomeHeroSlide = {
  image: string;
  mobileImage?: string;
};

export type HomeHeroUiContent = {
  ariaLabel: string;
  previousSlideLabel: string;
  nextSlideLabel: string;
  slideIndicatorLabelTemplate: string;
  autoplay: boolean;
  autoplayIntervalMs: number;
  showControls: boolean;
  showIndicators: boolean;
};

export type HomeSeoContent = {
  metaTitle: string;
  metaDescription: string;
  canonicalPath: string;
};

export type HomeSectionVisibility = {
  hero: boolean;
  trustBar: boolean;
  productSection: boolean;
  aboutSection: boolean;
  newsSection: boolean;
  finalCta: boolean;
};

export type HeaderTopBarContent = {
  highlight: string;
  text: string;
  linkLabel: string;
  linkHref: string;
};

export type HomeProductSectionContent = {
  label: string;
  title: string;
  maxCategoryProducts: number;
  showCategoryScrollButtons: boolean;
  showInlineProductDetail: boolean;
  showProductSpecs: boolean;
  showProductVisualWatermark: boolean;
  findModelTitle: string;
  detailsShortLabel: string;
  viewDetailsLabel: string;
  backToModelsLabel: string;
  previousCategoriesLabel: string;
  nextCategoriesLabel: string;
  productNameLabel: string;
  modelLabel: string;
  modelSpecLabel: string;
  capacitySpecLabel: string;
  typeSpecLabel: string;
  systemSpecLabel: string;
  systemValue: string;
  visualWatermarkLabel: string;
};

export type TrustMark = {
  name: string;
  logo: string;
  logoClass?: string;
};

export type TrustBarContent = {
  label: string;
  title: string;
  description: string;
  showIntroCard: boolean;
  showMarkNames: boolean;
  marks: TrustMark[];
};

export type HomeGalleryItem = {
  src: string;
  alt: string;
  video?: boolean;
  videoUrl?: string;
};

export type HomeStatItem = {
  value: string;
  label: string;
  iconName?: string;
};

export type AboutSectionContent = {
  label: string;
  title: string;
  text: string;
  ctaLabel: string;
  ctaHref: string;
  showGallery: boolean;
  showStats: boolean;
  showTimeline: boolean;
  showCta: boolean;
  galleryThumbnailLabelTemplate: string;
  videoLabel: string;
  gallery: HomeGalleryItem[];
  stats: HomeStatItem[];
  timeline: string[];
};

export type NewsSectionContent = {
  label: string;
  title: string;
  ctaLabel: string;
  ctaHref: string;
  showCta: boolean;
  showExcerpt: boolean;
  showDate: boolean;
  showCategory: boolean;
  maxArticles: number;
  readMoreLabel: string;
  pinnedLabel: string;
  backgroundWord: string;
};

export type FinalCtaContent = {
  label: string;
  title: string;
  buttonLabel: string;
  buttonHref: string;
  image: string;
};

export type FooterLink = {
  label: string;
  href: string;
};

export type FooterLinkGroup = {
  title: string;
  links: FooterLink[];
};

export type FooterContent = {
  intro: string;
  socialLinks: FooterLink[];
  groups: FooterLinkGroup[];
  contactTitle: string;
  copyright: string;
  legalLinks: FooterLink[];
};

export const defaultHomeHeroSlides: HomeHeroSlide[] = [
  {
    image: "/images/marine-scene.jpg",
  },
  {
    image: "/images/telescopic-banner.jpg",
  },
  {
    image: "/images/knuckle-banner.jpg",
  },
];

export const defaultHomeHeroUiContent: HomeHeroUiContent = {
  ariaLabel: "Featured lifting solutions",
  previousSlideLabel: "Previous banner",
  nextSlideLabel: "Next banner",
  slideIndicatorLabelTemplate: "Show banner {index}",
  autoplay: true,
  autoplayIntervalMs: 5600,
  showControls: true,
  showIndicators: true,
};

export const defaultHomeSeoContent: HomeSeoContent = {
  metaTitle: "BOB-LIFT | Truck Mounted Cranes and Lifting Solutions",
  metaDescription: "BOB-LIFT supplies truck mounted cranes, marine cranes, aerial work platforms and lifting attachments for global projects.",
  canonicalPath: "/",
};

export const defaultHomeSectionVisibility: HomeSectionVisibility = {
  hero: true,
  trustBar: true,
  productSection: true,
  aboutSection: true,
  newsSection: true,
  finalCta: true,
};

export const defaultHeaderTopBarContent: HeaderTopBarContent = {
  highlight: "Global Lifting Solutions.",
  text: "Exported to 80+ Countries Worldwide.",
  linkLabel: "Learn More +",
  linkHref: "/about",
};

export const defaultHomeProductSectionContent: HomeProductSectionContent = {
  label: "Main Products",
  title: "BOB-LIFT Crane Solutions",
  maxCategoryProducts: 8,
  showCategoryScrollButtons: true,
  showInlineProductDetail: true,
  showProductSpecs: true,
  showProductVisualWatermark: true,
  findModelTitle: "Find Your Model",
  detailsShortLabel: "Details",
  viewDetailsLabel: "View Details",
  backToModelsLabel: "Back to Models",
  previousCategoriesLabel: "Previous product categories",
  nextCategoriesLabel: "Next product categories",
  productNameLabel: "Product Name",
  modelLabel: "Model",
  modelSpecLabel: "Model",
  capacitySpecLabel: "Capacity",
  typeSpecLabel: "Type",
  systemSpecLabel: "System",
  systemValue: "Hydraulic",
  visualWatermarkLabel: "BOB-LIFT",
};

export const defaultTrustBarContent: TrustBarContent = {
  label: "Certified",
  title: "Quality Assurance",
  description: "Export-ready documentation.",
  showIntroCard: true,
  showMarkNames: true,
  marks: [
    { name: "Quality Management", logo: "/certifications/iso.svg", logoClass: "h-8 w-24 md:h-12 md:w-32" },
    { name: "European Conformity", logo: "/certifications/ce.svg", logoClass: "h-9 w-20 md:h-14 md:w-28" },
    { name: "Russia / EAEU Conformity", logo: "/certifications/eac.svg", logoClass: "h-9 w-24 md:h-14 md:w-32" },
    { name: "Inspection & Certification", logo: "/certifications/sgs.svg", logoClass: "h-8 w-24 md:h-12 md:w-32" },
  ],
};

export const defaultAboutSectionContent: AboutSectionContent = {
  label: "About BOB-LIFT",
  title: "Engineering Excellence. Powered by People.",
  text: "BOB-LIFT is a global manufacturer of advanced lifting solutions with 35+ years of experience. We combine innovation, precision and passion to deliver cranes that exceed expectations worldwide.",
  ctaLabel: "More About Us",
  ctaHref: "/about",
  showGallery: true,
  showStats: true,
  showTimeline: true,
  showCta: true,
  galleryThumbnailLabelTemplate: "Show {title}",
  videoLabel: "Video",
  gallery: [
    { src: "/images/truck-mounted-crane.png", alt: "Truck mounted crane" },
    { src: "/images/factory-workshop.jpg", alt: "BOB-LIFT factory video preview", video: true },
    { src: "/images/marine-scene.jpg", alt: "Marine crane project" },
    { src: "/images/telescopic-banner.jpg", alt: "Telescopic boom crane" },
    { src: "/images/knuckle-banner.jpg", alt: "Knuckle boom crane" },
  ],
  stats: [
    { value: "35+", label: "Years Experience", iconName: "award" },
    { value: "120+", label: "Export Countries", iconName: "globe" },
    { value: "9999+", label: "Cranes Exported", iconName: "package-check" },
  ],
  timeline: ["1989 Company Founded", "2005 Global Export Expansion", "2015 Advanced R&D Center Established", "2022 Smart Manufacturing Upgraded"],
};

export const defaultNewsSectionContent: NewsSectionContent = {
  label: "News Center",
  title: "Latest Insights from BOB-LIFT.",
  ctaLabel: "View All News",
  ctaHref: "/news",
  showCta: true,
  showExcerpt: true,
  showDate: true,
  showCategory: true,
  maxArticles: 4,
  readMoreLabel: "Read More",
  pinnedLabel: "Pinned News",
  backgroundWord: "NEWS",
};

export const defaultFinalCtaContent: FinalCtaContent = {
  label: "Project Consultation",
  title: "Need a Lifting Solution?",
  buttonLabel: "Request a Quote",
  buttonHref: "/quote",
  image: "/images/cta-lift-photo.png",
};

export const defaultFooterContent: FooterContent = {
  intro: "Engineered lifting solutions for global projects. Built to lift. Built to last.",
  socialLinks: [
    { label: "LinkedIn", href: "/contact" },
    { label: "YouTube", href: "/contact" },
    { label: "Facebook", href: "/contact" },
  ],
  groups: [
    {
      title: "Products",
      links: [
        { label: "Telescopic Boom Truck Mounted Cranes", href: "/products#telescopic-boom-truck-mounted-cranes" },
        { label: "Knuckle Boom Truck Mounted Cranes", href: "/products#knuckle-boom-truck-mounted-cranes" },
        { label: "Marine Cranes", href: "/products#knuckle-boom-marine-cranes" },
        { label: "Pickup Cranes", href: "/products#pickup-cranes" },
        { label: "Mini Dumpers", href: "/products#mini-dumpers" },
      ],
    },
    {
      title: "Company",
      links: [
        { label: "About BOB-LIFT", href: "/about" },
        { label: "Customer Cases", href: "/cases" },
        { label: "Company News", href: "/news" },
        { label: "Contact Us", href: "/contact" },
      ],
    },
  ],
  contactTitle: "Contact Us",
  copyright: "© 2026 BOB-LIFT. All Rights Reserved.",
  legalLinks: [
    { label: "Privacy Policy", href: "/contact" },
    { label: "Terms of Use", href: "/contact" },
    { label: "Sitemap", href: "/contact" },
  ],
};
