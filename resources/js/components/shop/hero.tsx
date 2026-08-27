import { useState } from 'react';
import type { ReactNode } from 'react';
import BookingModal from '@/components/shop/booking-modal';

const STATS: { value: string; label: string; icon: ReactNode }[] = [
    {
        value: '2 500+',
        label: 'Produits en stock',
        icon: (
            <svg
                className="h-5 w-5 text-[#25D366]"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={1.8}
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
                />
            </svg>
        ),
    },
    {
        value: '48h',
        label: 'Délai de livraison',
        icon: (
            <svg
                className="h-5 w-5 text-[#25D366]"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={1.8}
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"
                />
            </svg>
        ),
    },
    {
        value: '10 ans',
        label: "D'expertise",
        icon: (
            <svg
                className="h-5 w-5 text-[#25D366]"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={1.8}
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"
                />
            </svg>
        ),
    },
];

const CERTIFICATIONS = [
    'MINEE Certifié',
    'ISO 9001',
    'NF Sanitaire',
    'Qualiplombier',
    'RGE',
];

export default function Hero({ services }: { services: string[] }) {
    const [bookingOpen, setBookingOpen] = useState(false);

    return (
        <section
            id="top"
            className="relative overflow-hidden bg-white pt-28 pb-16 md:pt-36 md:pb-24"
        >
            {/* Décorations de fond */}
            <div
                className="clip-hero absolute top-0 right-0 -z-10 h-full w-1/2 bg-[#E8F5E9]"
                aria-hidden="true"
            />
            <div
                className="absolute top-20 right-20 -z-10 h-72 w-72 rounded-full bg-[#25D366]/10 blur-3xl"
                aria-hidden="true"
            />

            <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 md:grid-cols-2 md:px-8">
                {/* Colonne gauche */}
                <div>
                    <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-[#25D366]/30 bg-[#E8F5E9] px-4 py-2 text-xs font-semibold tracking-wider text-[#1DA851] uppercase">
                        <span
                            className="h-2 w-2 animate-pulse rounded-full bg-[#25D366]"
                            aria-hidden="true"
                        />
                        Livraison rapide partout au Cameroun
                    </div>

                    <h1 className="font-display text-4xl leading-tight font-bold text-[#1A1A2E] md:text-5xl lg:text-6xl">
                        Votre référence
                        <br />
                        <span className="text-[#25D366]">plomberie</span>
                        <br />
                        au Cameroun
                    </h1>

                    <p className="mt-6 max-w-md text-lg leading-relaxed text-[#4A4A6A]">
                        Robinetterie, tuyauterie, sanitaire et outillage pro.
                        Commandez en ligne, confirmez via WhatsApp — livré chez
                        vous rapidement.
                    </p>

                    <div className="mt-8 flex flex-wrap gap-4">
                        <a
                            href="/#produits"
                            className="inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-8 py-4 text-base font-bold text-[#1A1A2E] shadow-lg transition-all hover:-translate-y-0.5 hover:bg-[#1DA851] hover:shadow-xl"
                        >
                            Voir les produits
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2.5}
                                aria-hidden="true"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6"
                                />
                            </svg>
                        </a>
                        <button
                            onClick={() => setBookingOpen(true)}
                            className="inline-flex items-center gap-2 rounded-xl border-2 border-[#1A1A2E] px-8 py-4 text-base font-bold text-[#1A1A2E] transition-all hover:bg-[#1A1A2E] hover:text-white"
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
                                    d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"
                                />
                            </svg>
                            Trouver un technicien
                        </button>
                    </div>

                    {/* Statistiques */}
                    <div className="mt-10 flex flex-wrap gap-8">
                        {STATS.map((s) => (
                            <div
                                key={s.label}
                                className="flex items-center gap-3"
                            >
                                <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#E8F5E9]">
                                    {s.icon}
                                </div>
                                <div>
                                    <p className="font-display text-2xl leading-none font-bold text-[#1A1A2E]">
                                        {s.value}
                                    </p>
                                    <p className="mt-0.5 text-xs text-[#4A4A6A]">
                                        {s.label}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Colonne droite — visuel */}
                <div className="relative flex justify-center">
                    <div className="relative w-full max-w-sm">
                        <div className="aspect-[4/5] overflow-hidden rounded-2xl shadow-2xl">
                            <img
                                src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?q=80&w=800&auto=format&fit=crop"
                                alt="Technicien plombier professionnel au travail"
                                className="h-full w-full object-cover"
                            />
                            <div className="absolute right-4 bottom-4 left-4 rounded-xl bg-white/95 p-4 shadow-lg backdrop-blur-sm">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-[#25D366]">
                                        <svg
                                            className="h-5 w-5 text-[#1A1A2E]"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                            aria-hidden="true"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M20 12V22H4V12"
                                            />
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M22 7H2v5h20V7z"
                                            />
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M12 22V7M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7z"
                                            />
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <p className="text-sm font-bold text-[#1A1A2E]">
                                            Commande confirmée
                                        </p>
                                        <p className="text-xs text-[#4A4A6A]">
                                            via WhatsApp en 2 clics
                                        </p>
                                    </div>
                                    <div className="ml-auto flex items-center gap-1">
                                        <div
                                            className="h-2 w-2 animate-pulse rounded-full bg-green-500"
                                            aria-hidden="true"
                                        />
                                        <span className="text-xs font-semibold text-green-600">
                                            En ligne
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="absolute -top-4 -right-4 rotate-3 rounded-2xl bg-[#25D366] p-4 shadow-xl">
                            <p className="font-display text-2xl leading-none font-bold text-[#1A1A2E]">
                                -20%
                            </p>
                            <p className="text-xs font-medium text-[#1A1A2E]">
                                1ère commande
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Labels de confiance */}
            <div className="mx-auto mt-14 max-w-7xl px-4 md:px-8">
                <p className="mb-5 text-center text-xs font-semibold tracking-wider text-[#4A4A6A] uppercase">
                    Certifié &amp; reconnu au Cameroun
                </p>
                <div className="flex flex-wrap justify-center gap-6 md:gap-10">
                    {CERTIFICATIONS.map((b) => (
                        <div
                            key={b}
                            className="rounded-lg border border-[#E9ECEF] bg-[#F8F9FA] px-5 py-2.5 text-xs font-bold text-[#4A4A6A] transition-colors hover:border-[#25D366]"
                        >
                            {b}
                        </div>
                    ))}
                </div>
            </div>

            {bookingOpen && (
                <BookingModal
                    services={services}
                    onClose={() => setBookingOpen(false)}
                />
            )}
        </section>
    );
}
