import { Link, router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import NotificationBell from '@/components/notification-bell';
import SearchCommand from '@/components/shop/search-command';
import { useShopAuth } from '@/contexts/auth-modal-context';
import { useCart } from '@/contexts/cart-context';
import { useFavorites } from '@/contexts/favorites-context';
import { dashboard, home } from '@/routes';
import { logout } from '@/routes';

const LINKS = [
    { label: 'Produits', href: '/#produits' },
    { label: 'Techniciens', href: '/#techniciens' },
    { label: 'Services', href: '/#services' },
    { label: 'Localisation', href: '/#localisation' },
    { label: 'FAQ', href: '/#faq' },
    { label: 'Contact', href: '/#contact' },
];

export default function NavBar() {
    const [scrolled, setScrolled] = useState(false);
    const [open, setOpen] = useState(false);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const [searchOpen, setSearchOpen] = useState(false);
    const { totalItems, setIsOpen } = useCart();
    const { favorites, setIsOpen: openFavorites } = useFavorites();
    const { user, setAuthModal } = useShopAuth();
    const userMenuRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 12);
        window.addEventListener('scroll', onScroll);

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            if (
                userMenuRef.current &&
                !userMenuRef.current.contains(e.target as Node)
            ) {
                setUserMenuOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);

        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Ctrl/⌘ + K ouvre la recherche depuis n'importe où sur la vitrine.
    useEffect(() => {
        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key.toLowerCase() === 'k' && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                setSearchOpen(true);
            }
        };
        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    const closeSearch = useCallback(() => setSearchOpen(false), []);

    const handleLogout = () => router.post(logout.url());

    return (
        <header
            className={`fixed inset-x-0 top-0 z-40 transition-all duration-300 ${
                scrolled ? 'bg-white/95 shadow-md backdrop-blur-md' : 'bg-white'
            }`}
        >
            {/* Barre supérieure */}
            <div className="hidden items-center justify-center gap-6 bg-[#1A1A2E] px-6 py-2 text-xs text-white md:flex">
                <span className="flex items-center gap-1.5">
                    <svg
                        className="h-3.5 w-3.5 text-[#25D366]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2}
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"
                        />
                    </svg>
                    Livraison rapide au Cameroun
                </span>
                <span className="h-3 w-px bg-white/20" />
                <span className="flex items-center gap-1.5">
                    <svg
                        className="h-3.5 w-3.5 text-[#25D366]"
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
                    +237 677 259 585
                </span>
                <span className="h-3 w-px bg-white/20" />
                <span className="flex items-center gap-1.5">
                    <svg
                        className="h-3.5 w-3.5 text-[#25D366]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2}
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    Matériaux plomberie pro
                </span>
            </div>

            <div className="mx-auto max-w-7xl px-4 md:px-8">
                <div className="flex h-16 items-center justify-between md:h-20">
                    {/* Logo */}
                    <Link
                        href={home()}
                        className="group flex items-center gap-2"
                    >
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#25D366] shadow-sm transition-transform group-hover:scale-105">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                className="h-6 w-6"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"
                                    fill="#1A1A2E"
                                />
                                <path
                                    d="M9 6h2v2H9V6zm4 0h2v2h-2V6z"
                                    fill="#1A1A2E"
                                    opacity="0.4"
                                />
                            </svg>
                        </div>
                        <div>
                            <span className="block font-display text-lg leading-none font-bold text-[#1A1A2E]">
                                Réf.
                                <span className="text-[#25D366]">
                                    Plomberie
                                </span>
                            </span>
                            <span className="hidden text-[10px] tracking-wide text-[#4A4A6A] sm:block">
                                Matériaux &amp; Équipements
                            </span>
                        </div>
                    </Link>

                    {/* Navigation desktop */}
                    <nav className="hidden items-center gap-8 md:flex">
                        {LINKS.map((l) => (
                            <a
                                key={l.label}
                                href={l.href}
                                className="group relative text-sm font-medium text-[#4A4A6A] transition-colors hover:text-[#25D366]"
                            >
                                {l.label}
                                <span className="absolute -bottom-0.5 left-0 h-0.5 w-0 bg-[#25D366] transition-all group-hover:w-full" />
                            </a>
                        ))}
                    </nav>

                    {/* Actions */}
                    <div className="flex items-center gap-2">
                        {/* Recherche */}
                        <button
                            onClick={() => setSearchOpen(true)}
                            className="flex h-10 w-10 items-center justify-center rounded-xl text-[#4A4A6A] transition-colors hover:bg-gray-100 hover:text-[#1A1A2E] lg:hidden"
                            aria-label="Rechercher un produit"
                        >
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                                aria-hidden="true"
                            >
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                        </button>

                        {/* Recherche — version large, qui annonce le raccourci */}
                        <button
                            onClick={() => setSearchOpen(true)}
                            className="hidden items-center gap-2 rounded-xl border border-[#E9ECEF] bg-[#F8F9FA] py-2 pr-2 pl-3 text-sm text-[#4A4A6A] transition-colors hover:border-[#25D366] hover:bg-white lg:flex"
                            aria-label="Rechercher un produit"
                        >
                            <svg
                                className="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                                aria-hidden="true"
                            >
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                            Rechercher
                            <kbd className="rounded border border-[#E9ECEF] bg-white px-1.5 py-0.5 font-sans text-[10px] text-[#4A4A6A]">
                                Ctrl K
                            </kbd>
                        </button>

                        {/* Notifications : réservées aux clients connectés,
                            puisque le journal est rattaché à un compte. */}
                        {user && <NotificationBell />}

                        {/* Favoris */}
                        <button
                            onClick={() => openFavorites(true)}
                            className="relative hidden h-10 w-10 items-center justify-center rounded-xl text-[#4A4A6A] transition-colors hover:bg-gray-100 hover:text-[#1A1A2E] md:flex"
                            aria-label={`Favoris, ${favorites.length} produit${favorites.length !== 1 ? 's' : ''}`}
                        >
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                                />
                            </svg>
                            {favorites.length > 0 && (
                                <span className="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">
                                    {favorites.length}
                                </span>
                            )}
                        </button>

                        {/* Menu utilisateur */}
                        <div
                            className="relative hidden md:block"
                            ref={userMenuRef}
                        >
                            {user ? (
                                <>
                                    <button
                                        onClick={() =>
                                            setUserMenuOpen((v) => !v)
                                        }
                                        className="flex items-center gap-2 rounded-xl px-3 py-2 transition-colors hover:bg-gray-100"
                                        aria-haspopup="true"
                                        aria-expanded={userMenuOpen}
                                    >
                                        <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-[#25D366] text-sm font-bold text-[#1A1A2E]">
                                            {user.name.charAt(0).toUpperCase()}
                                        </div>
                                        <span className="max-w-[80px] truncate text-sm font-medium text-[#1A1A2E]">
                                            {user.name}
                                        </span>
                                        <svg
                                            className={`h-4 w-4 text-[#4A4A6A] transition-transform ${userMenuOpen ? 'rotate-180' : ''}`}
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </button>
                                    {userMenuOpen && (
                                        <div className="absolute top-full right-0 z-50 mt-2 w-48 rounded-xl border border-[#E9ECEF] bg-white py-1 shadow-xl">
                                            <div className="border-b border-[#E9ECEF] px-4 py-3">
                                                <p className="text-sm font-semibold text-[#1A1A2E]">
                                                    {user.name}
                                                </p>
                                                <p className="truncate text-xs text-[#4A4A6A]">
                                                    {user.email}
                                                </p>
                                            </div>
                                            <Link
                                                href={dashboard()}
                                                onClick={() =>
                                                    setUserMenuOpen(false)
                                                }
                                                className="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-[#4A4A6A] transition-colors hover:bg-[#F8F9FA] hover:text-[#1A1A2E]"
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
                                                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                                                    />
                                                </svg>
                                                Mon compte
                                            </Link>
                                            <button
                                                onClick={() => {
                                                    openFavorites(true);
                                                    setUserMenuOpen(false);
                                                }}
                                                className="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-[#4A4A6A] transition-colors hover:bg-[#F8F9FA] hover:text-[#1A1A2E]"
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
                                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                                                    />
                                                </svg>
                                                Mes favoris ({favorites.length})
                                            </button>
                                            <button
                                                onClick={() => {
                                                    handleLogout();
                                                    setUserMenuOpen(false);
                                                }}
                                                className="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-500 transition-colors hover:bg-red-50"
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
                                                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                                                    />
                                                </svg>
                                                Se déconnecter
                                            </button>
                                        </div>
                                    )}
                                </>
                            ) : (
                                <button
                                    onClick={() => setAuthModal('login')}
                                    className="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-[#4A4A6A] transition-colors hover:bg-gray-100 hover:text-[#1A1A2E]"
                                >
                                    <svg
                                        className="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        strokeWidth={2}
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                                        />
                                    </svg>
                                    Connexion
                                </button>
                            )}
                        </div>

                        {/* Panier */}
                        <button
                            onClick={() => setIsOpen(true)}
                            className="relative flex items-center gap-2 rounded-xl bg-[#25D366] px-4 py-2.5 text-sm font-semibold text-[#1A1A2E] shadow-sm transition-colors hover:bg-[#1DA851] hover:shadow-md"
                            aria-label={`Panier, ${totalItems} article${totalItems !== 1 ? 's' : ''}`}
                        >
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                                aria-hidden="true"
                            >
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                                <line x1="3" y1="6" x2="21" y2="6" />
                                <path d="M16 10a4 4 0 0 1-8 0" />
                            </svg>
                            <span className="hidden sm:inline">Panier</span>
                            {totalItems > 0 && (
                                <span className="badge-pulse absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#1A1A2E] text-[10px] font-bold text-white">
                                    {totalItems}
                                </span>
                            )}
                        </button>

                        {/* Burger mobile */}
                        <button
                            onClick={() => setOpen((v) => !v)}
                            className="flex flex-col gap-1.5 p-2 md:hidden"
                            aria-label="Menu"
                            aria-expanded={open}
                        >
                            <span
                                className={`block h-0.5 w-6 bg-[#1A1A2E] transition-transform ${open ? 'translate-y-2 rotate-45' : ''}`}
                            />
                            <span
                                className={`block h-0.5 w-6 bg-[#1A1A2E] transition-opacity ${open ? 'opacity-0' : ''}`}
                            />
                            <span
                                className={`block h-0.5 w-6 bg-[#1A1A2E] transition-transform ${open ? '-translate-y-2 -rotate-45' : ''}`}
                            />
                        </button>
                    </div>
                </div>
            </div>

            {/* Menu mobile */}
            {open && (
                <div className="flex flex-col gap-4 border-t border-gray-100 bg-white px-4 py-5 shadow-lg md:hidden">
                    {LINKS.map((l) => (
                        <a
                            key={l.label}
                            href={l.href}
                            onClick={() => setOpen(false)}
                            className="border-b border-gray-100 py-1 text-base font-medium text-[#1A1A2E]"
                        >
                            {l.label}
                        </a>
                    ))}
                    <button
                        onClick={() => {
                            openFavorites(true);
                            setOpen(false);
                        }}
                        className="flex items-center gap-2 border-b border-gray-100 py-1 text-left text-base font-medium text-[#1A1A2E]"
                    >
                        <svg
                            className="h-4 w-4 text-red-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2}
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                            />
                        </svg>
                        Favoris{' '}
                        {favorites.length > 0 && `(${favorites.length})`}
                    </button>
                    {user ? (
                        <button
                            onClick={() => {
                                handleLogout();
                                setOpen(false);
                            }}
                            className="py-1 text-left text-base font-medium text-red-500"
                        >
                            Se déconnecter ({user.name})
                        </button>
                    ) : (
                        <button
                            onClick={() => {
                                setAuthModal('login');
                                setOpen(false);
                            }}
                            className="py-1 text-left text-base font-medium text-[#25D366]"
                        >
                            Se connecter
                        </button>
                    )}
                    <div className="flex items-center gap-2 pt-2 text-xs text-[#4A4A6A]">
                        <svg
                            className="h-3.5 w-3.5 flex-shrink-0 text-[#25D366]"
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
                        +237 677 259 585
                    </div>
                </div>
            )}

            {searchOpen && <SearchCommand onClose={closeSearch} />}
        </header>
    );
}
