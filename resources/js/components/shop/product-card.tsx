import { router } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import type { MouseEvent } from 'react';
import PriceTiers from '@/components/shop/price-tiers';
import StarRating from '@/components/shop/star-rating';
import { useCart } from '@/contexts/cart-context';
import { useComparison } from '@/contexts/comparison-context';
import { useFavorites } from '@/contexts/favorites-context';
import {
    formatPrice,
    getActiveTier,
    getDiscount,
    productUrl,
} from '@/lib/shop';
import type { Product } from '@/types/shop';

export default function ProductCard({ product }: { product: Product }) {
    const { addItem } = useCart();
    const { toggleFavorite, isFavorite } = useFavorites();
    const { toggleCompare, isComparing } = useComparison();
    const [qty, setQty] = useState(1);
    const [added, setAdded] = useState(false);

    const activeTier = getActiveTier(product.priceTiers, qty);
    const activePrice = activeTier ? activeTier.price : product.price;
    const fav = isFavorite(product.id);
    const comparing = isComparing(product.id);
    const discount = getDiscount(product.price, product.oldPrice);

    const goToDetail = () => router.visit(productUrl(product.slug));

    const handleAdd = (e: MouseEvent) => {
        e.stopPropagation();
        addItem({ ...product, price: activePrice }, qty);
        setAdded(true);
        setTimeout(() => setAdded(false), 1500);
    };

    return (
        <article className="product-card group flex flex-col overflow-hidden rounded-2xl border border-[#E9ECEF] bg-white">
            {/* Visuel */}
            <div
                className="relative aspect-[4/3] cursor-pointer overflow-hidden bg-[#F8F9FA]"
                onClick={goToDetail}
                role="button"
                aria-label={`Voir le détail : ${product.name}`}
            >
                <img
                    src={product.img}
                    alt={product.name}
                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    loading="lazy"
                />

                {/* Badges */}
                <div className="absolute top-3 left-3 flex flex-col gap-1.5">
                    {product.badge && (
                        <span
                            className={`rounded-full px-2.5 py-1 text-[10px] font-bold tracking-wide uppercase shadow-sm ${
                                product.badge === 'Promo'
                                    ? 'bg-red-500 text-white'
                                    : product.badge === 'Bestseller'
                                      ? 'bg-[#25D366] text-[#1A1A2E]'
                                      : product.badge === 'Nouveau'
                                        ? 'bg-[#1A1A2E] text-white'
                                        : 'bg-[#4A4A6A] text-white'
                            }`}
                        >
                            {product.badge}
                        </span>
                    )}
                    {discount && (
                        <span className="rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">
                            -{discount}%
                        </span>
                    )}
                </div>

                {/* Favori et comparateur */}
                <div className="absolute top-3 right-3 flex gap-1.5">
                    <button
                        onClick={(e) => {
                            e.stopPropagation();
                            toggleCompare(product);
                        }}
                        className={`flex h-8 w-8 items-center justify-center rounded-xl shadow transition-all ${
                            comparing
                                ? 'bg-[#1A1A2E] text-white'
                                : 'bg-white/90 text-[#4A4A6A] opacity-0 group-hover:opacity-100 hover:text-[#1A1A2E]'
                        }`}
                        aria-label={
                            comparing
                                ? 'Retirer du comparateur'
                                : 'Ajouter au comparateur'
                        }
                        title={
                            comparing
                                ? 'Retirer du comparateur'
                                : 'Comparer ce produit'
                        }
                    >
                        <svg
                            className="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2}
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M9 4.5v15m6-15v15M4.5 8.25h3m9 0h3m-15 7.5h3m9 0h3"
                            />
                        </svg>
                    </button>

                    <button
                        onClick={(e) => {
                            e.stopPropagation();
                            toggleFavorite(product);
                        }}
                        className={`flex h-8 w-8 items-center justify-center rounded-xl shadow transition-all ${
                            fav
                                ? 'bg-red-500 text-white'
                                : 'bg-white/90 text-[#4A4A6A] opacity-0 group-hover:opacity-100 hover:text-red-500'
                        }`}
                        aria-label={
                            fav ? 'Retirer des favoris' : 'Ajouter aux favoris'
                        }
                    >
                        <svg
                            className="h-4 w-4"
                            fill={fav ? 'currentColor' : 'none'}
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
                    </button>
                </div>

                {/* Alerte stock */}
                {product.stock <= 8 && (
                    <div className="absolute right-3 bottom-3 left-3 rounded-lg bg-[#1A1A2E]/80 py-1.5 text-center text-[10px] font-semibold text-white backdrop-blur-sm">
                        Plus que {product.stock} en stock
                    </div>
                )}
            </div>

            {/* Contenu */}
            <div className="flex flex-1 flex-col p-4">
                <p className="mb-1 text-[10px] font-semibold tracking-wider text-[#25D366] uppercase">
                    {product.category.charAt(0).toUpperCase() +
                        product.category.slice(1)}
                </p>

                <h3
                    className="mb-2 line-clamp-2 cursor-pointer font-display text-base leading-snug font-bold text-[#1A1A2E] transition-colors hover:text-[#25D366]"
                    onClick={goToDetail}
                >
                    {product.name}
                </h3>

                <p className="mb-3 line-clamp-2 text-xs leading-relaxed text-[#4A4A6A]">
                    {product.desc}
                </p>

                {/* Note */}
                <div className="mb-3 flex items-center gap-1.5">
                    <StarRating
                        rating={product.rating}
                        className="h-3.5 w-3.5"
                    />
                    <span className="text-xs text-[#4A4A6A]">
                        {product.rating} ({product.reviews})
                    </span>
                </div>

                {/* Garantie / authenticité */}
                {product.warrantyBadges.length > 0 && (
                    <div className="mb-3 flex flex-wrap gap-1.5">
                        {product.warrantyBadges.map((badge) => (
                            <span
                                key={badge.value}
                                className="flex items-center gap-1 rounded-full bg-[#25D366]/10 px-2 py-0.5 text-[10px] font-semibold text-[#1DA851]"
                                title={badge.label}
                            >
                                <ShieldCheck className="h-3 w-3" />
                                {badge.label}
                            </span>
                        ))}
                    </div>
                )}

                {/* Paliers de prix */}
                <PriceTiers
                    priceTiers={product.priceTiers}
                    activeQty={qty}
                    compact
                />

                {/* Prix courant */}
                <div className="mb-4 flex items-baseline gap-2">
                    <span className="font-display text-xl font-bold text-[#1A1A2E]">
                        {formatPrice(activePrice)}
                    </span>
                    {product.oldPrice && activePrice === product.price && (
                        <span className="text-sm text-[#4A4A6A] line-through">
                            {formatPrice(product.oldPrice)}
                        </span>
                    )}
                    <span className="text-[10px] text-[#4A4A6A]">/ pièce</span>
                </div>

                {/* Quantité + ajout */}
                <div className="mt-auto flex items-center gap-2">
                    <div
                        className="flex items-center overflow-hidden rounded-lg border border-[#E9ECEF]"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <button
                            onClick={() => setQty((q) => Math.max(1, q - 1))}
                            className="px-2.5 py-2 text-sm font-bold text-[#4A4A6A] transition-colors hover:bg-gray-100"
                            aria-label="Diminuer la quantité"
                        >
                            −
                        </button>
                        <span className="min-w-[2rem] px-2.5 py-2 text-center text-sm font-semibold text-[#1A1A2E]">
                            {qty}
                        </span>
                        <button
                            onClick={() => setQty((q) => q + 1)}
                            className="px-2.5 py-2 text-sm font-bold text-[#4A4A6A] transition-colors hover:bg-gray-100"
                            aria-label="Augmenter la quantité"
                        >
                            +
                        </button>
                    </div>

                    <button
                        onClick={handleAdd}
                        className={`flex flex-1 items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-bold transition-all ${
                            added
                                ? 'scale-95 bg-green-500 text-white'
                                : 'bg-[#25D366] text-[#1A1A2E] hover:bg-[#1DA851] hover:shadow-md'
                        }`}
                    >
                        {added ? (
                            <>
                                <svg
                                    className="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    strokeWidth={2.5}
                                    aria-hidden="true"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                Ajouté !
                            </>
                        ) : (
                            <>
                                <svg
                                    className="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    strokeWidth={2}
                                    aria-hidden="true"
                                >
                                    <path d="M6 2 3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
                                    <line x1="3" y1="6" x2="21" y2="6" />
                                    <path d="M16 10a4 4 0 01-8 0" />
                                </svg>
                                Ajouter
                            </>
                        )}
                    </button>
                </div>

                <button
                    onClick={goToDetail}
                    className="mt-2 text-center text-xs text-[#4A4A6A] underline underline-offset-2 transition-colors hover:text-[#25D366]"
                >
                    Voir le détail &amp; les avis
                </button>
            </div>
        </article>
    );
}
