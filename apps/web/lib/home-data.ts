export type NavItem = { label: string; href: string; hasMenu?: boolean; sortOrder?: number };
export type ProductNavLink = { label: string; href: string; iconName?: string };
export type ProductNavGroup = { title: string; href: string; description: string; iconName?: string; links: ProductNavLink[] };

export const navItems: NavItem[] = [
  { label: "Home", href: "/" },
  { label: "Products", href: "/products", hasMenu: true },
  { label: "Application", href: "/#application", hasMenu: true },
  { label: "Cases", href: "/cases" },
  { label: "About BOB-LIFT", href: "/about" },
  { label: "News", href: "/news" },
  { label: "Contact Us", href: "/contact" },
];


export const productNavGroups: ProductNavGroup[] = [
  {
    title: "Truck Mounted Crane",
    href: "/products#telescopic-boom-truck-mounted-cranes",
    description: "Road transport lifting families",
    links: [
      { label: "Telescopic Boom Truck Mounted Cranes", href: "/products#telescopic-boom-truck-mounted-cranes" },
      { label: "Knuckle Boom Truck Mounted Cranes", href: "/products#knuckle-boom-truck-mounted-cranes" },
      { label: "Aerial Work Platform", href: "/products#other-cranes" },
    ],
  },
  {
    title: "Marine Crane",
    href: "/products#knuckle-boom-marine-cranes",
    description: "Deck and port lifting choices",
    links: [
      { label: "Knuckle Boom Marine Cranes", href: "/products#knuckle-boom-marine-cranes" },
      { label: "Telescopic Boom Marine Cranes", href: "/products#telescopic-boom-marine-cranes" },
    ],
  },
  {
    title: "Compact & Attachments",
    href: "/products#pickup-cranes",
    description: "Smaller equipment and add-ons",
    links: [
      { label: "Pickup Cranes", href: "/products#pickup-cranes" },
      { label: "Forklift Fly Jib Cranes", href: "/products#forklift-fly-jib-cranes" },
      { label: "Mini Dumpers", href: "/products#mini-dumpers" },
      { label: "Other Cranes", href: "/products#other-cranes" },
    ],
  },
];
