export type CatalogProduct = {
  name: string;
  model: string;
  image: string;
  capacity: string;
  type: string;
  intro: string;
  href: string;
  detailPath?: string;
};

export type ProductCategory = {
  name: string;
  image: string;
  href: string;
  products: CatalogProduct[];
};
