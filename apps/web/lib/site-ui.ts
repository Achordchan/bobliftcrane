export type SiteLanguageOption = {
  locale: string;
  label: string;
  flag: string;
  market: string;
  direction: "ltr" | "rtl";
  translationStatus: "not-started" | "in-progress" | "ready";
  completionPercent: number;
  enabled: boolean;
  sortOrder: number;
};

export type ChromeUiContent = {
  languageMenuAriaLabel: string;
  mainNavigationLabel: string;
  logoHomeLabel: string;
  logoImageAlt: string;
  mobileMenuAriaLabel: string;
  mobileMenuEyebrow: string;
  mobileMenuTitle: string;
  mobileProductCategoriesLabel: string;
  requestQuoteLabel: string;
  productCatalogTitle: string;
  productCatalogDescription: string;
  viewProductCatalogLabel: string;
  floatingQuoteLabel: string;
  floatingQuoteTooltip: string;
  floatingEmailLabel: string;
  floatingWhatsAppLabel: string;
  floatingCallLabel: string;
  floatingWeChatLabel: string;
  weChatTitle: string;
  weChatDescriptionTemplate: string;
  weChatQrAlt: string;
  closeWeChatLabel: string;
  backToTopLabel: string;
};

export const defaultLanguageOptions: SiteLanguageOption[] = [
  { locale: "en", label: "English", flag: "/flags/us.svg", market: "United States / Global", direction: "ltr", translationStatus: "ready", completionPercent: 100, enabled: true, sortOrder: 10 },
  { locale: "es", label: "Español", flag: "/flags/es.svg", market: "Spain / LATAM", direction: "ltr", translationStatus: "in-progress", completionPercent: 85, enabled: false, sortOrder: 20 },
  { locale: "ru", label: "Русский", flag: "/flags/ru.svg", market: "Russia / CIS", direction: "ltr", translationStatus: "not-started", completionPercent: 0, enabled: false, sortOrder: 30 },
  { locale: "ar", label: "العربية", flag: "/flags/sa.svg", market: "MENA / Arabic", direction: "rtl", translationStatus: "in-progress", completionPercent: 85, enabled: false, sortOrder: 40 },
  { locale: "pt", label: "Português", flag: "/flags/br.svg", market: "Brazil / Lusophone", direction: "ltr", translationStatus: "not-started", completionPercent: 0, enabled: false, sortOrder: 50 },
  { locale: "fr", label: "Français", flag: "/flags/fr.svg", market: "France / Africa", direction: "ltr", translationStatus: "not-started", completionPercent: 0, enabled: false, sortOrder: 60 },
  { locale: "de", label: "Deutsch", flag: "/flags/de.svg", market: "Germany / EU", direction: "ltr", translationStatus: "not-started", completionPercent: 0, enabled: false, sortOrder: 70 },
  { locale: "tr", label: "Türkçe", flag: "/flags/tr.svg", market: "Türkiye / Central Asia", direction: "ltr", translationStatus: "not-started", completionPercent: 0, enabled: false, sortOrder: 80 },
  { locale: "id", label: "Indonesia", flag: "/flags/id.svg", market: "Indonesia / ASEAN", direction: "ltr", translationStatus: "not-started", completionPercent: 0, enabled: false, sortOrder: 90 },
  { locale: "vi", label: "Tiếng Việt", flag: "/flags/vn.svg", market: "Vietnam", direction: "ltr", translationStatus: "not-started", completionPercent: 0, enabled: false, sortOrder: 100 },
  { locale: "th", label: "ไทย", flag: "/flags/th.svg", market: "Thailand", direction: "ltr", translationStatus: "not-started", completionPercent: 0, enabled: false, sortOrder: 110 },
  { locale: "hi", label: "हिन्दी", flag: "/flags/in.svg", market: "India", direction: "ltr", translationStatus: "not-started", completionPercent: 0, enabled: false, sortOrder: 120 },
  { locale: "zh", label: "中文", flag: "/flags/cn.svg", market: "China / Global", direction: "ltr", translationStatus: "in-progress", completionPercent: 85, enabled: false, sortOrder: 130 },
  { locale: "ko", label: "한국어", flag: "/flags/kr.svg", market: "Korea", direction: "ltr", translationStatus: "not-started", completionPercent: 0, enabled: false, sortOrder: 140 },
];

export const defaultChromeUiContent: ChromeUiContent = {
  languageMenuAriaLabel: "Change language",
  mainNavigationLabel: "Main navigation",
  logoHomeLabel: "BOB-LIFT Home",
  logoImageAlt: "BOB-LIFT Cranes",
  mobileMenuAriaLabel: "Open mobile navigation",
  mobileMenuEyebrow: "Navigation",
  mobileMenuTitle: "BOB-LIFT Menu",
  mobileProductCategoriesLabel: "Product Categories",
  requestQuoteLabel: "Request Quote",
  productCatalogTitle: "Explore All Products",
  productCatalogDescription: "Discover our full range of lifting solutions.",
  viewProductCatalogLabel: "View Product Catalog",
  floatingQuoteLabel: "Quote",
  floatingQuoteTooltip: "Request Quote",
  floatingEmailLabel: "Email",
  floatingWhatsAppLabel: "WhatsApp",
  floatingCallLabel: "Call",
  floatingWeChatLabel: "WeChat",
  weChatTitle: "WeChat QR Code",
  weChatDescriptionTemplate: "Scan or search ID: {wechatId}",
  weChatQrAlt: "BOB-LIFT WeChat QR code",
  closeWeChatLabel: "Close WeChat QR code",
  backToTopLabel: "Back to top",
};

export type ContactPageUiContent = {
  metaTitle: string;
  metaDescription: string;
  canonicalPath: string;
  heroLabel: string;
  heroTitle: string;
  heroDescription: string;
  heroImage: string;
  cardLabels: { email: string; phone: string; whatsapp: string; address: string };
  whatsappValue: string;
  weChatTitle: string;
  weChatDescriptionTemplate: string;
  weChatQrAlt: string;
};

export type QuoteWorkflowItem = { title: string; text: string; iconName: "file-text" | "mail-check" | "message-circle" };

export type QuotePageUiContent = {
  metaTitle: string;
  metaDescription: string;
  canonicalPath: string;
  heroLabel: string;
  heroTitle: string;
  heroDescription: string;
  heroImage: string;
  workflowLabel: string;
  workflowTitle: string;
  workflowItems: QuoteWorkflowItem[];
  productLinkLabel: string;
  productLinkHref: string;
};

export type QuoteFormUiContent = {
  label: string;
  title: string;
  badge: string;
  fields: {
    name: string;
    email: string;
    phone: string;
    country: string;
    product: string;
    productNotDecided: string;
    quantity: string;
    message: string;
    messagePlaceholder: string;
  };
  buttons: { save: string; saving: string; email: string; whatsapp: string };
  productOptionLimit: number;
  fieldControls: Record<"name" | "email" | "phone" | "country" | "product" | "quantity" | "message", { visible: boolean; required: boolean }>;
  buttonChannels: { save: boolean; email: boolean; whatsapp: boolean };
  messages: { successTitle: string; successText: string; errorFallback: string; invalidPayload: string; requiredFields: string };
  inquiryLineLabels: { name: string; email: string; phone: string; country: string; product: string; quantity: string; message: string };
};


export type NewsPageUiContent = {
  metaTitle: string;
  metaDescription: string;
  canonicalPath: string;
  heroLabel: string;
  heroTitle: string;
  heroDescription: string;
  heroImage: string;
  readFeaturedLabel: string;
  requestQuoteLabel: string;
  featuredLabel: string;
  readArticleLabel: string;
  readMoreLabel: string;
};

export type NewsDetailUiContent = {
  backLabel: string;
  relatedLabel: string;
  quoteCtaLabel: string;
  relatedArticleCount: number;
};

export type CasesPageUiContent = {
  metaTitle: string;
  metaDescription: string;
  canonicalPath: string;
  heroLabel: string;
  heroTitle: string;
  heroDescription: string;
  heroImage: string;
  primaryCtaLabel: string;
  secondaryCtaLabel: string;
  listLabel: string;
  listTitle: string;
  cardTypeLabel: string;
  viewCaseLabel: string;
};

export type CaseDetailUiContent = {
  backLabel: string;
  factsTitle: string;
  discussSimilarLabel: string;
};

export type AboutPageUiContent = {
  metaTitle: string;
  metaDescription: string;
  canonicalPath: string;
  primaryCtaLabel: string;
  secondaryCtaLabel: string;
  strengthLabel: string;
  strengthTitle: string;
  timelineLabel: string;
  galleryImageCount: number;
};

export type ContentLabelsUiContent = {
  articleCategories: { companyNews: string; productNews: string; industryInsight: string };
  caseFacts: { region: string; industry: string; priority: string; defaultPriority: string; defaultCaseType: string };
};

export type SeoFallbacksUiContent = {
  siteTitle: string;
  siteDescription: string;
  genericTitle: string;
  genericDescription: string;
  productTitle: string;
  productTitleTemplate: string;
  productDescription: string;
  productDescriptionTemplate: string;
  productsTitle: string;
  productsTitleTemplate: string;
  productsDescription: string;
  productCategoryDescriptionTemplate: string;
  productNavGroupDescription: string;
  articleTitle: string;
  articleTitleTemplate: string;
  articleDescription: string;
  articleExcerpt: string;
  caseTitle: string;
  caseTitleTemplate: string;
  caseDescription: string;
  caseSummary: string;
};

export type PageUiContent = {
  seoFallbacks: SeoFallbacksUiContent;
  contactPage: ContactPageUiContent;
  quotePage: QuotePageUiContent;
  quoteForm: QuoteFormUiContent;
  newsPage: NewsPageUiContent;
  newsDetail: NewsDetailUiContent;
  casesPage: CasesPageUiContent;
  caseDetail: CaseDetailUiContent;
  aboutPage: AboutPageUiContent;
  contentLabels: ContentLabelsUiContent;
};

export const defaultPageUiContent: PageUiContent = {
  seoFallbacks: {
    siteTitle: "BOB-LIFT | Engineered Lifting Power for Global Projects",
    siteDescription: "BOB-LIFT manufactures truck mounted cranes, marine cranes and custom lifting solutions for global projects.",
    genericTitle: "BOB-LIFT",
    genericDescription: "BOB-LIFT lifting solutions.",
    productTitle: "Product | BOB-LIFT",
    productTitleTemplate: "{title} | BOB-LIFT",
    productDescription: "BOB-LIFT product detail page.",
    productDescriptionTemplate: "{product} BOB-LIFT product detail page.",
    productsTitle: "Products | BOB-LIFT",
    productsTitleTemplate: "{title} | BOB-LIFT",
    productsDescription: "Browse BOB-LIFT product categories and crane models.",
    productCategoryDescriptionTemplate: "{category} product list from BOB-LIFT.",
    productNavGroupDescription: "BOB-LIFT product category",
    articleTitle: "Article | BOB-LIFT",
    articleTitleTemplate: "{title} | BOB-LIFT",
    articleDescription: "BOB-LIFT article detail page.",
    articleExcerpt: "BOB-LIFT product and project update.",
    caseTitle: "Case | BOB-LIFT",
    caseTitleTemplate: "{title} | BOB-LIFT Case",
    caseDescription: "BOB-LIFT customer case detail page.",
    caseSummary: "BOB-LIFT customer project case.",
  },
  contactPage: {
    metaTitle: "Contact Us | BOB-LIFT",
    metaDescription: "Contact BOB-LIFT by email, phone, WhatsApp or WeChat for crane quotation and technical consultation.",
    canonicalPath: "/contact",
    heroLabel: "Contact Us",
    heroTitle: "Talk to BOB-LIFT before choosing a crane.",
    heroDescription: "For model selection, truck chassis matching, marine installation and export questions, use the direct contact channels or create a structured inquiry draft.",
    heroImage: "/images/global-network-blue.jpg",
    cardLabels: { email: "Email", phone: "Phone", whatsapp: "WhatsApp", address: "Address" },
    whatsappValue: "Start chat",
    weChatTitle: "WeChat",
    weChatDescriptionTemplate: "Scan the QR code or search ID {wechatId}. The QR block is kept as a compact card so it does not dominate mobile screens.",
    weChatQrAlt: "BOB-LIFT WeChat QR code",
  },
  quotePage: {
    metaTitle: "Request Quote | BOB-LIFT",
    metaDescription: "Send BOB-LIFT your lifting requirement and prepare a quotation inquiry by email or WhatsApp.",
    canonicalPath: "/quote",
    heroLabel: "Request Quote",
    heroTitle: "Request a tailored BOB-LIFT quotation.",
    heroDescription: "Share your product interest, destination country, contact details and working conditions. The team can then match a practical lifting solution for your project.",
    heroImage: "/images/cta-lift-photo.png",
    workflowLabel: "Quote Workflow",
    workflowTitle: "Fill once, send through your preferred channel.",
    workflowItems: [
      { iconName: "file-text", title: "Describe requirement", text: "Model, quantity, load and working radius." },
      { iconName: "mail-check", title: "Create inquiry draft", text: "Your details become a clean quotation message." },
      { iconName: "message-circle", title: "Send by Email or WhatsApp", text: "You control the final sending channel." },
    ],
    productLinkLabel: "Need to choose a model first?",
    productLinkHref: "/products",
  },
  quoteForm: {
    label: "Request Quote",
    title: "Tell us your lifting requirement.",
    badge: "24h reply",
    fields: {
      name: "Full Name",
      email: "Email",
      phone: "Phone / WhatsApp",
      country: "Country / Region",
      product: "Product Interest",
      productNotDecided: "Not decided yet",
      quantity: "Expected Quantity",
      message: "Project Details",
      messagePlaceholder: "Load capacity, working radius, truck chassis, destination port, deadline...",
    },
    buttons: { save: "Save Inquiry Draft", saving: "Saving Inquiry...", email: "Send Email", whatsapp: "WhatsApp" },
    productOptionLimit: 0,
    fieldControls: {
      name: { visible: true, required: true },
      email: { visible: true, required: true },
      phone: { visible: true, required: false },
      country: { visible: true, required: false },
      product: { visible: true, required: false },
      quantity: { visible: true, required: false },
      message: { visible: true, required: false },
    },
    buttonChannels: { save: true, email: true, whatsapp: true },
    messages: {
      successTitle: "Inquiry saved in the admin.",
      successText: "Use Email or WhatsApp if you also want to send the draft through your preferred app.",
      errorFallback: "Inquiry could not be saved.",
      invalidPayload: "The inquiry form was not submitted correctly. Please refresh the page and try again.",
      requiredFields: "Please enter your name and a valid email address.",
    },
    inquiryLineLabels: { name: "Name", email: "Email", phone: "Phone / WhatsApp", country: "Country", product: "Product", quantity: "Quantity", message: "Project details" },
  },
  newsPage: {
    metaTitle: "News | BOB-LIFT",
    metaDescription: "BOB-LIFT product news, company news and lifting solution insights.",
    canonicalPath: "/news",
    heroLabel: "News Center",
    heroTitle: "Updates that help buyers choose lifting equipment faster.",
    heroDescription: "Read BOB-LIFT product updates, factory stories and marine solution articles for practical purchasing and equipment-selection reference.",
    heroImage: "/images/factory-workshop.jpg",
    readFeaturedLabel: "Read Featured",
    requestQuoteLabel: "Request Quote",
    featuredLabel: "Featured Article",
    readArticleLabel: "Read Article",
    readMoreLabel: "Read More",
  },
  newsDetail: { backLabel: "Back to News", relatedLabel: "Related", quoteCtaLabel: "Ask for solution", relatedArticleCount: 3 },
  casesPage: {
    metaTitle: "Case Center | BOB-LIFT",
    metaDescription: "BOB-LIFT customer cases for marine operations, material handling and aerial work platform applications.",
    canonicalPath: "/cases",
    heroLabel: "Case Center",
    heroTitle: "Project stories with real working conditions.",
    heroDescription: "Browse customer cases across marine operations, material handling and aerial work applications, then discuss a similar lifting solution with BOB-LIFT.",
    heroImage: "/images/marine-scene.jpg",
    primaryCtaLabel: "Plan My Project",
    secondaryCtaLabel: "View Cases",
    listLabel: "Field Evidence",
    listTitle: "From lifting challenge to equipment match.",
    cardTypeLabel: "Customer Case",
    viewCaseLabel: "View Case",
  },
  caseDetail: { backLabel: "Back to Case Center", factsTitle: "Case Facts", discussSimilarLabel: "Discuss Similar Project" },
  aboutPage: {
    metaTitle: "About BOB-LIFT | BOB-LIFT",
    metaDescription: "Learn about BOB-LIFT manufacturing, export capability and engineering experience.",
    canonicalPath: "/about",
    primaryCtaLabel: "Contact Team",
    secondaryCtaLabel: "View Products",
    strengthLabel: "Company Strength",
    strengthTitle: "Manufacturing, export and service in one team.",
    timelineLabel: "Development Timeline",
    galleryImageCount: 4,
  },
  contentLabels: {
    articleCategories: { companyNews: "Company News", productNews: "Product News", industryInsight: "Industry Insight" },
    caseFacts: { region: "Region", industry: "Industry", priority: "Priority", defaultPriority: "Equipment Match", defaultCaseType: "Customer Case" },
  },
};

export function formatSeoTemplate(template: string, values: Record<string, string | null | undefined>) {
  return template.replace(/\{([a-zA-Z0-9_]+)\}/g, (placeholder, key: string) => {
    const value = values[key];
    return value && value.trim() ? value : placeholder;
  });
}
