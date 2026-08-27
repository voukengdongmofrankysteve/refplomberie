import WhatsAppIcon from '@/components/shop/whatsapp-icon';
import { useStoreInfo } from '@/hooks/use-store-info';
import { track } from '@/lib/track';

const PRODUCT_LINKS = [
    'Robinetterie',
    'Tuyauterie & Raccords',
    'Sanitaire',
    'Chauffe-eau',
    'Outils & Accessoires',
];

const SERVICE_LINKS = [
    'Installation',
    'Dépannage urgence',
    'Diagnostic gratuit',
    'Réserver un technicien',
    'Maintenance',
];

export default function Footer() {
    const store = useStoreInfo();

    return (
        <footer className="bg-[#1A1A2E] text-white/80">
            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:grid-cols-2 md:px-8 lg:grid-cols-4">
                {/* Marque */}
                <div className="lg:col-span-1">
                    <div className="mb-4 flex items-center gap-2">
                        <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-[#25D366]">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                className="h-5 w-5"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"
                                    fill="#1A1A2E"
                                />
                            </svg>
                        </div>
                        <span className="font-display text-lg font-bold text-white">
                            Réf.
                            <span className="text-[#25D366]">Plomberie</span>
                        </span>
                    </div>
                    <p className="mb-5 text-sm leading-relaxed">
                        Votre référence plomberie au Cameroun. Matériel
                        professionnel, livraison rapide, commande confirmée via
                        WhatsApp.
                    </p>
                    <a
                        href={`https://wa.me/${store.whatsapp}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        onClick={() =>
                            track('whatsapp_click', { label: 'Pied de page' })
                        }
                        className="inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#20BB5A]"
                    >
                        <WhatsAppIcon className="h-4 w-4 fill-white" />
                        Nous contacter
                    </a>
                </div>

                {/* Produits */}
                <div>
                    <p className="mb-5 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                        Produits
                    </p>
                    <ul className="space-y-3 text-sm">
                        {PRODUCT_LINKS.map((l) => (
                            <li key={l}>
                                <a
                                    href="/#produits"
                                    className="transition-colors hover:text-[#25D366]"
                                >
                                    {l}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>

                {/* Services */}
                <div>
                    <p className="mb-5 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                        Services
                    </p>
                    <ul className="space-y-3 text-sm">
                        {SERVICE_LINKS.map((l) => (
                            <li key={l}>
                                <a
                                    href="/#services"
                                    className="transition-colors hover:text-white"
                                >
                                    {l}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>

                {/* Informations */}
                <div>
                    <p className="mb-5 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                        Informations
                    </p>
                    <ul className="space-y-3 text-sm">
                        <li>
                            <a
                                href="/#contact"
                                className="transition-colors hover:text-white"
                            >
                                Contact
                            </a>
                        </li>
                        <li>
                            <a
                                href="/#localisation"
                                className="transition-colors hover:text-white"
                            >
                                Nos magasins
                            </a>
                        </li>
                    </ul>
                    <div className="mt-6 space-y-3 text-sm">
                        <div className="flex items-center gap-2.5">
                            <svg
                                className="h-4 w-4 flex-shrink-0 text-[#25D366]"
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
                            <span>{store.phone}</span>
                        </div>
                        <div className="flex items-center gap-2.5">
                            <svg
                                className="h-4 w-4 flex-shrink-0 text-[#25D366]"
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
                            <span>{store.email}</span>
                        </div>
                        <div className="flex items-center gap-2.5">
                            <svg
                                className="h-4 w-4 flex-shrink-0 text-[#25D366]"
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
                            <span>Yaoundé, Cameroun</span>
                        </div>
                        <div className="flex items-center gap-2.5">
                            <svg
                                className="h-4 w-4 flex-shrink-0 text-[#25D366]"
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
                            <span>Lun–Sam, 7h–18h</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Barre inférieure */}
            <div className="border-t border-white/10">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-5 text-xs text-white/40 md:px-8">
                    <p>
                        © {new Date().getFullYear()} Réf. Plomberie. Tous droits
                        réservés.
                    </p>
                    <div className="flex items-center gap-4">
                        <span className="flex items-center gap-1.5">
                            <span
                                className="h-2 w-2 rounded-full bg-[#25D366]"
                                aria-hidden="true"
                            />
                            Certifié Qualibat
                        </span>
                        <span className="flex items-center gap-1.5">
                            <span
                                className="h-2 w-2 rounded-full bg-[#25D366]"
                                aria-hidden="true"
                            />
                            Paiement sécurisé
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    );
}
