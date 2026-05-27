export type CatalogProduct = {
  name: string;
  model: string;
  image: string;
  capacity: string;
  type: string;
  intro: string;
  href: string;
};

export type ProductCategory = {
  name: string;
  image: string;
  href: string;
  products: CatalogProduct[];
};

const oldSite = "https://www.bobliftcrane.com";

function product(
  name: string,
  model: string,
  capacity: string,
  type: string,
  image: string,
  href: string,
  intro: string,
): CatalogProduct {
  return { name, model, capacity, type, image, href: `${oldSite}${href}`, intro };
}

export const productCatalog: ProductCategory[] = [
  {
    name: "Telescopic Boom Truck Mounted Cranes",
    image: "/images/oldsite/products/5-ton-telescopic-boom-truck-mounted-crane.png",
    href: `${oldSite}/telescopic-boom-truck-mounted-crane`,
    products: [
      product("2 Ton Telescopic Boom Truck Mounted Crane", "SQ2SA3", "2 Ton", "Telescopic Truck", "/images/oldsite/products/2-ton-telescopic-boom-truck-mounted-crane.png", "/2-ton-telescopic-boom-truck-mounted-crane.html", "Straight-boom truck mounted crane for construction, logistics and field lifting."),
      product("3.2 Ton Telescopic Boom Truck Mounted Crane", "SQ3.2SA3", "3.2 Ton", "Telescopic Truck", "/images/oldsite/products/3-2-ton-telescopic-boom-truck-mounted-crane.png", "/3-2-ton-telescopic-boom-truck-mounted-crane.html", "Compact telescopic lifting solution for trucks and mobile service fleets."),
      product("4 Ton Telescopic Boom Truck Mounted Crane", "SQ4SA4", "4 Ton", "Telescopic Truck", "/images/oldsite/products/4-ton-telescopic-boom-truck-mounted-crane.png", "/4-ton-telescopic-boom-truck-mounted-crane.html", "Hydraulic telescopic boom crane designed for reliable truck-mounted lifting."),
      product("5 Ton Telescopic Boom Truck Mounted Crane", "SQ5SA4", "5 Ton", "Telescopic Truck", "/images/oldsite/products/5-ton-telescopic-boom-truck-mounted-crane.png", "/5-ton-telescopic-boom-truck-mounted-crane.html", "Export-ready truck crane model for medium-duty loading operations."),
      product("6.3 Ton Telescopic Boom Truck Mounted Crane", "SQ6.3SA4", "6.3 Ton", "Telescopic Truck", "/images/oldsite/products/6-3-ton-telescopic-boom-truck-mounted-crane.png", "/6-3-ton-telescopic-boom-truck-mounted-crane.html", "Balanced lifting performance for construction material handling and transport."),
      product("8 Ton Telescopic Boom Truck Mounted Crane", "SQ8SA5", "8 Ton", "Telescopic Truck", "/images/oldsite/products/8-ton-telescopic-boom-truck-mounted-crane.png", "/8-ton-telescopic-boom-truck-mounted-crane.html", "Heavy telescopic truck crane for demanding jobsite lifting."),
      product("10 Ton Telescopic Boom Truck Mounted Crane", "SQ10SA5", "10 Ton", "Telescopic Truck", "/images/oldsite/products/10-ton-telescopic-boom-truck-mounted-crane.png", "/10-ton-telescopic-boom-truck-mounted-crane.html", "High-capacity straight boom model for professional lifting fleets."),
      product("12 Ton Telescopic Boom Truck Mounted Crane", "SQ12SA5", "12 Ton", "Telescopic Truck", "/images/oldsite/products/12-ton-telescopic-boom-truck-mounted-crane.png", "/12-ton-telescopic-boom-truck-mounted-crane.html", "Large telescopic truck mounted crane for heavy project work."),
    ],
  },
  {
    name: "Knuckle Boom Truck Mounted Cranes",
    image: "/images/oldsite/products/4-ton-knuckle-boom-truck-mounted-crane.png",
    href: `${oldSite}/knuckle-boom-truck-mounted-crane`,
    products: [
      product("1 Ton Knuckle Boom Truck Mounted Crane", "SQ1ZA3", "1 Ton", "Knuckle Truck", "/images/oldsite/products/1-ton-knuckle-boom-truck-mounted-crane.png", "/1-ton-knuckle-boom-truck-mounted-crane.html", "Folding boom truck crane for compact urban lifting and loading."),
      product("3.2 Ton Knuckle Boom Truck Mounted Crane", "SQ3.2ZA3", "3.2 Ton", "Knuckle Truck", "/images/oldsite/products/3-2-ton-knuckle-boom-truck-mounted-crane.png", "/3-2-ton-knuckle-boom-truck-mounted-crane.html", "Flexible knuckle boom crane for tight access and precise handling."),
      product("4 Ton Knuckle Boom Truck Mounted Crane", "SQ4ZA4", "4 Ton", "Knuckle Truck", "/images/oldsite/products/4-ton-knuckle-boom-truck-mounted-crane.png", "/4-ton-knuckle-boom-truck-mounted-crane.html", "Truck-mounted folding boom crane with efficient hydraulic control."),
      product("8 Ton Knuckle Boom Truck Mounted Crane", "SQ8ZA4", "8 Ton", "Knuckle Truck", "/images/oldsite/products/8-ton-knuckle-boom-truck-mounted-crane.png", "/8-ton-knuckle-boom-truck-mounted-crane.html", "Heavy-duty folding boom crane for project logistics and construction sites."),
    ],
  },
  {
    name: "Knuckle Boom Marine Cranes",
    image: "/images/oldsite/products/5-ton-knuckle-boom-marine-crane.png",
    href: `${oldSite}/knuckle-boom-marine-crane`,
    products: [
      product("1 Ton Knuckle Boom Marine Crane", "SQ1ZA3TJ", "1 Ton", "Knuckle Marine", "/images/oldsite/products/1-ton-knuckle-boom-marine-crane.png", "/1-ton-knuckle-boom-marine-crane.html", "Compact folding marine crane for vessel and dock operations."),
      product("2 Ton Knuckle Boom Marine Crane", "SQ2ZA2TJ", "2 Ton", "Knuckle Marine", "/images/oldsite/products/2-ton-knuckle-boom-marine-crane.png", "/2-ton-knuckle-boom-marine-crane.html", "Marine-grade lifting equipment for shipboard material handling."),
      product("3.2 Ton Knuckle Boom Marine Crane", "SQ3.2ZA3TJ", "3.2 Ton", "Knuckle Marine", "/images/oldsite/products/3-2-ton-knuckle-boom-marine-crane.png", "/3-2-ton-knuckle-boom-marine-crane.html", "Hydraulic knuckle boom marine crane for flexible deck lifting."),
      product("4 Ton Knuckle Boom Marine Crane", "SQ4ZA3TJ", "4 Ton", "Knuckle Marine", "/images/oldsite/products/4-ton-knuckle-boom-marine-crane.png", "/4-ton-knuckle-boom-marine-crane.html", "Durable folding marine crane for ports, vessels and offshore service."),
      product("5 Ton Knuckle Boom Marine Crane", "SQ5ZA3TJ", "5 Ton", "Knuckle Marine", "/images/oldsite/products/5-ton-knuckle-boom-marine-crane.png", "/5-ton-knuckle-boom-marine-crane.html", "Reliable marine lifting solution for vessel supply and dockside work."),
      product("6.3 Ton Knuckle Boom Marine Crane", "SQ6.3ZA4TJ", "6.3 Ton", "Knuckle Marine", "/images/oldsite/products/6-3-ton-knuckle-boom-marine-crane.png", "/6-3-ton-knuckle-boom-marine-crane.html", "Export-ready folding boom marine crane for heavier water operations."),
      product("8 Ton Knuckle Boom Marine Crane", "SQ8ZA4TJ", "8 Ton", "Knuckle Marine", "/images/oldsite/products/8-ton-knuckle-boom-marine-crane.png", "/8-ton-knuckle-boom-marine-crane.html", "Heavy marine knuckle boom crane for ship lifting support."),
      product("10 Ton Knuckle Boom Marine Crane", "SQ10ZA4TJ", "10 Ton", "Knuckle Marine", "/images/oldsite/products/10-ton-knuckle-boom-marine-crane.png", "/10-ton-knuckle-boom-marine-crane.html", "High-capacity marine crane for demanding deck and port operations."),
    ],
  },
  {
    name: "Telescopic Boom Marine Cranes",
    image: "/images/oldsite/products/5-ton-telescopic-boom-marine-crane.png",
    href: `${oldSite}/telescopic-boom-marine-crane`,
    products: [
      product("2 Ton Telescopic Boom Marine Crane", "SQ2SA3T", "2 Ton", "Telescopic Marine", "/images/oldsite/products/2-ton-telescopic-boom-marine-crane.png", "/2-ton-telescopic-boom-marine-crane.html", "Straight boom marine crane for dock, vessel and deck lifting."),
      product("3.2 Ton Telescopic Boom Marine Crane", "SQ3.2SA4T", "3.2 Ton", "Telescopic Marine", "/images/oldsite/products/3-2-ton-telescopic-boom-marine-crane.png", "/3-2-ton-telescopic-boom-marine-crane.html", "Telescopic marine lifting model for compact shipboard operation."),
      product("5 Ton Telescopic Boom Marine Crane", "SQ5SA4T", "5 Ton", "Telescopic Marine", "/images/oldsite/products/5-ton-telescopic-boom-marine-crane.png", "/5-ton-telescopic-boom-marine-crane.html", "Marine-grade straight boom crane for efficient water operations."),
      product("6.3 Ton Telescopic Boom Marine Crane", "SQ6.3SA4T", "6.3 Ton", "Telescopic Marine", "/images/oldsite/products/6-3-ton-telescopic-boom-marine-crane.png", "/6-3-ton-telescopic-boom-marine-crane.html", "Hydraulic telescopic marine crane for heavier deck loads."),
      product("8 Ton Telescopic Boom Marine Crane", "SQ8SA4T", "8 Ton", "Telescopic Marine", "/images/oldsite/products/8-ton-telescopic-boom-marine-crane.png", "/8-ton-telescopic-boom-marine-crane.html", "Reliable telescopic ship crane for port and vessel handling."),
      product("10 Ton Telescopic Boom Marine Crane", "SQ10SA4T", "10 Ton", "Telescopic Marine", "/images/oldsite/products/10-ton-telescopic-boom-marine-crane.png", "/10-ton-telescopic-boom-marine-crane.html", "High-capacity marine crane for yacht, dock and offshore lifting."),
      product("12 Ton Telescopic Boom Marine Crane", "SQ12SA4T", "12 Ton", "Telescopic Marine", "/images/oldsite/products/12-ton-telescopic-boom-marine-crane.png", "/12-ton-telescopic-boom-marine-crane.html", "Heavy telescopic marine model for professional ship lifting tasks."),
      product("16 Ton Telescopic Boom Marine Crane", "SQ16SA4T", "16 Ton", "Telescopic Marine", "/images/oldsite/products/16-ton-telescopic-boom-marine-crane.png", "/16-ton-telescopic-boom-marine-crane.html", "Large-capacity marine crane for high-demand port and vessel projects."),
    ],
  },
  {
    name: "Pickup Cranes",
    image: "/images/oldsite/products/800-kg-pickup-crane-with-outriggers.png",
    href: `${oldSite}/pickup-crane`,
    products: [
      product("800 kg Pickup Crane With Outriggers", "SQ08ZA3", "800 kg", "Pickup Crane", "/images/oldsite/products/800-kg-pickup-crane-with-outriggers.png", "/800-kg-pickup-crane-with-outriggers.html", "Light pickup-mounted crane with outriggers for mobile loading work."),
      product("800 kg Pickup Crane Without Outriggers", "SQ08ZA3T", "800 kg", "Pickup Crane", "/images/oldsite/products/800-kg-pickup-crane-without-outriggers.png", "/800-kg-pickup-crane-without-outriggers.html", "Compact pickup crane for fast service, loading and maintenance tasks."),
    ],
  },
  {
    name: "Forklift Fly Jib Cranes",
    image: "/images/oldsite/products/4-ton-forklift-fly-jib-crane.png",
    href: `${oldSite}/forklift-fly-jib-crane`,
    products: [
      product("2.3 Ton Forklift Fly Jib Crane", "CC85", "2.3 Ton", "Forklift Attachment", "/images/oldsite/products/2-3-ton-forklift-fly-jib-crane.png", "/2-3-ton-forklift-fly-jib-crane.html", "Forklift-mounted jib attachment for warehouse and yard lifting."),
      product("3 Ton Forklift Fly Jib Crane", "CC105", "3 Ton", "Forklift Attachment", "/images/oldsite/products/3-ton-forklift-fly-jib-crane.png", "/3-ton-forklift-fly-jib-crane.html", "Practical fly jib crane for converting forklifts into lifting tools."),
      product("4 Ton Forklift Fly Jib Crane", "CC125", "4 Ton", "Forklift Attachment", "/images/oldsite/products/4-ton-forklift-fly-jib-crane.png", "/4-ton-forklift-fly-jib-crane.html", "Heavy forklift fly jib model for industrial material handling."),
      product("7 Ton Forklift Fly Jib Crane", "CC165", "7 Ton", "Forklift Attachment", "/images/oldsite/products/7-ton-forklift-fly-jib-crane.png", "/7-ton-forklift-fly-jib-crane.html", "High-capacity forklift jib crane for heavier yard operations."),
    ],
  },
  {
    name: "Mini Dumpers",
    image: "/images/oldsite/products/2-ton-mini-dumper.png",
    href: `${oldSite}/mini-dumper`,
    products: [
      product("1 Ton Mini Dumper", "FC10", "1 Ton", "Mini Dumper", "/images/oldsite/products/1-ton-mini-dumper.png", "/1-ton-mini-dumper.html", "Compact dumper for construction, landscaping and tight jobsites."),
      product("1.5 Ton Mini Dumper", "FY15", "1.5 Ton", "Mini Dumper", "/images/oldsite/products/1-5-ton-mini-dumper.png", "/1-5-ton-mini-dumper.html", "Efficient mini dumper for material transport in confined work areas."),
      product("2 Ton Mini Dumper", "FY20", "2 Ton", "Mini Dumper", "/images/oldsite/products/2-ton-mini-dumper.png", "/2-ton-mini-dumper.html", "Heavy mini dumper for construction and agricultural transport."),
      product("3 Ton Mini Dumper", "FY30", "3 Ton", "Mini Dumper", "/images/oldsite/products/3-ton-mini-dumper.png", "/3-ton-mini-dumper.html", "Robust compact dumper for demanding site material movement."),
      product("4 Ton Mini Dumper", "FY40", "4 Ton", "Mini Dumper", "/images/oldsite/products/4-ton-mini-dumper.png", "/4-ton-mini-dumper.html", "High-capacity mini dumper for larger construction workflows."),
      product("5 Ton Mini Dumper", "FY50", "5 Ton", "Mini Dumper", "/images/oldsite/products/5-ton-mini-dumper.png", "/5-ton-mini-dumper.html", "Large mini dumper option for heavy-duty site transport."),
    ],
  },
  {
    name: "Other Cranes",
    image: "/images/oldsite/products/zm75-timber-crane.png",
    href: `${oldSite}/other-crane.html`,
    products: [
      product("Knuckle Spider Crane", "Spider Knuckle", "Spider Type", "Other Crane", "/images/oldsite/products/knuckle-spider-crane.png", "/knuckle-spider-crane.html", "Compact spider crane for confined lifting scenarios."),
      product("ZM75: Arm Length 7667MM - Timber Crane", "ZM75", "7667 mm Arm", "Timber Crane", "/images/oldsite/products/zm75-timber-crane.png", "/zm75-timber-crane.html", "Timber crane model for forestry and material handling."),
      product("ZM65: Arm Length 7600MM - Timber Crane", "ZM65", "7600 mm Arm", "Timber Crane", "/images/oldsite/products/zm65-timber-crane.png", "/zm65-timber-crane.html", "Timber crane with long-arm configuration for forestry tasks."),
      product("Telescopic Spider Crane", "Spider Telescopic", "Spider Type", "Other Crane", "/images/oldsite/products/telescopic-spider-crane.png", "/telescopic-spider-crane.html", "Telescopic spider crane for compact lifting access."),
    ],
  },
];

export type CatalogProductEntry = {
  product: CatalogProduct;
  category: ProductCategory;
};

export function productLegacyPath(product: CatalogProduct) {
  return new URL(product.href).pathname;
}

export function productSlug(product: CatalogProduct) {
  const segment = productLegacyPath(product).split("/").pop() ?? product.model.toLowerCase();
  return segment.replace(/\.html$/i, "").toLowerCase();
}

export function productDetailPath(product: CatalogProduct) {
  return `/products/${productSlug(product)}`;
}

export function categorySlug(category: ProductCategory) {
  return category.name.toLowerCase().replace(/&/g, "and").replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");
}

export function allProductEntries(): CatalogProductEntry[] {
  return productCatalog.flatMap((category) => category.products.map((product) => ({ category, product })));
}

export function getProductEntryBySlug(slug: string) {
  return allProductEntries().find(({ product }) => productSlug(product) === slug);
}

export function getProductEntryByLegacySegment(segment: string) {
  return allProductEntries().find(({ product }) => productLegacyPath(product).split("/").pop() === segment);
}
