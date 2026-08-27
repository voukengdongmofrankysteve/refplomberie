import type { ReactNode } from 'react';

const FEATURES: { icon: ReactNode; title: string; desc: string }[] = [
    {
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
                    d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"
                />
            </svg>
        ),
        title: "10 ans d'expertise",
        desc: 'Une décennie au service des professionnels et particuliers. Nos équipes sont certifiées et expérimentées.',
    },
    {
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
        desc: 'Stock permanent de 2 500 références. Expédition rapide partout au Cameroun.',
    },
    {
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
        title: 'Commande WhatsApp',
        desc: 'Ajoutez au panier, puis confirmez directement via WhatsApp. Simple, rapide, humain.',
    },
    {
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
                    d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"
                />
            </svg>
        ),
        title: 'Conseil technique',
        desc: 'Nos techniciens experts répondent à vos questions techniques avant et après votre achat.',
    },
    {
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
                    d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"
                />
            </svg>
        ),
        title: 'Prix compétitifs',
        desc: 'Tarifs pros accessibles aux particuliers. Devis personnalisé pour les chantiers importants.',
    },
    {
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
                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                />
            </svg>
        ),
        title: 'Garantie 2 ans min.',
        desc: 'Tous nos produits sont garantis minimum 2 ans. Retours acceptés sous 30 jours.',
    },
];

export default function WhyUs() {
    return (
        <section
            id="pourquoi"
            className="relative overflow-hidden bg-[#1A1A2E] py-16 md:py-24"
        >
            <div
                className="absolute top-0 right-0 h-96 w-96 translate-x-1/2 -translate-y-1/2 rounded-full bg-[#25D366]/5"
                aria-hidden="true"
            />
            <div
                className="absolute bottom-0 left-0 h-64 w-64 -translate-x-1/2 translate-y-1/2 rounded-full bg-[#25D366]/5"
                aria-hidden="true"
            />

            <div className="relative mx-auto max-w-7xl px-4 md:px-8">
                <div className="mb-14 text-center">
                    <p className="mb-3 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                        Pourquoi nous choisir
                    </p>
                    <h2 className="font-display text-3xl font-bold text-white md:text-4xl">
                        La plomberie made simple
                    </h2>
                    <p className="mx-auto mt-4 max-w-md text-[#ADB5BD]">
                        De la commande en ligne à la livraison, nous avons
                        simplifié chaque étape pour vous.
                    </p>
                </div>

                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {FEATURES.map((f) => (
                        <div
                            key={f.title}
                            className="group rounded-2xl border border-white/10 bg-white/5 p-6 transition-all duration-300 hover:border-[#25D366]/30 hover:bg-[#25D366]/10"
                        >
                            <div className="mb-4 w-fit text-[#25D366] transition-transform group-hover:scale-110">
                                {f.icon}
                            </div>
                            <h3 className="mb-2 font-display text-lg font-bold text-white transition-colors group-hover:text-[#25D366]">
                                {f.title}
                            </h3>
                            <p className="text-sm leading-relaxed text-[#ADB5BD]">
                                {f.desc}
                            </p>
                        </div>
                    ))}
                </div>

                {/* Bannière d'appel à l'action */}
                <div className="mt-14 flex flex-col items-center justify-between gap-6 rounded-2xl bg-[#25D366] p-8 md:flex-row md:p-10">
                    <div>
                        <h3 className="font-display text-2xl font-bold text-[#1A1A2E] md:text-3xl">
                            Prêt à commander ?
                        </h3>
                        <p className="mt-1 text-sm text-[#1A1A2E]/70">
                            Parcourez notre catalogue et confirmez votre
                            commande via WhatsApp.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <a
                            href="/#produits"
                            className="rounded-xl bg-[#1A1A2E] px-8 py-3.5 text-sm font-bold whitespace-nowrap text-white transition-colors hover:bg-[#2d2d4e]"
                        >
                            Voir le catalogue
                        </a>
                        <a
                            href="/#techniciens"
                            className="rounded-xl border border-[#1A1A2E]/10 bg-white px-8 py-3.5 text-sm font-bold whitespace-nowrap text-[#1A1A2E] transition-colors hover:bg-gray-50"
                        >
                            Trouver un technicien
                        </a>
                    </div>
                </div>
            </div>
        </section>
    );
}
