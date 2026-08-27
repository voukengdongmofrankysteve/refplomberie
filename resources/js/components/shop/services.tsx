import type { ReactNode } from 'react';

const SERVICES: {
    icon: ReactNode;
    title: string;
    desc: string;
    tag: string;
}[] = [
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
        title: 'Installation Plomberie',
        desc: 'Pose complète de salles de bains, cuisines, systèmes de chauffage. Nos artisans certifiés interviennent rapidement.',
        tag: 'Installation',
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
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"
                />
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M12 15.75h.007v.008H12v-.008z"
                />
            </svg>
        ),
        title: 'Dépannage Urgence 24/7',
        desc: "Fuite d'eau, canalisation bouchée, pression insuffisante. Intervention sous 2h partout dans la zone.",
        tag: 'Urgence',
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
                    d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"
                />
            </svg>
        ),
        title: 'Diagnostic & Devis',
        desc: 'Bilan complet de votre installation, détection de fuites non apparentes, devis gratuit et sans engagement.',
        tag: 'Diagnostic',
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
        title: 'Entretien & Maintenance',
        desc: 'Contrats de maintenance annuels, entretien chauffe-eau, adoucisseurs, robinetterie. Garantissez la durabilité.',
        tag: 'Maintenance',
    },
];

export default function Services() {
    return (
        <section id="services" className="bg-white py-16 md:py-24">
            <div className="mx-auto max-w-7xl px-4 md:px-8">
                {/* En-tête */}
                <div className="mb-14 grid items-end gap-8 md:grid-cols-2">
                    <div>
                        <p className="mb-3 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                            Nos services
                        </p>
                        <h2 className="font-display text-3xl leading-snug font-bold text-[#1A1A2E] md:text-4xl">
                            Tout pour votre
                            <br />
                            <span className="text-[#25D366]">plomberie</span>
                        </h2>
                    </div>
                    <p className="leading-relaxed text-[#4A4A6A]">
                        De la vente de matériel professionnel à
                        l&apos;intervention sur site, Réf. Plomberie vous
                        accompagne à chaque étape de vos projets.
                    </p>
                </div>

                {/* Cartes */}
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {SERVICES.map((s) => (
                        <div
                            key={s.title}
                            className="group cursor-default rounded-2xl border border-[#E9ECEF] bg-[#F8F9FA] p-6 transition-all duration-300 hover:border-[#25D366] hover:bg-[#25D366]"
                        >
                            <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-[#E8F5E9] text-[#25D366] transition-colors group-hover:bg-white/30 group-hover:text-[#1A1A2E]">
                                {s.icon}
                            </div>
                            <span className="text-[10px] font-bold tracking-widest text-[#25D366] uppercase group-hover:text-[#1A1A2E]/60">
                                {s.tag}
                            </span>
                            <h3 className="mt-2 mb-3 font-display text-lg leading-snug font-bold text-[#1A1A2E]">
                                {s.title}
                            </h3>
                            <p className="text-sm leading-relaxed text-[#4A4A6A] transition-colors group-hover:text-[#1A1A2E]/80">
                                {s.desc}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
