import { product } from '@/routes/shop';
import type { PriceTier } from '@/types/shop';

/** Formatage monétaire FCFA utilisé dans toute la vitrine. */
export function formatPrice(n: number): string {
    return `${new Intl.NumberFormat('fr-FR').format(n)} FCFA`;
}

/**
 * URL de la page détail d'un produit (route `shop.product`, via Wayfinder).
 * Le modèle est lié par son slug.
 */
export function productUrl(slug: string): string {
    return product.url(slug);
}

/** Frais de port appliqués à un sous-total, selon les règles du magasin. */
export function shippingFor(
    subtotal: number,
    store: { shippingCost: number; freeShippingFrom: number },
): number {
    return subtotal >= store.freeShippingFrom ? 0 : store.shippingCost;
}

/** Palier tarifaire applicable pour une quantité donnée. */
export function getActiveTier(
    priceTiers: PriceTier[] | undefined,
    qty: number,
): PriceTier | null {
    if (!priceTiers || priceTiers.length === 0) {
        return null;
    }

    let active = priceTiers[0];

    for (const tier of priceTiers) {
        if (qty >= tier.minQty) {
            active = tier;
        }
    }

    return active;
}

/** Pourcentage de remise par rapport à l'ancien prix. */
export function getDiscount(
    price: number,
    oldPrice: number | null,
): number | null {
    if (!oldPrice) {
        return null;
    }

    return Math.round((1 - price / oldPrice) * 100);
}

/** Lien WhatsApp universel, partageable et indexable. */
export function whatsAppUrl(phone: string, message: string): string {
    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}

/**
 * Lien profond vers l'application installée sur l'appareil.
 *
 * Contrairement à `wa.me`, ce schéma ouvre WhatsApp directement, sans passer
 * par la page intermédiaire « Continuer vers la discussion ».
 */
export function whatsAppAppUrl(phone: string, message: string): string {
    return `whatsapp://send?phone=${phone}&text=${encodeURIComponent(message)}`;
}

/** Appareil tactile, où l'application WhatsApp est la cible naturelle. */
function isHandheld(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return (
        window.matchMedia('(pointer: coarse)').matches &&
        window.matchMedia('(max-width: 1024px)').matches
    );
}

/**
 * Prépare la cible d'ouverture, à appeler DANS le gestionnaire de clic.
 *
 * Sur ordinateur on réserve un onglet : l'ouvrir plus tard, depuis la réponse
 * du serveur, serait bloqué par l'anti-pop-up. Sur mobile aucun onglet n'est
 * réservé — on bascule directement vers l'application.
 */
export function prepareWhatsAppTarget(): Window | null {
    return isHandheld() ? null : window.open('', '_blank');
}

/**
 * Ouvre la conversation WhatsApp avec le message pré-rempli.
 *
 * Sur mobile, on tente d'abord l'application installée ; si rien ne se passe
 * au bout d'un instant — application absente — on retombe sur `wa.me`.
 */
export function openWhatsAppConversation(
    phone: string,
    message: string,
    target: Window | null = null,
): void {
    const web = whatsAppUrl(phone, message);

    if (!isHandheld()) {
        if (target && !target.closed) {
            target.location.href = web;
        } else {
            window.open(web, '_blank');
        }

        return;
    }

    target?.close();

    // L'onglet reste visible si l'application n'a pas pris la main : c'est le
    // signal qu'il faut basculer sur le lien web.
    const fallback = window.setTimeout(() => {
        if (document.visibilityState === 'visible') {
            window.location.href = web;
        }
    }, 1200);

    document.addEventListener(
        'visibilitychange',
        () => window.clearTimeout(fallback),
        { once: true },
    );

    window.location.href = whatsAppAppUrl(phone, message);
}
