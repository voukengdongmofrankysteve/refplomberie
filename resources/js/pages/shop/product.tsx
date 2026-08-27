import { Link, router, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import SeoTitle from '@/components/seo-title';
import FrequentlyBoughtTogether from '@/components/shop/frequently-bought-together';
import PriceTiers from '@/components/shop/price-tiers';
import ProductGallery from '@/components/shop/product-gallery';
import ProductVideo from '@/components/shop/product-video';
import ShareButtons from '@/components/shop/share-buttons';
import StarRating from '@/components/shop/star-rating';
import { StoreMapCompact } from '@/components/shop/store-map';
import { useShopAuth } from '@/contexts/auth-modal-context';
import { useCart } from '@/contexts/cart-context';
import { useComparison } from '@/contexts/comparison-context';
import { useFavorites } from '@/contexts/favorites-context';
import {
    formatPrice,
    getActiveTier,
    getDiscount,
    productUrl,
} from '@/lib/shop';
import { home } from '@/routes';
import { store as storeReview } from '@/routes/reviews';
import type { Product, ProductReview } from '@/types/shop';

type Props = {
    product: Product;
    related: Product[];
    /** Ce que d'autres clients ont acheté dans la même commande. */
    frequentlyBoughtWith: Product[];
    /** Avis publiés, servis depuis la table `reviews`. */
    reviews: ProductReview[];
    /** Pourquoi le visiteur peut, ou non, déposer un avis. */
    reviewGate: 'guest' | 'not_purchased' | 'already_reviewed' | 'can_review';
    /** URL absolue de la fiche : c'est elle que lisent WhatsApp et Facebook. */
    shareUrl: string;
};

export default function ProductDetail({
    product,
    related,
    frequentlyBoughtWith,
    reviews,
    reviewGate,
    shareUrl,
}: Props) {
    const { addItem, setIsOpen: openCart } = useCart();
    const { toggleFavorite, isFavorite } = useFavorites();
    const { toggleCompare, isComparing } = useComparison();
    const { user, setAuthModal } = useShopAuth();

    const [qty, setQty] = useState(1);
    const [added, setAdded] = useState(false);
    const [shownProductId, setShownProductId] = useState(product.id);

    const reviewForm = useForm({ rating: 5, body: '' });

    // Inertia réutilise ce composant d'une fiche à l'autre : on réinitialise
    // l'état pendant le rendu plutôt que dans un effet.
    if (shownProductId !== product.id) {
        setShownProductId(product.id);
        setQty(1);
        setAdded(false);
    }

    useEffect(() => {
        window.scrollTo(0, 0);
    }, [product.id]);

    const activeTier = getActiveTier(product.priceTiers, qty);
    const activePrice = activeTier ? activeTier.price : product.price;
    const discount = getDiscount(product.price, product.oldPrice);
    const fav = isFavorite(product.id);
    const comparing = isComparing(product.id);
    const images =
        product.images && product.images.length > 0
            ? product.images
            : [product.img];

    const handleAdd = () => {
        addItem({ ...product, price: activePrice }, qty);
        setAdded(true);
        setTimeout(() => setAdded(false), 1500);
    };

    const handleReview = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        if (!user) {
            setAuthModal('login');

            return;
        }

        reviewForm.post(storeReview.url(product.slug), {
            preserveScroll: true,
            onSuccess: () => reviewForm.reset(),
        });
    };

    const quickInfo = [
        { label: 'Livraison', value: 'Partout au Cameroun' },
        { label: 'Stock', value: `${product.stock} unités` },
        { label: 'Catégorie', value: product.category },
        { label: 'Garantie', value: '2 ans minimum' },
    ];

    return (
        <>
            <SeoTitle />

            <div className="min-h-screen bg-[#F8F9FA] pt-20 md:pt-24">
                {/* Fil d'Ariane */}
                <div className="border-b border-[#E9ECEF] bg-white">
                    <div className="mx-auto flex max-w-7xl items-center gap-2 px-4 py-3 text-sm text-[#4A4A6A] md:px-8">
                        <Link
                            href={home()}
                            className="transition-colors hover:text-[#25D366]"
                        >
                            Accueil
                        </Link>
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
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                        <Link
                            href="/#produits"
                            className="transition-colors hover:text-[#25D366]"
                        >
                            Produits
                        </Link>
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
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                        <span className="max-w-[200px] truncate font-medium text-[#1A1A2E]">
                            {product.name}
                        </span>
                    </div>
                </div>

                <div className="mx-auto max-w-7xl px-4 py-10 md:px-8">
                    {/* Bloc produit */}
                    <div className="mb-8 overflow-hidden rounded-2xl border border-[#E9ECEF] bg-white shadow-sm">
                        <div className="grid gap-0 md:grid-cols-2">
                            <div className="bg-[#F8F9FA] p-4">
                                {/* La clé remonte la galerie sur la vue 1 au changement de produit. */}
                                <ProductGallery
                                    key={product.id}
                                    images={images}
                                    name={product.name}
                                    badge={product.badge}
                                    discount={discount}
                                    fav={fav}
                                    onToggleFav={() => toggleFavorite(product)}
                                />
                            </div>

                            <div className="flex flex-col p-6 md:p-8">
                                <p className="mb-2 text-xs font-bold tracking-wider text-[#25D366] uppercase">
                                    {product.category}
                                </p>
                                <h1 className="mb-3 font-display text-2xl leading-snug font-bold text-[#1A1A2E] md:text-3xl">
                                    {product.name}
                                </h1>

                                <div className="mb-4 flex items-center gap-2">
                                    <StarRating rating={product.rating} />
                                    <span className="text-sm text-[#4A4A6A]">
                                        {product.rating} ({product.reviews}{' '}
                                        avis)
                                    </span>
                                </div>

                                {product.warrantyBadges.length > 0 && (
                                    <div className="mb-4 flex flex-wrap gap-2">
                                        {product.warrantyBadges.map((badge) => (
                                            <span
                                                key={badge.value}
                                                className="flex items-center gap-1.5 rounded-full bg-[#25D366]/10 px-3 py-1 text-xs font-semibold text-[#1DA851]"
                                            >
                                                <ShieldCheck className="h-3.5 w-3.5" />
                                                {badge.label}
                                            </span>
                                        ))}
                                    </div>
                                )}

                                <p className="mb-6 leading-relaxed text-[#4A4A6A]">
                                    {product.desc}
                                </p>

                                {product.stock <= 8 && (
                                    <div className="mb-5 flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm">
                                        <svg
                                            className="h-4 w-4 flex-shrink-0 text-amber-500"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"
                                            />
                                        </svg>
                                        <span className="font-medium text-amber-700">
                                            Plus que {product.stock} en stock —
                                            commandez vite !
                                        </span>
                                    </div>
                                )}

                                <PriceTiers
                                    priceTiers={product.priceTiers}
                                    activeQty={qty}
                                />

                                <div className="mb-6 flex items-baseline gap-3">
                                    <span className="font-display text-3xl font-bold text-[#1A1A2E]">
                                        {formatPrice(activePrice)}
                                    </span>
                                    {product.oldPrice &&
                                        activePrice === product.price && (
                                            <span className="text-lg text-[#4A4A6A] line-through">
                                                {formatPrice(product.oldPrice)}
                                            </span>
                                        )}
                                    <span className="text-sm text-[#4A4A6A]">
                                        / pièce
                                    </span>
                                </div>

                                {/* Quantité + panier */}
                                <div className="mb-4 flex items-center gap-3">
                                    <div className="flex items-center overflow-hidden rounded-xl border border-[#E9ECEF]">
                                        <button
                                            onClick={() =>
                                                setQty((q) =>
                                                    Math.max(1, q - 1),
                                                )
                                            }
                                            className="px-4 py-3 text-lg font-bold text-[#4A4A6A] transition-colors hover:bg-gray-100"
                                            aria-label="Diminuer la quantité"
                                        >
                                            −
                                        </button>
                                        <span className="min-w-[3rem] px-5 py-3 text-center font-semibold text-[#1A1A2E]">
                                            {qty}
                                        </span>
                                        <button
                                            onClick={() => setQty((q) => q + 1)}
                                            className="px-4 py-3 text-lg font-bold text-[#4A4A6A] transition-colors hover:bg-gray-100"
                                            aria-label="Augmenter la quantité"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <button
                                        onClick={handleAdd}
                                        className={`flex flex-1 items-center justify-center gap-2 rounded-xl py-3.5 font-bold transition-all ${
                                            added
                                                ? 'bg-green-500 text-white'
                                                : 'bg-[#25D366] text-[#1A1A2E] hover:bg-[#1DA851] hover:shadow-lg'
                                        }`}
                                    >
                                        {added ? (
                                            <>
                                                <svg
                                                    className="h-5 w-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    strokeWidth={2.5}
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                                Ajouté au panier !
                                            </>
                                        ) : (
                                            <>
                                                <svg
                                                    className="h-5 w-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    strokeWidth={2}
                                                >
                                                    <path d="M6 2 3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
                                                    <line
                                                        x1="3"
                                                        y1="6"
                                                        x2="21"
                                                        y2="6"
                                                    />
                                                    <path d="M16 10a4 4 0 01-8 0" />
                                                </svg>
                                                Ajouter au panier
                                            </>
                                        )}
                                    </button>

                                    <button
                                        onClick={() => toggleFavorite(product)}
                                        className={`flex h-12 w-12 items-center justify-center rounded-xl border-2 transition-all ${
                                            fav
                                                ? 'border-red-500 bg-red-50 text-red-500'
                                                : 'border-[#E9ECEF] text-[#4A4A6A] hover:border-red-300 hover:text-red-400'
                                        }`}
                                        aria-label={
                                            fav
                                                ? 'Retirer des favoris'
                                                : 'Ajouter aux favoris'
                                        }
                                    >
                                        <svg
                                            className="h-5 w-5"
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

                                    <button
                                        onClick={() => toggleCompare(product)}
                                        className={`flex h-12 w-12 items-center justify-center rounded-xl border-2 transition-all ${
                                            comparing
                                                ? 'border-[#1A1A2E] bg-[#1A1A2E]/5 text-[#1A1A2E]'
                                                : 'border-[#E9ECEF] text-[#4A4A6A] hover:border-[#1A1A2E]/30'
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
                                            className="h-5 w-5"
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
                                </div>

                                {added && (
                                    <button
                                        onClick={() => openCart(true)}
                                        className="w-full text-sm font-semibold text-[#4A4A6A] underline underline-offset-4 transition-colors hover:text-[#25D366]"
                                    >
                                        Voir mon panier
                                    </button>
                                )}

                                <div className="mt-6 border-t border-[#E9ECEF] pt-6">
                                    <ShareButtons
                                        product={product}
                                        url={shareUrl}
                                    />
                                </div>

                                <div className="mt-6 grid grid-cols-2 gap-4 border-t border-[#E9ECEF] pt-6">
                                    {quickInfo.map((info) => (
                                        <div key={info.label}>
                                            <p className="text-xs font-semibold tracking-wide text-[#4A4A6A] uppercase">
                                                {info.label}
                                            </p>
                                            <p className="mt-0.5 text-sm font-medium text-[#1A1A2E]">
                                                {info.value}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>

                    <ProductVideo videoUrl={product.videoUrl} />

                    <FrequentlyBoughtTogether items={frequentlyBoughtWith} />

                    {/* Avis clients */}
                    <div className="overflow-hidden rounded-2xl border border-[#E9ECEF] bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b border-[#E9ECEF] px-6 py-5 md:px-8">
                            <div>
                                <h2 className="font-display text-xl font-bold text-[#1A1A2E]">
                                    Avis clients
                                </h2>
                                <p className="mt-0.5 text-sm text-[#4A4A6A]">
                                    {product.reviews} avis · Note moyenne{' '}
                                    {product.rating}/5
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                <StarRating rating={product.rating} />
                                <span className="font-display text-xl font-bold text-[#1A1A2E]">
                                    {product.rating}
                                </span>
                            </div>
                        </div>

                        <div className="grid gap-0 divide-y divide-[#E9ECEF] md:grid-cols-2 md:divide-x md:divide-y-0">
                            {/* Formulaire d'avis */}
                            <div className="px-6 py-6 md:px-8">
                                <h3 className="mb-4 font-display text-lg font-bold text-[#1A1A2E]">
                                    Laisser un avis
                                </h3>

                                {reviewGate === 'guest' ? (
                                    <div className="rounded-xl border border-[#25D366]/30 bg-[#E8F5E9] p-5 text-center">
                                        <svg
                                            className="mx-auto mb-3 h-10 w-10 text-[#25D366]"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={1.5}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                                            />
                                        </svg>
                                        <p className="mb-4 text-sm text-[#4A4A6A]">
                                            Connectez-vous pour laisser un
                                            commentaire sur ce produit.
                                        </p>
                                        <button
                                            onClick={() =>
                                                setAuthModal('login')
                                            }
                                            className="rounded-xl bg-[#25D366] px-6 py-2.5 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851]"
                                        >
                                            Se connecter
                                        </button>
                                    </div>
                                ) : reviewGate === 'not_purchased' ? (
                                    <div className="rounded-xl border border-[#E9ECEF] bg-[#F8F9FA] p-5 text-center">
                                        <ShieldCheck className="mx-auto mb-2 h-8 w-8 text-[#4A4A6A]" />
                                        <p className="text-sm font-semibold text-[#1A1A2E]">
                                            Réservé aux clients ayant acheté ce
                                            produit
                                        </p>
                                        <p className="mt-1 text-xs text-[#4A4A6A]">
                                            Les avis ne sont ouverts qu’après un
                                            achat confirmé, pour garantir leur
                                            fiabilité.
                                        </p>
                                    </div>
                                ) : reviewGate === 'already_reviewed' ? (
                                    <div className="rounded-xl border border-green-200 bg-green-50 p-5 text-center">
                                        <svg
                                            className="mx-auto mb-2 h-8 w-8 text-green-600"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                        <p className="text-sm font-semibold text-green-700">
                                            Vous avez déjà donné votre avis sur
                                            ce produit. Merci !
                                        </p>
                                    </div>
                                ) : (
                                    <form
                                        onSubmit={handleReview}
                                        className="space-y-4"
                                    >
                                        <div>
                                            <label className="mb-2 block text-xs font-semibold tracking-wide text-[#4A4A6A] uppercase">
                                                Votre note *
                                            </label>
                                            <StarRating
                                                rating={reviewForm.data.rating}
                                                interactive
                                                onChange={(r) =>
                                                    reviewForm.setData(
                                                        'rating',
                                                        r,
                                                    )
                                                }
                                            />
                                        </div>
                                        <div>
                                            <label
                                                htmlFor="review-text"
                                                className="mb-1.5 block text-xs font-semibold tracking-wide text-[#4A4A6A] uppercase"
                                            >
                                                Votre commentaire *
                                            </label>
                                            <textarea
                                                id="review-text"
                                                rows={4}
                                                required
                                                value={reviewForm.data.body}
                                                onChange={(e) =>
                                                    reviewForm.setData(
                                                        'body',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full resize-none rounded-xl border border-[#E9ECEF] bg-[#F8F9FA] px-4 py-3 text-sm text-[#1A1A2E] transition-all focus:border-[#25D366] focus:bg-white focus:ring-2 focus:ring-[#25D366]/20 focus:outline-none"
                                                placeholder="Partagez votre expérience avec ce produit..."
                                            />
                                        </div>
                                        {reviewForm.errors.body && (
                                            <p className="text-xs text-red-600">
                                                {reviewForm.errors.body}
                                            </p>
                                        )}
                                        <button
                                            type="submit"
                                            disabled={reviewForm.processing}
                                            className="w-full rounded-xl bg-[#25D366] py-3 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851] disabled:opacity-60"
                                        >
                                            {reviewForm.processing
                                                ? 'Publication…'
                                                : 'Publier mon avis'}
                                        </button>
                                    </form>
                                )}
                            </div>

                            {/* Liste des avis */}
                            <div className="max-h-[500px] space-y-5 overflow-y-auto px-6 py-6 md:px-8">
                                {reviews.length === 0 && (
                                    <p className="py-8 text-center text-sm text-[#4A4A6A]">
                                        Aucun avis pour le moment. Soyez le
                                        premier !
                                    </p>
                                )}

                                {reviews.map((c) => (
                                    <div
                                        key={c.id}
                                        className="border-b border-[#F1F3F5] pb-5 last:border-0"
                                    >
                                        <div className="flex items-start gap-3">
                                            <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-[#25D366] text-sm font-bold text-[#1A1A2E]">
                                                {c.avatar}
                                            </div>
                                            <div className="flex-1">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <p className="text-sm font-semibold text-[#1A1A2E]">
                                                            {c.author}
                                                        </p>
                                                        {c.verifiedPurchase && (
                                                            <span className="flex items-center gap-1 rounded-full bg-[#25D366]/10 px-2 py-0.5 text-[10px] font-semibold text-[#1DA851]">
                                                                <ShieldCheck className="h-3 w-3" />
                                                                Achat vérifié
                                                            </span>
                                                        )}
                                                    </div>
                                                    <span className="text-xs text-[#4A4A6A]">
                                                        {c.date}
                                                    </span>
                                                </div>
                                                <StarRating rating={c.rating} />
                                                <p className="mt-2 text-sm leading-relaxed text-[#4A4A6A]">
                                                    {c.text}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Carte magasin */}
                    <StoreMapCompact />

                    {/* Produits similaires */}
                    {related.length > 0 && (
                        <div className="mt-8">
                            <h3 className="mb-5 font-display text-xl font-bold text-[#1A1A2E]">
                                Produits similaires
                            </h3>
                            <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
                                {related.map((p) => (
                                    <button
                                        key={p.id}
                                        onClick={() =>
                                            router.visit(productUrl(p.slug))
                                        }
                                        className="group overflow-hidden rounded-xl border border-[#E9ECEF] bg-white text-left transition-all hover:border-[#25D366] hover:shadow-md"
                                    >
                                        <div className="aspect-[4/3] overflow-hidden">
                                            <img
                                                src={p.img}
                                                alt={p.name}
                                                className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            />
                                        </div>
                                        <div className="p-3">
                                            <p className="line-clamp-1 text-sm font-semibold text-[#1A1A2E]">
                                                {p.name}
                                            </p>
                                            <p className="mt-1 text-xs font-bold text-[#25D366]">
                                                {formatPrice(p.price)}
                                            </p>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
