import type { ReactNode } from 'react';
import { useStoreInfo } from '@/hooks/use-store-info';
import type { StoreInfo } from '@/types/shop';

const ICONS: Record<string, ReactNode> = {
    address: (
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
                d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"
            />
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"
            />
        </svg>
    ),
    phone: (
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
                d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"
            />
        </svg>
    ),
    email: (
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
                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
            />
        </svg>
    ),
    hours: (
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
                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
            />
        </svg>
    ),
};

function StoreDetails({ store }: { store: StoreInfo }) {
    const rows = [
        { key: 'address', label: 'Adresse', value: store.address },
        { key: 'phone', label: 'Téléphone', value: store.phone },
        { key: 'email', label: 'Email', value: store.email },
        { key: 'hours', label: 'Horaires', value: store.hours },
    ];

    return (
        <div className="flex flex-col gap-4">
            {rows.map((row) => (
                <div key={row.key} className="flex items-start gap-3">
                    <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[#E8F5E9] text-[#25D366]">
                        {ICONS[row.key]}
                    </div>
                    <div>
                        <p className="text-[10px] font-bold tracking-wider text-[#4A4A6A] uppercase">
                            {row.label}
                        </p>
                        <p className="mt-0.5 text-sm font-medium text-[#1A1A2E]">
                            {row.value}
                        </p>
                    </div>
                </div>
            ))}

            <a
                href={store.mapLinkUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-1 inline-flex items-center gap-2 text-sm font-bold text-[#25D366] transition-colors hover:text-[#1DA851]"
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
                        d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"
                    />
                </svg>
                Ouvrir dans Google Maps
            </a>
        </div>
    );
}

/** Section complète — page d'accueil. */
export function StoreMapSection() {
    const store = useStoreInfo();

    return (
        <section id="localisation" className="bg-[#F8F9FA] py-16 md:py-24">
            <div className="mx-auto max-w-7xl px-4 md:px-8">
                <div className="mb-12 text-center">
                    <p className="mb-3 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                        Notre magasin
                    </p>
                    <h2 className="font-display text-3xl font-bold text-[#1A1A2E] md:text-4xl">
                        Retrouvez-nous en magasin
                    </h2>
                    <p className="mx-auto mt-4 max-w-md text-sm leading-relaxed text-[#4A4A6A]">
                        Venez découvrir nos produits en showroom, obtenir un
                        conseil technique ou récupérer votre commande
                        directement sur place.
                    </p>
                </div>

                <div className="mx-auto max-w-4xl overflow-hidden rounded-2xl border border-[#E9ECEF] bg-white shadow-sm">
                    <div className="grid md:grid-cols-2">
                        <div className="relative h-72 bg-[#E8F5E9] md:h-auto">
                            <iframe
                                title="Carte Réf. Plomberie"
                                src={store.mapEmbedUrl}
                                width="100%"
                                height="100%"
                                style={{ border: 0 }}
                                allowFullScreen
                                loading="lazy"
                                referrerPolicy="no-referrer-when-downgrade"
                                className="absolute inset-0 h-full w-full"
                            />
                        </div>

                        <div className="flex flex-col justify-center gap-6 p-6 md:p-8">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#25D366]">
                                    <svg
                                        className="h-5 w-5 text-white"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        strokeWidth={2}
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="font-display text-lg leading-snug font-bold text-[#1A1A2E]">
                                        {store.name}
                                    </h3>
                                    <span className="mt-0.5 inline-flex items-center gap-1 text-xs font-semibold text-green-600">
                                        <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-green-500" />
                                        Ouvert maintenant
                                    </span>
                                </div>
                            </div>

                            <StoreDetails store={store} />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

/** Carte compacte — page détail produit. */
export function StoreMapCompact() {
    const store = useStoreInfo();

    return (
        <div className="mt-8 overflow-hidden rounded-2xl border border-[#E9ECEF] bg-white shadow-sm">
            <div className="flex items-center gap-3 border-b border-[#E9ECEF] px-5 py-4">
                <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-[#25D366]">
                    <svg
                        className="h-5 w-5 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2}
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"
                        />
                    </svg>
                </div>
                <div>
                    <h3 className="font-display font-bold text-[#1A1A2E]">
                        Disponible en magasin
                    </h3>
                    <p className="text-xs text-[#4A4A6A]">
                        Venez voir ce produit en showroom
                    </p>
                </div>
            </div>

            <div className="grid md:grid-cols-2">
                <div className="relative h-56 bg-[#E8F5E9]">
                    <iframe
                        title="Carte magasin"
                        src={store.mapEmbedUrl}
                        width="100%"
                        height="100%"
                        style={{ border: 0 }}
                        allowFullScreen
                        loading="lazy"
                        referrerPolicy="no-referrer-when-downgrade"
                        className="absolute inset-0 h-full w-full"
                    />
                </div>

                <div className="flex flex-col justify-center p-5">
                    <p className="mb-4 font-display text-sm font-bold text-[#1A1A2E]">
                        {store.name}
                    </p>
                    <StoreDetails store={store} />
                </div>
            </div>
        </div>
    );
}
