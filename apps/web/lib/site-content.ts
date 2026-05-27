export type Article = {
  slug: string;
  legacyPath: string;
  title: string;
  date: string;
  category: string;
  image: string;
  excerpt: string;
  sections: { heading: string; body: string }[];
};

export type CaseStudy = {
  slug: string;
  title: string;
  type: string;
  image: string;
  summary: string;
  facts: { label: string; value: string }[];
  sections: { heading: string; body: string }[];
};

export const articleCategories = ["Company News", "Product News", "Industry Insight", "Marine Solution"];

export const articles: Article[] = [
  {
    slug: "bob-lift-sq10za5-limited-time-discount-hot-selling",
    legacyPath: "/BOB-LIFT-SQ10ZA5-Limited-Time-Discount-Hot-Selling-id03356155.html",
    title: "BOB-LIFT SQ10ZA5 Limited Time Discount Hot Selling!",
    date: "2026-05-20",
    category: "Product News",
    image: "/images/oldsite/news/bob-lift-sq10za5-limited-time-discount-hot-selling.png",
    excerpt: "A focused product update for customers comparing 10 ton knuckle boom truck-mounted crane options.",
    sections: [
      { heading: "Why this model matters", body: "SQ10ZA5 is positioned for customers who need a compact folding boom layout, stable hydraulic control and stronger loading efficiency on truck-mounted working conditions." },
      { heading: "Best-fit projects", body: "The model is suitable for construction material handling, logistics loading, municipal maintenance and jobsites where side loading space is limited." },
      { heading: "How to request configuration", body: "Share the target load, truck chassis, working radius and destination country. The engineering team can then match crane capacity, installation layout and export documentation." },
    ],
  },
  {
    slug: "bob-lift-three-models-of-truck-mounted-cranes-are-currently-on-promotion",
    legacyPath: "/BOB-LIFT-Three-Models-of-Truck-mounted-Cranes-Are-Currently-on-Promotion-id06681255.html",
    title: "BOB-LIFT Three Models of Truck-mounted Cranes Are Currently on Promotion.",
    date: "2026-05-18",
    category: "Company News",
    image: "/images/oldsite/news/bob-lift-three-models-of-truck-mounted-cranes-are-currently-on-promotion.jpg",
    excerpt: "Three truck-mounted crane choices are highlighted for customers planning near-term procurement.",
    sections: [
      { heading: "Promotion focus", body: "The update helps buyers compare practical crane models by lifting capacity, boom type and installation scenario before requesting a formal quotation." },
      { heading: "Selection logic", body: "Telescopic boom cranes provide straightforward reach and lifting height, while knuckle boom cranes improve flexibility in narrow loading environments." },
      { heading: "Procurement support", body: "BOB-LIFT can support model selection, chassis matching, packaging advice and export documents after the customer confirms working conditions." },
    ],
  },
  {
    slug: "enter-the-bob-lift-factory-with-professional-manufacturing",
    legacyPath: "/Enter-The-BOB-LIFT-Factory-With-Professional-Manufacturing-We-Create-More-Reliable-Lifting-Equipment-id09841355.html",
    title: "Enter The BOB-LIFT Factory: With Professional Manufacturing, We Create More Reliable Lifting Equipment.",
    date: "2026-05-11",
    category: "Industry Insight",
    image: "/images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg",
    excerpt: "A factory-oriented article page for showing manufacturing capability, quality workflow and export readiness.",
    sections: [
      { heading: "Manufacturing foundation", body: "Reliable lifting equipment depends on repeatable fabrication, stable hydraulic assembly and inspection steps that can be checked before shipment." },
      { heading: "Quality workflow", body: "From component preparation to final testing, the production workflow is organized around safer lifting performance and easier long-term maintenance." },
      { heading: "Export readiness", body: "The factory page supports international buyers who need clear product information, photos, technical details and communication before placing an order." },
    ],
  },
  {
    slug: "bob-lift-offers-efficient-and-reliable-solutions-for-ship-lifting-operations",
    legacyPath: "/BOB-LIFT-Offers-Efficient-And-Reliable-Solutions-for-Ship-Lifting-Operations-id02577455.html",
    title: "BOB-LIFT Offers Efficient And Reliable Solutions for Ship Lifting Operations.",
    date: "2026-04-27",
    category: "Marine Solution",
    image: "/images/oldsite/news/bob-lift-offers-efficient-and-reliable-solutions-for-ship-lifting-operations.jpg",
    excerpt: "Marine crane content focused on deck operation, ship lifting support and corrosion-conscious configuration.",
    sections: [
      { heading: "Marine working conditions", body: "Ship and dock operations require cranes that can handle limited deck space, changing load positions and stricter reliability expectations." },
      { heading: "Equipment direction", body: "Knuckle boom and telescopic boom marine cranes can be configured for different reach, lifting capacity and installation spaces." },
      { heading: "Before quotation", body: "Customers should provide vessel type, installation area, working radius, load requirement and destination port so the team can prepare a practical solution." },
    ],
  },
];

export const caseStudies: CaseStudy[] = [
  {
    slug: "bob-lift-provides-reliable-support-for-water-operations",
    title: "BOB-LIFT provides reliable support for water operations",
    type: "Customer Case · Marine Operations",
    image: "/images/oldsite/cases/bob-lift-provides-reliable-support-for-water-operations.jpg",
    summary: "A marine operation case centered on stable lifting support, deck-side work and equipment reliability near water.",
    facts: [
      { label: "Scenario", value: "Water Operations" },
      { label: "Product", value: "Marine Crane" },
      { label: "Priority", value: "Stable Lifting" },
    ],
    sections: [
      { heading: "Working challenge", body: "Water operation projects often face limited installation space and higher expectations for stable lifting during repeated loading tasks." },
      { heading: "BOB-LIFT response", body: "The solution focuses on matching boom structure, working radius and hydraulic control to the actual marine operation environment." },
    ],
  },
  {
    slug: "strong-lifting-combined-with-efficient-grippers",
    title: "Strong lifting combined with efficient grippers",
    type: "Customer Case · Complex Working Conditions",
    image: "/images/oldsite/cases/strong-lifting-combined-with-efficient-grippers.jpg",
    summary: "A material-handling case showing how lifting equipment and attachments improve loading efficiency.",
    facts: [
      { label: "Scenario", value: "Material Handling" },
      { label: "Product", value: "Crane + Gripper" },
      { label: "Priority", value: "Efficiency" },
    ],
    sections: [
      { heading: "Working challenge", body: "Customers handling irregular materials need lifting equipment that can keep strength, control and grip efficiency in balance." },
      { heading: "BOB-LIFT response", body: "The case highlights matched lifting capacity and attachment logic, reducing repeated manual handling during field work." },
    ],
  },
  {
    slug: "leg-and-chassis-technology-of-the-bob-lift-aerial-work-platform",
    title: "Leg and chassis technology of the BOB-LIFT aerial work platform",
    type: "Customer Case · Aerial Work Platform",
    image: "/images/oldsite/cases/leg-and-chassis-technology-of-the-bob-lift-aerial-work-platform.jpg",
    summary: "A technology case focused on support legs, chassis stability and safe operation around aerial working conditions.",
    facts: [
      { label: "Scenario", value: "Aerial Work" },
      { label: "Product", value: "Aerial Platform" },
      { label: "Priority", value: "Stability" },
    ],
    sections: [
      { heading: "Working challenge", body: "Aerial work equipment needs stable support and predictable chassis behavior before lifting workers to operating height." },
      { heading: "BOB-LIFT response", body: "The case presents the role of leg structure and chassis technology in improving safety and operation confidence." },
    ],
  },
];

export function getArticleBySlug(slug: string) {
  return articles.find((item) => item.slug === slug);
}

export function getArticleByLegacySegment(segment: string) {
  return articles.find((item) => item.legacyPath.split("/").pop() === segment);
}

export function getCaseBySlug(slug: string) {
  return caseStudies.find((item) => item.slug === slug);
}
