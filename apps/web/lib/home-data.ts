import { Award, Building2, Factory, Globe2, Network, PackageCheck, Ship, Truck, Wrench } from "lucide-react";

export const navItems = [
  { label: "Home", href: "/" },
  { label: "Products", href: "/products", hasMenu: true },
  { label: "Application", href: "/#application", hasMenu: true },
  { label: "Cases", href: "/cases" },
  { label: "About BOB-LIFT", href: "/about" },
  { label: "News", href: "/news" },
  { label: "Contact Us", href: "/contact" },
];

export const productFamilies = [
  { title: "Truck Mounted Cranes", text: "Powerful, reliable and versatile solutions for on-road performance.", image: "/images/truck-mounted-crane.png", icon: Truck },
  { title: "Marine Cranes", text: "Engineered for marine environments with superior safety and durability.", image: "/images/marine-scene.jpg", icon: Ship },
  { title: "Telescopic Boom Cranes", text: "High reach, maximum capacity for heavy lifting operations.", image: "/images/telescopic-crane.png", icon: Building2 },
  { title: "Knuckle Boom Cranes", text: "Flexible and compact solutions for tight access and urban areas.", image: "/images/knuckle-crane.png", icon: Wrench },
];

export const featuredProducts = [
  { name: "SQ10SA5 Telescopic Boom Truck Mounted Crane", image: "/images/oldsite/products/10-ton-telescopic-boom-truck-mounted-crane.png", cap: "10 Ton", reach: "Telescopic Truck", href: "/products/10-ton-telescopic-boom-truck-mounted-crane" },
  { name: "SQ10ZA4TJ Knuckle Boom Marine Crane", image: "/images/oldsite/products/10-ton-knuckle-boom-marine-crane.png", cap: "10 Ton", reach: "Marine Crane", href: "/products/10-ton-knuckle-boom-marine-crane" },
  { name: "SQ08ZA3 Pickup Crane With Outriggers", image: "/images/oldsite/products/800-kg-pickup-crane-with-outriggers.png", cap: "800 kg", reach: "Pickup Crane", href: "/products/800-kg-pickup-crane-with-outriggers" },
  { name: "FY30 Mini Dumper", image: "/images/oldsite/products/3-ton-mini-dumper.png", cap: "3 Ton", reach: "Mini Dumper", href: "/products/3-ton-mini-dumper" },
];

export const industries = [
  { title: "Construction", image: "/images/construction-scene.jpg", icon: Factory },
  { title: "Logistics & Transport", image: "/images/aerial-platform.jpg", icon: Truck },
  { title: "Port Operations", image: "/images/marine-scene.jpg", icon: Ship },
  { title: "Marine Operations", image: "/images/global-network-blue.jpg", icon: Network },
  { title: "Municipal Engineering", image: "/images/telescopic-banner.jpg", icon: Building2 },
];

export const timeline = ["1989 Company Founded", "2005 Global Export Expansion", "2015 Advanced R&D Center Established", "2022 Smart Manufacturing Upgraded"];

export const stats = [
  { value: "35+", label: "Years Experience", icon: Award },
  { value: "120+", label: "Export Countries", icon: Globe2 },
  { value: "9999+", label: "Cranes Exported", icon: PackageCheck },
];

export const stories = [
  { title: "BOB-LIFT provides reliable support for water operations", type: "Customer Case · Marine Operations", image: "/images/oldsite/cases/bob-lift-provides-reliable-support-for-water-operations.jpg" },
  { title: "Strong lifting combined with efficient grippers", type: "Customer Case · Complex Working Conditions", image: "/images/oldsite/cases/strong-lifting-combined-with-efficient-grippers.jpg" },
  { title: "Leg and chassis technology of the BOB-LIFT aerial work platform", type: "Customer Case · Aerial Work Platform", image: "/images/oldsite/cases/leg-and-chassis-technology-of-the-bob-lift-aerial-work-platform.jpg" },
];

export const news = [
  { date: "2026-05-20", title: "BOB-LIFT SQ10ZA5 Limited Time Discount Hot Selling!", image: "/images/oldsite/news/bob-lift-sq10za5-limited-time-discount-hot-selling.png" },
  { date: "2026-05-18", title: "BOB-LIFT Three Models of Truck-mounted Cranes Are Currently on Promotion.", image: "/images/oldsite/news/bob-lift-three-models-of-truck-mounted-cranes-are-currently-on-promotion.jpg" },
  { date: "2026-05-11", title: "Enter The BOB-LIFT Factory: With Professional Manufacturing, We Create More Reliable Lifting Equipment.", image: "/images/oldsite/news/enter-the-bob-lift-factory-with-professional-manufacturing.jpg" },
  { date: "2026-04-27", title: "BOB-LIFT Offers Efficient And Reliable Solutions for Ship Lifting Operations.", image: "/images/oldsite/news/bob-lift-offers-efficient-and-reliable-solutions-for-ship-lifting-operations.jpg" },
];

export const footerGroups = [
  { title: "Products", links: [
    { label: "Telescopic Boom Truck Mounted Cranes", href: "/products#telescopic-boom-truck-mounted-cranes" },
    { label: "Knuckle Boom Truck Mounted Cranes", href: "/products#knuckle-boom-truck-mounted-cranes" },
    { label: "Marine Cranes", href: "/products#knuckle-boom-marine-cranes" },
    { label: "Pickup Cranes", href: "/products#pickup-cranes" },
    { label: "Mini Dumpers", href: "/products#mini-dumpers" },
  ] },
  { title: "Application", links: [
    { label: "Construction", href: "/#application" },
    { label: "Logistics & Transport", href: "/#application" },
    { label: "Port Operations", href: "/#application" },
    { label: "Marine Operations", href: "/#application" },
    { label: "Municipal Engineering", href: "/#application" },
  ] },
  { title: "Company", links: [
    { label: "About BOB-LIFT", href: "/about" },
    { label: "Customer Cases", href: "/cases" },
    { label: "Company News", href: "/news" },
    { label: "Industry News", href: "/news" },
    { label: "Contact Us", href: "/contact" },
  ] },
  { title: "Service", links: [
    { label: "BOB-LIFT Service", href: "/quote" },
    { label: "Repair and Maintenance", href: "/contact" },
    { label: "Technical Support", href: "/contact" },
    { label: "Send Inquiry", href: "/quote" },
    { label: "Terms & Conditions", href: "/contact" },
  ] },
];
