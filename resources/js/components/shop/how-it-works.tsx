import type { ReactNode } from 'react';
import WhatsAppIcon from '@/components/shop/whatsapp-icon';

const STEPS: { n: string; icon: ReactNode; title: string; desc: string }[] = [
    {
        n: '01',
        icon: (
            <svg
                className="h-7 w-7"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={1.8}
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                />
            </svg>
        ),
        title: 'Parcourez le catalogue',
        desc: 'Naviguez parmi nos 2 500+ produits de plomberie professionnelle. Filtrez par catégorie ou recherchez directement.',
    },
    {
        n: '02',
        icon: (
            <svg
                className="h-7 w-7"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={1.8}
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"
                />
            </svg>
        ),
        title: 'Ajoutez au panier',
        desc: 'Sélectionnez vos produits et quantités. Votre panier se construit en temps réel avec le total affiché.',
    },
    {
        n: '03',
        icon: (
            <svg
                className="h-7 w-7"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={1.8}
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"
                />
            </svg>
        ),
        title: 'Confirmez sur WhatsApp',
        desc: 'Un clic sur « Confirmer via WhatsApp » envoie votre commande complète. Notre équipe valide sous quelques minutes.',
    },
    {
        n: '04',
        icon: (
            <svg
                className="h-7 w-7"
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
        title: 'Livraison rapide',
        desc: 'Votre commande est préparée et expédiée rapidement. Livraison à domicile partout au Cameroun.',
    },
];

export default function HowItWorks() {
    return (
        <section className="bg-white py-16 md:py-24">
            <div className="mx-auto max-w-7xl px-4 md:px-8">
                <div className="mb-14 text-center">
                    <p className="mb-3 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                        Comment ça marche
                    </p>
                    <h2 className="font-display text-3xl font-bold text-[#1A1A2E] md:text-4xl">
                        Commander en 4 étapes simples
                    </h2>
                </div>

                <div className="relative grid gap-6 md:grid-cols-4">
                    {/* Ligne de liaison */}
                    <div
                        className="absolute top-12 right-[12.5%] left-[12.5%] hidden h-0.5 bg-[#E9ECEF] md:block"
                        aria-hidden="true"
                    >
                        <div className="absolute inset-0 w-3/4 bg-gradient-to-r from-[#25D366] to-[#25D366]/20" />
                    </div>

                    {STEPS.map((s) => (
                        <div
                            key={s.n}
                            className="relative flex flex-col items-center text-center md:items-start md:text-left"
                        >
                            <div className="relative z-10 mx-auto mb-5 flex h-20 w-20 flex-col items-center justify-center rounded-2xl border-2 border-[#25D366]/30 bg-[#E8F5E9] text-[#25D366] md:mx-0">
                                {s.icon}
                                <span className="mt-0.5 text-[10px] font-bold text-[#25D366]">
                                    {s.n}
                                </span>
                            </div>
                            <h3 className="mb-2 font-display text-lg font-bold text-[#1A1A2E]">
                                {s.title}
                            </h3>
                            <p className="text-sm leading-relaxed text-[#4A4A6A]">
                                {s.desc}
                            </p>
                        </div>
                    ))}
                </div>

                {/* Encart WhatsApp */}
                <div className="mt-12 flex flex-col items-center gap-6 rounded-2xl border border-[#25D366]/30 bg-[#E8F5E9] p-6 md:flex-row md:p-8">
                    <div className="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-[#25D366]">
                        <WhatsAppIcon className="h-8 w-8 fill-white" />
                    </div>
                    <div className="flex-1 text-center md:text-left">
                        <h3 className="font-display text-lg font-bold text-[#1A1A2E]">
                            Confirmation WhatsApp instantanée
                        </h3>
                        <p className="mt-1 text-sm text-[#4A4A6A]">
                            Votre panier est automatiquement formaté en message
                            WhatsApp avec le détail de chaque article, les
                            quantités et le total. Vous échangez directement
                            avec notre équipe.
                        </p>
                    </div>
                    <a
                        href="/#produits"
                        className="flex-shrink-0 rounded-xl bg-[#25D366] px-6 py-3 text-sm font-bold whitespace-nowrap text-white transition-colors hover:bg-[#20BB5A]"
                    >
                        Essayer maintenant
                    </a>
                </div>
            </div>
        </section>
    );
}
