import { ProductCard } from "@/components/products/ProductCard";
import { ProductListItem } from "@/components/products/ProductListItem";
import { defaultProductUiContent, type ProductListingUiContent } from "@/lib/product-ui";
import type { Product, ProductViewMode } from "@/types/product-listing";

export function ProductGrid({
  products,
  viewMode,
  favoriteIds,
  onToggleFavorite,
  onQuickView,
  ui = defaultProductUiContent.listing,
}: {
  products: Product[];
  viewMode: ProductViewMode;
  favoriteIds: Set<string>;
  onToggleFavorite: (productId: string) => void;
  onQuickView: (product: Product) => void;
  ui?: ProductListingUiContent;
}) {
  if (viewMode === "list") {
    return (
      <div className="space-y-5">
        {products.map((product, index) => (
          <ProductListItem product={product} eagerImage={index === 0} isFavorite={favoriteIds.has(product.id)} onToggleFavorite={onToggleFavorite} onQuickView={onQuickView} ui={ui} key={product.id} />
        ))}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 gap-5 md:grid-cols-2 2xl:grid-cols-3 xl:gap-6">
      {products.map((product, index) => (
        <ProductCard product={product} eagerImage={index === 0} isFavorite={favoriteIds.has(product.id)} onToggleFavorite={onToggleFavorite} onQuickView={onQuickView} ui={ui} key={product.id} />
      ))}
    </div>
  );
}
