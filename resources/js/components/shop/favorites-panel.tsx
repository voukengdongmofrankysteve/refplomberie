import { router } from '@inertiajs/react';
import { useCart } from '@/contexts/cart-context';
import { useFavorites } from '@/contexts/favorites-context';
import { formatPrice, productUrl } from '@/lib/shop';

export default function FavoritesPanel() {
    const { favorites, toggleFavorite, isOpen, setIsOpen } = useFavorites();
    const { addItem } = useCart();

    if (!isOpen) {
        return null;
    }

    const openProduct = (slug: string) => {
        setIsOpen(false);
        router.visit(productUrl(slug));
    };

    return (
        <>
            <div
                className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm"
                onClick={() => setIsOpen(false)}
                aria-hidden="true"
            />
            <aside
                className="cart-panel fixed top-0 right-0 bottom-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl"
                role="dialog"
                aria-modal="true"
                aria-label="Mes favoris"
            >
                {/* En-tête */}
                <div className="flex items-center justify-between border-b border-[#E9ECEF] bg-[#E8F5E9] px-5 py-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-[#25D366]">
                            <svg
                                className="h-5 w-5 text-[#1A1A2E]"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h2 className="font-display text-lg leading-none font-bold text-[#1A1A2E]">
                                Mes favoris
                            </h2>
                            <p className="text-xs text-[#4A4A6A]">
                                {favorites.length} produit
                                {favorites.length !== 1 ? 's' : ''}
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={() => setIsOpen(false)}
                        className="flex h-8 w-8 items-center justify-center rounded-lg bg-white transition-colors hover:bg-gray-100"
                        aria-label="Fermer les favoris"
                    >
                        <svg
                            className="h-4 w-4 text-[#4A4A6A]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2.5}
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                {/* Liste */}
                <div className="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    {favorites.length === 0 ? (
                        <div className="flex h-full flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#E8F5E9]">
                                <svg
                                    className="h-8 w-8 text-[#25D366]"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    strokeWidth={1.5}
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p className="font-display text-lg font-bold text-[#1A1A2E]">
                                    Aucun favori
                                </p>
                                <p className="mt-1 text-sm text-[#4A4A6A]">
                                    Cliquez sur le cœur d&apos;un produit pour
                                    l&apos;ajouter ici.
                                </p>
                            </div>
                            <button
                                onClick={() => setIsOpen(false)}
                                className="mt-2 rounded-xl bg-[#25D366] px-6 py-3 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851]"
                            >
                                Parcourir les produits
                            </button>
                        </div>
                    ) : (
                        favorites.map((product) => (
                            <div
                                key={product.id}
                                className="flex gap-3 rounded-xl border border-[#E9ECEF] bg-[#F8F9FA] p-3"
                            >
                                <img
                                    src={product.img}
                                    alt={product.name}
                                    className="h-16 w-16 flex-shrink-0 cursor-pointer rounded-lg object-cover"
                                    onClick={() => openProduct(product.slug)}
                                />
                                <div className="min-w-0 flex-1">
                                    <button
                                        className="line-clamp-1 text-left text-sm font-semibold text-[#1A1A2E] transition-colors hover:text-[#25D366]"
                                        onClick={() =>
                                            openProduct(product.slug)
                                        }
                                    >
                                        {product.name}
                                    </button>
                                    <p className="mt-0.5 text-xs text-[#4A4A6A]">
                                        {formatPrice(product.price)}
                                    </p>
                                    <div className="mt-2 flex items-center gap-2">
                                        <button
                                            onClick={() => addItem(product, 1)}
                                            className="flex-1 rounded-lg bg-[#25D366] px-3 py-1.5 text-xs font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851]"
                                        >
                                            Ajouter au panier
                                        </button>
                                        <button
                                            onClick={() =>
                                                toggleFavorite(product)
                                            }
                                            className="flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-500 transition-colors hover:bg-red-100"
                                            aria-label="Retirer des favoris"
                                        >
                                            <svg
                                                className="h-3.5 w-3.5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                strokeWidth={2.5}
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </aside>
        </>
    );
}
