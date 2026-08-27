import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import WhatsAppIcon from '@/components/shop/whatsapp-icon';
import { useShopAuth } from '@/contexts/auth-modal-context';
import { useCart } from '@/contexts/cart-context';
import type { CheckoutDetails } from '@/contexts/cart-context';
import { formatPrice } from '@/lib/shop';
import type { QuoteDetails } from '@/types/shop';

const FIELD_CLASS =
    'w-full rounded-xl border border-[#E9ECEF] bg-[#F8F9FA] px-3 py-2.5 text-sm text-[#1A1A2E] transition-all focus:border-[#25D366] focus:bg-white focus:ring-2 focus:ring-[#25D366]/20 focus:outline-none';

const LABEL_CLASS =
    'mb-1 block text-[10px] font-semibold tracking-wide text-[#4A4A6A] uppercase';

export default function CartPanel() {
    const { store } = usePage().props;
    const { user } = useShopAuth();
    const {
        items,
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
    } = useCart();

    // Le pied du panier a trois visages : le récapitulatif, le formulaire de
    // commande, et celui du devis.
    const [mode, setMode] = useState<'cart' | 'order' | 'quote'>('cart');
    const [codeInput, setCodeInput] = useState('');
    const [details, setDetails] = useState<CheckoutDetails>({
        customer_name: user?.name ?? '',
        customer_phone: user?.phone ?? '',
        customer_address: user?.address ?? '',
        note: '',
    });
    const [quoteDetails, setQuoteDetails] = useState<QuoteDetails>({
        customer_name: user?.name ?? '',
        customer_phone: user?.phone ?? '',
        customer_email: user?.email ?? '',
        customer_company: '',
        customer_address: user?.address ?? '',
        note: '',
    });

    if (!isOpen) {
        return null;
    }

    const freeShipping = shipping === 0;

    const handleChange = (
        e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>,
    ) => setDetails((d) => ({ ...d, [e.target.name]: e.target.value }));

    const handleQuoteChange = (
        e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>,
    ) => setQuoteDetails((d) => ({ ...d, [e.target.name]: e.target.value }));

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        checkout(details);
    };

    const handleQuoteSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        requestQuote(quoteDetails);
    };

    const handlePromoSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        applyPromo(codeInput);
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
                aria-label="Votre panier"
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
                                aria-hidden="true"
                            >
                                <path d="M6 2 3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
                                <line x1="3" y1="6" x2="21" y2="6" />
                                <path d="M16 10a4 4 0 01-8 0" />
                            </svg>
                        </div>
                        <div>
                            <h2 className="font-display text-lg leading-none font-bold text-[#1A1A2E]">
                                Mon panier
                            </h2>
                            <p className="text-xs text-[#4A4A6A]">
                                {totalItems} article
                                {totalItems !== 1 ? 's' : ''}
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={() => setIsOpen(false)}
                        className="flex h-8 w-8 items-center justify-center rounded-lg bg-white transition-colors hover:bg-gray-100"
                        aria-label="Fermer le panier"
                    >
                        <svg
                            className="h-4 w-4 text-[#4A4A6A]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2.5}
                            aria-hidden="true"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                {/* Articles */}
                <div className="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    {items.length === 0 ? (
                        <div className="flex h-full flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#E8F5E9]">
                                <svg
                                    className="h-8 w-8 text-[#25D366]"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    strokeWidth={1.5}
                                    aria-hidden="true"
                                >
                                    <path d="M6 2 3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
                                    <line x1="3" y1="6" x2="21" y2="6" />
                                    <path d="M16 10a4 4 0 01-8 0" />
                                </svg>
                            </div>
                            <div>
                                <p className="font-display text-lg font-bold text-[#1A1A2E]">
                                    Panier vide
                                </p>
                                <p className="mt-1 text-sm text-[#4A4A6A]">
                                    Ajoutez des produits pour commencer
                                </p>
                            </div>
                            <button
                                onClick={() => setIsOpen(false)}
                                className="mt-2 rounded-xl bg-[#25D366] px-6 py-3 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851]"
                            >
                                Voir les produits
                            </button>
                        </div>
                    ) : (
                        <>
                            {items.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex gap-3 rounded-xl border border-[#E9ECEF] bg-[#F8F9FA] p-3"
                                >
                                    <img
                                        src={item.img}
                                        alt={item.name}
                                        className="h-16 w-16 flex-shrink-0 rounded-lg object-cover"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <p className="line-clamp-1 text-sm font-semibold text-[#1A1A2E]">
                                            {item.name}
                                        </p>
                                        <p className="mt-0.5 text-xs text-[#4A4A6A]">
                                            {formatPrice(item.price)} / unité
                                        </p>
                                        <div className="mt-2 flex items-center justify-between">
                                            <div className="flex items-center overflow-hidden rounded-lg border border-[#E9ECEF] bg-white">
                                                <button
                                                    onClick={() =>
                                                        updateQty(
                                                            item.id,
                                                            item.qty - 1,
                                                        )
                                                    }
                                                    className="px-2 py-1 text-sm font-bold text-[#4A4A6A] hover:bg-gray-100"
                                                    aria-label="Réduire la quantité"
                                                >
                                                    −
                                                </button>
                                                <span className="min-w-[1.5rem] px-2 py-1 text-center text-sm font-semibold text-[#1A1A2E]">
                                                    {item.qty}
                                                </span>
                                                <button
                                                    onClick={() =>
                                                        updateQty(
                                                            item.id,
                                                            item.qty + 1,
                                                        )
                                                    }
                                                    className="px-2 py-1 text-sm font-bold text-[#4A4A6A] hover:bg-gray-100"
                                                    aria-label="Augmenter la quantité"
                                                >
                                                    +
                                                </button>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-bold text-[#1A1A2E]">
                                                    {formatPrice(
                                                        item.price * item.qty,
                                                    )}
                                                </span>
                                                <button
                                                    onClick={() =>
                                                        removeItem(item.id)
                                                    }
                                                    className="flex h-6 w-6 items-center justify-center rounded-md text-[#4A4A6A] transition-colors hover:bg-red-50 hover:text-red-500"
                                                    aria-label={`Supprimer ${item.name}`}
                                                >
                                                    <svg
                                                        className="h-3.5 w-3.5"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        strokeWidth={2.5}
                                                        aria-hidden="true"
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
                                </div>
                            ))}
                            <button
                                onClick={clearCart}
                                className="w-full py-1 text-xs font-medium text-red-500 transition-colors hover:text-red-700"
                            >
                                Vider le panier
                            </button>
                        </>
                    )}
                </div>

                {/* Pied */}
                {items.length > 0 && (
                    <div className="space-y-4 border-t border-[#E9ECEF] bg-white px-5 py-5">
                        {/* Avancement livraison gratuite */}
                        {!freeShipping ? (
                            <div className="rounded-xl border border-[#25D366]/30 bg-[#E8F5E9] px-4 py-3">
                                <p className="text-xs text-[#4A4A6A]">
                                    Plus que{' '}
                                    <span className="font-bold text-[#1DA851]">
                                        {formatPrice(
                                            store.freeShippingFrom - subtotal,
                                        )}
                                    </span>{' '}
                                    pour la livraison gratuite
                                </p>
                                <div className="mt-2 h-1.5 rounded-full bg-[#E9ECEF]">
                                    <div
                                        className="h-1.5 rounded-full bg-[#25D366] transition-all"
                                        style={{
                                            width: `${Math.min((subtotal / store.freeShippingFrom) * 100, 100)}%`,
                                        }}
                                        role="progressbar"
                                        aria-valuenow={subtotal}
                                        aria-valuemin={0}
                                        aria-valuemax={store.freeShippingFrom}
                                    />
                                </div>
                            </div>
                        ) : (
                            <div className="rounded-xl border border-green-200 bg-green-50 px-4 py-3">
                                <p className="flex items-center gap-1.5 text-xs font-semibold text-green-700">
                                    <svg
                                        className="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        strokeWidth={2.5}
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    Livraison gratuite débloquée !
                                </p>
                            </div>
                        )}

                        {/* Code promo */}
                        {promo ? (
                            <div className="flex items-center justify-between gap-3 rounded-xl border border-[#25D366]/40 bg-[#E8F5E9] px-4 py-2.5">
                                <div className="min-w-0">
                                    <p className="truncate text-xs font-bold text-[#1A1A2E]">
                                        {promo.code}
                                        <span className="ml-2 font-semibold text-[#1DA851]">
                                            {promo.advantage}
                                        </span>
                                    </p>
                                    {promo.label && (
                                        <p className="truncate text-[10px] text-[#4A4A6A]">
                                            {promo.label}
                                        </p>
                                    )}
                                </div>
                                <button
                                    type="button"
                                    onClick={() => {
                                        clearPromo();
                                        setCodeInput('');
                                    }}
                                    className="flex-shrink-0 text-[10px] font-semibold text-[#4A4A6A] underline underline-offset-2 hover:text-red-600"
                                >
                                    Retirer
                                </button>
                            </div>
                        ) : (
                            <form onSubmit={handlePromoSubmit}>
                                <div className="flex gap-2">
                                    <input
                                        value={codeInput}
                                        onChange={(e) =>
                                            setCodeInput(e.target.value)
                                        }
                                        className={`${FIELD_CLASS} uppercase`}
                                        placeholder="Code promo"
                                        aria-label="Code promo"
                                        autoComplete="off"
                                    />
                                    <button
                                        type="submit"
                                        disabled={
                                            isCheckingPromo ||
                                            codeInput.trim() === ''
                                        }
                                        className="flex-shrink-0 rounded-xl border border-[#25D366] px-4 text-xs font-bold text-[#1DA851] transition-colors hover:bg-[#E8F5E9] disabled:opacity-50"
                                    >
                                        {isCheckingPromo ? '…' : 'Appliquer'}
                                    </button>
                                </div>
                                {promoError && (
                                    <p className="mt-1.5 text-[11px] text-red-600">
                                        {promoError}
                                    </p>
                                )}
                            </form>
                        )}

                        {/* Totaux */}
                        <div className="space-y-1.5 text-sm">
                            <div className="flex justify-between text-[#4A4A6A]">
                                <span>Sous-total</span>
                                <span>{formatPrice(subtotal)}</span>
                            </div>
                            {discount > 0 && (
                                <div className="flex justify-between font-semibold text-[#1DA851]">
                                    <span>Remise {promo?.code}</span>
                                    <span>− {formatPrice(discount)}</span>
                                </div>
                            )}
                            <div className="flex justify-between text-[#4A4A6A]">
                                <span>Livraison</span>
                                <span
                                    className={
                                        freeShipping
                                            ? 'font-semibold text-green-600'
                                            : ''
                                    }
                                >
                                    {freeShipping
                                        ? 'Gratuite'
                                        : formatPrice(shipping)}
                                </span>
                            </div>
                            <div className="mt-2 flex justify-between border-t border-[#E9ECEF] pt-2 text-base font-bold text-[#1A1A2E]">
                                <span>Total</span>
                                <span>{formatPrice(total)}</span>
                            </div>
                        </div>

                        {mode === 'order' ? (
                            <form onSubmit={handleSubmit} className="space-y-3">
                                <div>
                                    <label
                                        htmlFor="cart-name"
                                        className={LABEL_CLASS}
                                    >
                                        Nom complet *
                                    </label>
                                    <input
                                        id="cart-name"
                                        name="customer_name"
                                        required
                                        value={details.customer_name}
                                        onChange={handleChange}
                                        className={FIELD_CLASS}
                                        placeholder="Jean Mbarga"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="cart-phone"
                                        className={LABEL_CLASS}
                                    >
                                        Téléphone *
                                    </label>
                                    <input
                                        id="cart-phone"
                                        name="customer_phone"
                                        type="tel"
                                        required
                                        value={details.customer_phone}
                                        onChange={handleChange}
                                        className={FIELD_CLASS}
                                        placeholder="+237 6 00 00 00 00"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="cart-address"
                                        className={LABEL_CLASS}
                                    >
                                        Adresse de livraison
                                    </label>
                                    <input
                                        id="cart-address"
                                        name="customer_address"
                                        value={details.customer_address}
                                        onChange={handleChange}
                                        className={FIELD_CLASS}
                                        placeholder="Quartier, ville"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="cart-note"
                                        className={LABEL_CLASS}
                                    >
                                        Note (optionnel)
                                    </label>
                                    <textarea
                                        id="cart-note"
                                        name="note"
                                        rows={2}
                                        value={details.note}
                                        onChange={handleChange}
                                        className={`${FIELD_CLASS} resize-none`}
                                        placeholder="Précisions de livraison…"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    disabled={isPlacingOrder}
                                    className="flex w-full items-center justify-center gap-3 rounded-xl bg-[#25D366] py-4 text-base font-bold text-white shadow-lg transition-all hover:-translate-y-0.5 hover:bg-[#20BB5A] hover:shadow-xl disabled:opacity-60"
                                >
                                    <WhatsAppIcon className="h-6 w-6 fill-white" />
                                    {isPlacingOrder
                                        ? 'Enregistrement…'
                                        : 'Confirmer via WhatsApp'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setMode('cart')}
                                    className="w-full text-xs text-[#4A4A6A] underline underline-offset-2"
                                >
                                    Revenir au panier
                                </button>
                            </form>
                        ) : mode === 'quote' ? (
                            <form
                                onSubmit={handleQuoteSubmit}
                                className="space-y-3"
                            >
                                <p className="rounded-xl border border-[#E9ECEF] bg-[#F8F9FA] px-3 py-2 text-[11px] text-[#4A4A6A]">
                                    Le devis est un document PDF daté, valable{' '}
                                    30 jours. Il n’engage pas la commande : rien
                                    n’est réservé ni facturé.
                                </p>
                                <div>
                                    <label
                                        htmlFor="quote-name"
                                        className={LABEL_CLASS}
                                    >
                                        Nom complet *
                                    </label>
                                    <input
                                        id="quote-name"
                                        name="customer_name"
                                        required
                                        value={quoteDetails.customer_name}
                                        onChange={handleQuoteChange}
                                        className={FIELD_CLASS}
                                        placeholder="Jean Mbarga"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="quote-company"
                                        className={LABEL_CLASS}
                                    >
                                        Société / chantier
                                    </label>
                                    <input
                                        id="quote-company"
                                        name="customer_company"
                                        value={quoteDetails.customer_company}
                                        onChange={handleQuoteChange}
                                        className={FIELD_CLASS}
                                        placeholder="BTP Central Sarl"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="quote-phone"
                                        className={LABEL_CLASS}
                                    >
                                        Téléphone *
                                    </label>
                                    <input
                                        id="quote-phone"
                                        name="customer_phone"
                                        type="tel"
                                        required
                                        value={quoteDetails.customer_phone}
                                        onChange={handleQuoteChange}
                                        className={FIELD_CLASS}
                                        placeholder="+237 6 00 00 00 00"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="quote-email"
                                        className={LABEL_CLASS}
                                    >
                                        Email
                                    </label>
                                    <input
                                        id="quote-email"
                                        name="customer_email"
                                        type="email"
                                        value={quoteDetails.customer_email}
                                        onChange={handleQuoteChange}
                                        className={FIELD_CLASS}
                                        placeholder="jean@example.com"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="quote-address"
                                        className={LABEL_CLASS}
                                    >
                                        Adresse
                                    </label>
                                    <input
                                        id="quote-address"
                                        name="customer_address"
                                        value={quoteDetails.customer_address}
                                        onChange={handleQuoteChange}
                                        className={FIELD_CLASS}
                                        placeholder="Quartier, ville"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="quote-note"
                                        className={LABEL_CLASS}
                                    >
                                        Observations
                                    </label>
                                    <textarea
                                        id="quote-note"
                                        name="note"
                                        rows={2}
                                        value={quoteDetails.note}
                                        onChange={handleQuoteChange}
                                        className={`${FIELD_CLASS} resize-none`}
                                        placeholder="Références de l’appel d’offres, délais…"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    disabled={isRequestingQuote}
                                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#1A1A2E] py-4 text-base font-bold text-white shadow-lg transition-all hover:-translate-y-0.5 hover:shadow-xl disabled:opacity-60"
                                >
                                    <svg
                                        className="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        strokeWidth={2}
                                        aria-hidden="true"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M12 3v13m0 0l-4-4m4 4l4-4M4 21h16"
                                        />
                                    </svg>
                                    {isRequestingQuote
                                        ? 'Établissement…'
                                        : 'Télécharger le devis PDF'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setMode('cart')}
                                    className="w-full text-xs text-[#4A4A6A] underline underline-offset-2"
                                >
                                    Revenir au panier
                                </button>
                            </form>
                        ) : (
                            <div className="space-y-2">
                                <button
                                    onClick={() => setMode('order')}
                                    className="flex w-full items-center justify-center gap-3 rounded-xl bg-[#25D366] py-4 text-base font-bold text-white shadow-lg transition-all hover:-translate-y-0.5 hover:bg-[#20BB5A] hover:shadow-xl"
                                >
                                    <WhatsAppIcon className="h-6 w-6 fill-white" />
                                    Passer la commande
                                </button>
                                <button
                                    onClick={() => setMode('quote')}
                                    className="flex w-full items-center justify-center gap-2 rounded-xl border border-[#E9ECEF] bg-white py-3 text-sm font-semibold text-[#1A1A2E] transition-colors hover:border-[#25D366] hover:bg-[#F8F9FA]"
                                >
                                    <svg
                                        className="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        strokeWidth={2}
                                        aria-hidden="true"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                    Demander un devis PDF
                                </button>
                            </div>
                        )}

                        <p className="flex items-center justify-center gap-1.5 text-center text-[10px] text-[#4A4A6A]">
                            <svg
                                className="h-3.5 w-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                />
                            </svg>
                            Commande enregistrée · Confirmation instantanée
                        </p>
                    </div>
                )}
            </aside>
        </>
    );
}
