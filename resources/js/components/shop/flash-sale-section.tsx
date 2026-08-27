import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import StarRating from '@/components/shop/star-rating';
import { useCart } from '@/contexts/cart-context';
import { useCountdown } from '@/hooks/use-countdown';
import { formatPrice, productUrl } from '@/lib/shop';
import type { FlashSale, FlashSaleProduct, Product } from '@/types/shop';

/**
 * Adapte un article de vente flash à la forme attendue par le panier.
 *
 * Le panier travaille sur des `Product` complets ; une vente flash n'a pas
 * besoin de porter la description ou les paliers de prix pour autant — ils
 * restent simplement vides, sans conséquence sur le calcul du panier.
 */
function toCartProduct(item: FlashSaleProduct): Product {
    return {
        id: item.id,
        slug: item.slug,
        category: item.category,
        categoryLabel: item.category,
        name: item.name,
        desc: '',
        videoUrl: null,
        price: item.price,
        oldPrice: item.originalPrice,
        badge: null,
        warrantyBadges: [],
        img: item.img,
        images: [item.img],
        rating: item.rating,
        reviews: item.reviews,
        stock: item.stock,
        priceTiers: [],
    };
}

function TimeBox({ value, label }: { value: number; label: string }) {
    return (
        <div className="flex flex-col items-center">
            <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 font-display text-xl font-bold text-white tabular-nums md:h-14 md:w-14 md:text-2xl">
                {String(value).padStart(2, '0')}
            </span>
            <span className="mt-1 text-[10px] font-semibold tracking-wide text-white/70 uppercase">
                {label}
            </span>
        </div>
    );
}

/**
 * Sélection à prix cassé, pour un temps compté.
 *
 * Deux visages : « démarre dans » pour une vente programmée mais pas encore
 * ouverte, « se termine dans » une fois qu'elle l'est. Quand le compte à
 * rebours touche zéro, on redemande simplement ce prop au serveur plutôt que
 * de deviner la suite — c'est lui qui décide ce qui est réellement en cours.
 */
export default function FlashSaleSection({ sale }: { sale: FlashSale | null }) {
    const { addItem } = useCart();
    const upcoming = sale?.status === 'upcoming';
    const target = upcoming ? sale?.startsAt : sale?.endsAt;
    const countdown = useCountdown(target ?? new Date().toISOString());
    const reloaded = useRef(false);

    useEffect(() => {
        if (countdown.ended && sale && !reloaded.current) {
            // La bascule programmée → en cours (ou la fin d'une vente) ne se
            // décide pas dans le navigateur : on redemande la vérité au
            // serveur plutôt que d'improviser un nouvel état ici.
            reloaded.current = true;
            router.reload({ only: ['flashSale'] });
        }
    }, [countdown.ended, sale]);

    if (!sale || countdown.ended) {
        return null;
    }

    return (
        <section className="relative overflow-hidden bg-gradient-to-br from-red-600 via-red-500 to-orange-500 py-12 md:py-16">
            <div
                className="absolute top-0 right-0 h-72 w-72 translate-x-1/3 -translate-y-1/3 rounded-full bg-white/10"
                aria-hidden="true"
            />

            <div className="relative mx-auto max-w-7xl px-4 md:px-8">
                <div className="mb-8 flex flex-wrap items-center justify-between gap-6">
                    <div>
                        <p className="mb-2 flex items-center gap-2 text-xs font-bold tracking-widest text-white/80 uppercase">
                            <svg
                                className="h-4 w-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                                aria-hidden="true"
                            >
                                <path d="M11 3a1 1 0 10-2 0v1.055A6.002 6.002 0 004.055 9H3a1 1 0 100 2h1.055A6.002 6.002 0 009 15.945V17a1 1 0 102 0v-1.055A6.002 6.002 0 0015.945 11H17a1 1 0 100-2h-1.055A6.002 6.002 0 0011 4.055V3z" />
                            </svg>
                            {upcoming ? 'Vente flash à venir' : 'Vente flash'}
                        </p>
                        <h2 className="font-display text-2xl font-bold text-white md:text-3xl">
                            {sale.title}
                        </h2>
                        <p className="mt-1 text-xs font-semibold text-white/80">
                            {upcoming ? 'Débute dans' : 'Se termine dans'}
                        </p>
                    </div>

                    <div className="flex items-center gap-2 md:gap-3">
                        <TimeBox value={countdown.days} label="jours" />
                        <span className="pb-4 font-display text-xl font-bold text-white/60">
                            :
                        </span>
                        <TimeBox value={countdown.hours} label="heures" />
                        <span className="pb-4 font-display text-xl font-bold text-white/60">
                            :
                        </span>
                        <TimeBox value={countdown.minutes} label="min" />
                        <span className="pb-4 font-display text-xl font-bold text-white/60">
                            :
                        </span>
                        <TimeBox value={countdown.seconds} label="sec" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {sale.products.map((item) => (
                        <div
                            key={item.id}
                            className="flex flex-col overflow-hidden rounded-2xl bg-white"
                        >
                            <a
                                href={productUrl(item.slug)}
                                className="block aspect-square overflow-hidden bg-[#F8F9FA]"
                            >
                                <img
                                    src={item.img}
                                    alt={item.name}
                                    loading="lazy"
                                    className="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                                />
                            </a>
                            <div className="flex flex-1 flex-col p-3">
                                <span className="mb-1 w-fit rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">
                                    −{item.discount}%
                                </span>
                                <a
                                    href={productUrl(item.slug)}
                                    className="mb-1 line-clamp-2 text-xs leading-snug font-bold text-[#1A1A2E] hover:text-red-600"
                                >
                                    {item.name}
                                </a>
                                <div className="mb-2 flex items-center gap-1">
                                    <StarRating
                                        rating={item.rating}
                                        className="h-3 w-3"
                                    />
                                    <span className="text-[10px] text-[#4A4A6A]">
                                        ({item.reviews})
                                    </span>
                                </div>
                                <div className="mt-auto">
                                    <div className="flex items-baseline gap-1.5">
                                        <span className="font-display text-sm font-bold text-red-600">
                                            {formatPrice(item.price)}
                                        </span>
                                        <span className="text-[10px] text-[#4A4A6A] line-through">
                                            {formatPrice(item.originalPrice)}
                                        </span>
                                    </div>
                                    <button
                                        onClick={() =>
                                            addItem(toCartProduct(item))
                                        }
                                        disabled={upcoming || item.stock === 0}
                                        title={
                                            upcoming
                                                ? 'Disponible dès le début de la vente'
                                                : undefined
                                        }
                                        className="mt-2 w-full rounded-lg bg-[#1A1A2E] py-2 text-xs font-bold text-white transition-colors hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {upcoming
                                            ? 'Bientôt'
                                            : item.stock === 0
                                              ? 'Épuisé'
                                              : 'Ajouter'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
