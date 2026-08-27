import { router, usePage } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import {
    formatPrice,
    openWhatsAppConversation,
    prepareWhatsAppTarget,
    shippingFor,
} from '@/lib/shop';
import { track } from '@/lib/track';
import { store as storeOrder } from '@/routes/orders';
import { check as checkPromo } from '@/routes/promo-codes';
import { store as storeQuote } from '@/routes/quotes';
import type {
    AppliedPromo,
    CartItem,
    Product,
    QuoteDetails,
} from '@/types/shop';

/** Coordonnées saisies au moment de valider la commande. */
export type CheckoutDetails = {
    customer_name: string;
    customer_phone: string;
    customer_address: string;
    note: string;
};

type CartContextValue = {
    items: CartItem[];
    addItem: (product: Product, qty?: number) => void;
    removeItem: (id: number) => void;
    updateQty: (id: number, qty: number) => void;
    clearCart: () => void;
    totalItems: number;
    subtotal: number;
    shipping: number;
    discount: number;
    total: number;
    isOpen: boolean;
    setIsOpen: (open: boolean) => void;
    /** Enregistre la commande en base puis ouvre WhatsApp. */
    checkout: (details: CheckoutDetails) => void;
    isPlacingOrder: boolean;
    /** Établit un devis PDF sans engager la commande. */
    requestQuote: (details: QuoteDetails) => void;
    isRequestingQuote: boolean;
    promo: AppliedPromo | null;
    promoError: string | null;
    isCheckingPromo: boolean;
    applyPromo: (code: string) => void;
    clearPromo: () => void;
    toast: string | null;
};

const CartContext = createContext<CartContextValue | null>(null);

export function CartProvider({ children }: { children: ReactNode }) {
    const { store } = usePage().props;
    const [items, setItems] = useState<CartItem[]>([]);
    const [isOpen, setIsOpen] = useState(false);
    const [isPlacingOrder, setIsPlacingOrder] = useState(false);
    const [isRequestingQuote, setIsRequestingQuote] = useState(false);
    const [promo, setPromo] = useState<AppliedPromo | null>(null);
    const [promoError, setPromoError] = useState<string | null>(null);
    const [isCheckingPromo, setIsCheckingPromo] = useState(false);
    const [toast, setToast] = useState<string | null>(null);
    const toastTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const showToast = useCallback((msg: string) => {
        setToast(msg);

        if (toastTimer.current) {
            clearTimeout(toastTimer.current);
        }

        toastTimer.current = setTimeout(() => setToast(null), 2500);
    }, []);

    const addItem = useCallback(
        (product: Product, qty = 1) => {
            setItems((prev) => {
                const existing = prev.find((i) => i.id === product.id);

                if (existing) {
                    return prev.map((i) =>
                        i.id === product.id ? { ...i, qty: i.qty + qty } : i,
                    );
                }

                return [...prev, { ...product, qty }];
            });

            // Le panier vit dans le navigateur : sans cette déclaration, le
            // pas entre « fiche consultée » et « commande passée » serait
            // invisible dans les statistiques.
            track('add_to_cart', {
                subject: 'product',
                id: product.id,
                label: product.name,
                value: product.price * qty,
            });

            showToast(`"${product.name}" ajouté au panier`);
        },
        [showToast],
    );

    const removeItem = useCallback((id: number) => {
        setItems((prev) => prev.filter((i) => i.id !== id));
    }, []);

    const updateQty = useCallback((id: number, qty: number) => {
        if (qty < 1) {
            return;
        }

        setItems((prev) => prev.map((i) => (i.id === id ? { ...i, qty } : i)));
    }, []);

    const clearCart = useCallback(() => setItems([]), []);

    const totalItems = items.reduce((sum, i) => sum + i.qty, 0);
    const subtotal = items.reduce((sum, i) => sum + i.price * i.qty, 0);

    // Le serveur reste l'autorité sur la remise ; ces valeurs ne servent qu'à
    // l'affichage et sont recalculées à l'enregistrement de la commande.
    const discount = promo ? Math.min(promo.discount, subtotal) : 0;
    const shipping = promo ? promo.shipping : shippingFor(subtotal, store);
    const total = subtotal - discount + shipping;

    /** Soumet un code au serveur, qui seul décide de sa validité. */
    const verifyPromo = useCallback(
        (code: string, amount: number, announce: boolean) => {
            setIsCheckingPromo(true);

            // Vérification en lecture seule : une requête GET suffit, et évite
            // d'avoir à porter un jeton CSRF hors du flux Inertia.
            return fetch(
                checkPromo.url({ query: { code, subtotal: amount } }),
                { headers: { Accept: 'application/json' } },
            )
                .then((response) => response.json())
                .then(
                    (
                        data: AppliedPromo & {
                            valid: boolean;
                            message: string;
                        },
                    ) => {
                        if (data.valid) {
                            setPromo({
                                code: data.code,
                                label: data.label,
                                advantage: data.advantage,
                                discount: data.discount,
                                shipping: data.shipping,
                            });
                            setPromoError(null);

                            if (announce) {
                                showToast(data.message);
                            }

                            return;
                        }

                        setPromo(null);
                        setPromoError(data.message);
                    },
                )
                .catch(() => {
                    setPromo(null);
                    setPromoError('Vérification impossible pour le moment.');
                })
                .finally(() => setIsCheckingPromo(false));
        },
        [showToast],
    );

    const applyPromo = useCallback(
        (code: string) => {
            if (code.trim() === '') {
                return;
            }

            void verifyPromo(code, subtotal, true);
        },
        [verifyPromo, subtotal],
    );

    const clearPromo = useCallback(() => {
        setPromo(null);
        setPromoError(null);
    }, []);

    // Le panier évolue après l'application du code : la remise et le seuil de
    // franchise de port sont revalidés, sinon un montant périmé s'afficherait.
    const appliedCode = promo?.code ?? null;

    useEffect(() => {
        if (appliedCode === null) {
            return;
        }

        // Court délai : régler une quantité au clavier ou aux boutons +/−
        // ne déclenche qu'une seule vérification, une fois la main levée.
        const timer = window.setTimeout(
            () => void verifyPromo(appliedCode, subtotal, false),
            300,
        );

        return () => window.clearTimeout(timer);
    }, [appliedCode, subtotal, verifyPromo]);

    /**
     * La commande est d'abord persistée — le serveur recalcule les prix à
     * partir des paliers et du code promo — puis le récapitulatif part sur
     * WhatsApp.
     */
    const checkout = useCallback(
        (details: CheckoutDetails) => {
            if (items.length === 0 || isPlacingOrder) {
                return;
            }

            setIsPlacingOrder(true);
            track('checkout_started', { value: total });

            // Cible réservée MAINTENANT, dans le clic de l'utilisateur : sur
            // ordinateur un onglet (l'ouvrir depuis la réponse asynchrone plus
            // bas serait bloqué par l'anti-pop-up), sur mobile rien, puisque
            // c'est l'application WhatsApp qui prendra la main.
            const target = prepareWhatsAppTarget();

            const lines = items.map(
                (i) =>
                    `• ${i.name} x${i.qty} — ${formatPrice(i.price * i.qty)}`,
            );

            router.post(
                storeOrder.url(),
                {
                    ...details,
                    promo_code: promo?.code ?? '',
                    items: items.map((i) => ({
                        product_id: i.id,
                        quantity: i.qty,
                    })),
                },
                {
                    preserveScroll: true,
                    onSuccess: (page) => {
                        const reference = page.props.flash.orderReference;

                        const message =
                            `🛒 *Nouvelle commande — ${store.name}*\n\n` +
                            (reference ? `Référence : *${reference}*\n` : '') +
                            `Client : ${details.customer_name}\n` +
                            `Téléphone : ${details.customer_phone}\n` +
                            (details.customer_address
                                ? `Adresse : ${details.customer_address}\n`
                                : '') +
                            (details.note ? `Note : ${details.note}\n` : '') +
                            `\n${lines.join('\n')}\n\n` +
                            (discount > 0
                                ? `Remise (${promo?.code}) : -${formatPrice(discount)}\n`
                                : '') +
                            `Livraison : ${shipping === 0 ? 'Gratuite' : formatPrice(shipping)}\n` +
                            `*Total : ${formatPrice(total)}*\n\n` +
                            `Merci de confirmer ma commande.\n\n` +
                            `🔗 ${window.location.origin}`;

                        openWhatsAppConversation(
                            store.whatsapp,
                            message,
                            target,
                        );

                        clearCart();
                        clearPromo();
                        setIsOpen(false);
                    },
                    onError: () => target?.close(),
                    onFinish: () => setIsPlacingOrder(false),
                },
            );
        },
        [
            items,
            isPlacingOrder,
            shipping,
            discount,
            promo,
            total,
            store,
            clearCart,
            clearPromo,
        ],
    );

    /**
     * Établit un devis à partir du panier.
     *
     * Rien n'est engagé : le panier reste intact, aucun code promo n'est
     * consommé. Le PDF est téléchargé dès que le serveur a répondu.
     */
    const requestQuote = useCallback(
        (details: QuoteDetails) => {
            if (items.length === 0 || isRequestingQuote) {
                return;
            }

            setIsRequestingQuote(true);

            router.post(
                storeQuote.url(),
                {
                    ...details,
                    items: items.map((i) => ({
                        product_id: i.id,
                        quantity: i.qty,
                    })),
                },
                {
                    preserveScroll: true,
                    onSuccess: (page) => {
                        const url = page.props.flash.quoteUrl;

                        if (url) {
                            // Réponse en pièce jointe : le navigateur télécharge
                            // sans quitter la page.
                            window.location.href = url;
                        }
                    },
                    onFinish: () => setIsRequestingQuote(false),
                },
            );
        },
        [items, isRequestingQuote],
    );

    const value = useMemo<CartContextValue>(
        () => ({
            items,
            addItem,
            removeItem,
            updateQty,
            clearCart,
            totalItems,
            subtotal,
            shipping,
            discount,
            total,
            isOpen,
            setIsOpen,
            checkout,
            isPlacingOrder,
            requestQuote,
            isRequestingQuote,
            promo,
            promoError,
            isCheckingPromo,
            applyPromo,
            clearPromo,
            toast,
        }),
        [
            items,
            addItem,
            removeItem,
            updateQty,
            clearCart,
            totalItems,
            subtotal,
            shipping,
            discount,
            total,
            isOpen,
            checkout,
            isPlacingOrder,
            requestQuote,
            isRequestingQuote,
            promo,
            promoError,
            isCheckingPromo,
            applyPromo,
            clearPromo,
            toast,
        ],
    );

    return (
        <CartContext.Provider value={value}>{children}</CartContext.Provider>
    );
}

export function useCart(): CartContextValue {
    const ctx = useContext(CartContext);

    if (!ctx) {
        throw new Error('useCart must be used inside CartProvider');
    }

    return ctx;
}
