import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import StarRating from '@/components/shop/star-rating';
import { useCart } from '@/contexts/cart-context';
import { useComparison } from '@/contexts/comparison-context';
import { formatPrice, getDiscount, productUrl } from '@/lib/shop';

/**
 * Comparateur : les fiches sélectionnées, côte à côte.
 *
 * Une table plutôt que des cartes empilées : c'est la lecture en colonne,
 * ligne par critère, qui permet de repérer d'un regard lequel gagne sur le
 * prix et lequel gagne sur le stock.
 */
export default function ComparisonModal() {
    const { items, isOpen, setIsOpen, toggleCompare, clear } = useComparison();
    const { addItem, setIsOpen: openCart } = useCart();

    if (!isOpen || items.length === 0) {
        return null;
    }

    const openProduct = (slug: string) => {
        setIsOpen(false);
        router.visit(productUrl(slug));
    };

    const rows: {
        label: string;
        render: (item: (typeof items)[number]) => ReactNode;
    }[] = [
        {
            label: 'Prix',
            render: (item) => {
                const discount = getDiscount(item.price, item.oldPrice);

                return (
                    <div>
                        <span className="text-lg font-bold text-[#1A1A2E]">
                            {formatPrice(item.price)}
                        </span>
                        {item.oldPrice && (
                            <span className="ml-2 text-xs text-[#4A4A6A] line-through">
                                {formatPrice(item.oldPrice)}
                            </span>
                        )}
                        {discount && (
                            <span className="ml-2 rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">
                                −{discount}%
                            </span>
                        )}
                    </div>
                );
            },
        },
        {
            label: 'Catégorie',
            render: (item) => (
                <span className="capitalize">{item.category}</span>
            ),
        },
        {
            label: 'Note',
            render: (item) => (
                <div className="flex items-center gap-1.5">
                    <StarRating rating={item.rating} className="h-4 w-4" />
                    <span className="text-xs text-[#4A4A6A]">
                        {item.rating} ({item.reviews})
                    </span>
                </div>
            ),
        },
        {
            label: 'Disponibilité',
            render: (item) =>
                item.stock === 0 ? (
                    <span className="font-semibold text-red-500">Épuisé</span>
                ) : item.stock <= 8 ? (
                    <span className="font-semibold text-amber-600">
                        Plus que {item.stock} en stock
                    </span>
                ) : (
                    <span className="font-semibold text-[#1DA851]">
                        En stock
                    </span>
                ),
        },
        {
            label: 'À partir de',
            render: (item) => {
                const best = item.priceTiers.at(-1);

                return best ? (
                    <span>
                        {formatPrice(best.price)} / pce dès {best.minQty} pcs
                    </span>
                ) : (
                    <span className="text-[#4A4A6A]">
                        Pas de tarif dégressif
                    </span>
                );
            },
        },
        {
            label: 'Description',
            render: (item) => (
                <p className="line-clamp-4 text-xs text-[#4A4A6A]">
                    {item.desc}
                </p>
            ),
        },
    ];

    return (
        <>
            <div
                className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm"
                onClick={() => setIsOpen(false)}
                aria-hidden="true"
            />

            <div
                className="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-label="Comparateur de produits"
            >
                <div className="animate-modal flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div className="flex items-center justify-between border-b border-[#E9ECEF] px-6 py-4">
                        <h2 className="font-display text-xl font-bold text-[#1A1A2E]">
                            Comparer {items.length} produits
                        </h2>
                        <button
                            onClick={() => setIsOpen(false)}
                            className="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 transition-colors hover:bg-gray-200"
                            aria-label="Fermer"
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

                    <div className="overflow-auto">
                        <table className="w-full border-collapse text-sm">
                            <thead>
                                <tr>
                                    <th className="sticky left-0 z-10 w-32 bg-white p-3 text-left align-bottom text-xs font-semibold text-[#4A4A6A] uppercase">
                                        Produit
                                    </th>
                                    {items.map((item) => (
                                        <th
                                            key={item.id}
                                            className="min-w-[220px] border-l border-[#E9ECEF] p-3 text-left align-bottom"
                                        >
                                            <button
                                                onClick={() =>
                                                    toggleCompare(item)
                                                }
                                                className="mb-2 text-xs font-semibold text-[#4A4A6A] hover:text-red-500"
                                            >
                                                ✕ Retirer
                                            </button>
                                            <button
                                                onClick={() =>
                                                    openProduct(item.slug)
                                                }
                                                className="block w-full text-left"
                                            >
                                                <img
                                                    src={item.img}
                                                    alt={item.name}
                                                    className="mb-2 aspect-[4/3] w-full rounded-xl object-cover"
                                                />
                                                <span className="line-clamp-2 font-display font-bold text-[#1A1A2E] hover:text-[#25D366]">
                                                    {item.name}
                                                </span>
                                            </button>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row) => (
                                    <tr
                                        key={row.label}
                                        className="border-t border-[#E9ECEF]"
                                    >
                                        <th className="sticky left-0 z-10 w-32 bg-white p-3 text-left text-xs font-semibold text-[#4A4A6A] uppercase">
                                            {row.label}
                                        </th>
                                        {items.map((item) => (
                                            <td
                                                key={item.id}
                                                className="border-l border-[#E9ECEF] p-3 align-top"
                                            >
                                                {row.render(item)}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                                <tr className="border-t border-[#E9ECEF]">
                                    <th className="sticky left-0 z-10 w-32 bg-white p-3" />
                                    {items.map((item) => (
                                        <td
                                            key={item.id}
                                            className="border-l border-[#E9ECEF] p-3"
                                        >
                                            <button
                                                onClick={() => {
                                                    addItem(item);
                                                    openCart(true);
                                                    setIsOpen(false);
                                                }}
                                                disabled={item.stock === 0}
                                                className="w-full rounded-xl bg-[#25D366] py-2.5 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851] disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Ajouter au panier
                                            </button>
                                        </td>
                                    ))}
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div className="border-t border-[#E9ECEF] px-6 py-3 text-right">
                        <button
                            onClick={clear}
                            className="text-sm font-semibold text-[#4A4A6A] underline-offset-2 hover:underline"
                        >
                            Vider la comparaison
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}
