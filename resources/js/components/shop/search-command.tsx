import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { formatPrice, productUrl } from '@/lib/shop';
import { search as searchRoute } from '@/routes';
import type { SearchResults } from '@/types/shop';

const EMPTY: SearchResults = { products: [], categories: [] };

/** Laisse la frappe se poser avant d'interroger le serveur. */
const DEBOUNCE_MS = 180;

type SearchCommandProps = {
    onClose: () => void;
};

/**
 * Recherche instantanée, ouverte depuis n'importe quelle page.
 *
 * Le catalogue n'est chargé en entier que sur l'accueil : la liste vient donc
 * du serveur, ce qui la rend aussi disponible depuis une fiche produit et
 * évite d'embarquer tout le catalogue dans le bundle.
 */
export default function SearchCommand({ onClose }: SearchCommandProps) {
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<SearchResults>(EMPTY);
    const [loading, setLoading] = useState(false);
    const [highlight, setHighlight] = useState(0);
    const inputRef = useRef<HTMLInputElement | null>(null);

    const trimmed = term.trim();
    const hits = results.products;

    const go = useCallback(
        (href: string) => {
            onClose();
            router.visit(href);
        },
        [onClose],
    );

    useEffect(() => {
        inputRef.current?.focus();
    }, []);

    useEffect(() => {
        if (trimmed.length < 2) {
            return;
        }

        const controller = new AbortController();

        const timer = window.setTimeout(() => {
            setLoading(true);

            fetch(searchRoute.url({ query: { q: trimmed } }), {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })
                .then((response) => response.json() as Promise<SearchResults>)
                .then((data) => {
                    setResults(data);
                    setHighlight(0);
                })
                .catch(() => {
                    // Requête annulée par la frappe suivante : rien à signaler.
                })
                .finally(() => setLoading(false));
        }, DEBOUNCE_MS);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [trimmed]);

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Escape') {
            onClose();

            return;
        }

        if (hits.length === 0) {
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlight((i) => (i + 1) % hits.length);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlight((i) => (i - 1 + hits.length) % hits.length);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            go(productUrl(hits[highlight].slug));
        }
    };

    return (
        <div
            className="fixed inset-0 z-[60] flex items-start justify-center bg-[#1A1A2E]/50 px-4 pt-20 backdrop-blur-sm sm:pt-28"
            role="presentation"
            onClick={onClose}
        >
            <div
                className="w-full max-w-2xl overflow-hidden rounded-2xl border border-[#E9ECEF] bg-white shadow-2xl"
                role="dialog"
                aria-modal="true"
                aria-label="Recherche dans le catalogue"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Champ */}
                <div className="flex items-center gap-3 border-b border-[#E9ECEF] px-5 py-4">
                    <svg
                        className="h-5 w-5 flex-shrink-0 text-[#25D366]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2}
                        aria-hidden="true"
                    >
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input
                        ref={inputRef}
                        type="text"
                        value={term}
                        onChange={(e) => setTerm(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder="Chercher un produit, une catégorie…"
                        className="flex-1 bg-transparent text-base text-[#1A1A2E] placeholder:text-[#4A4A6A]/60 focus:outline-none"
                        aria-label="Rechercher"
                        autoComplete="off"
                    />
                    {loading && trimmed.length >= 2 && (
                        <span
                            className="h-4 w-4 flex-shrink-0 animate-spin rounded-full border-2 border-[#E9ECEF] border-t-[#25D366]"
                            aria-label="Recherche en cours"
                        />
                    )}
                    <button
                        type="button"
                        onClick={onClose}
                        className="flex-shrink-0 rounded-lg border border-[#E9ECEF] px-2 py-1 text-[10px] font-semibold text-[#4A4A6A] transition-colors hover:bg-[#F8F9FA]"
                    >
                        ESC
                    </button>
                </div>

                {/* Résultats */}
                <div className="max-h-[60vh] overflow-y-auto">
                    {trimmed.length < 2 ? (
                        <p className="px-5 py-8 text-center text-sm text-[#4A4A6A]">
                            Tapez au moins deux lettres pour lancer la
                            recherche.
                        </p>
                    ) : hits.length === 0 &&
                      results.categories.length === 0 &&
                      !loading ? (
                        <div className="px-5 py-10 text-center">
                            <p className="mb-1 font-display text-lg font-bold text-[#1A1A2E]">
                                Aucun résultat pour « {trimmed} »
                            </p>
                            <p className="text-sm text-[#4A4A6A]">
                                Essayez un autre terme, ou parcourez le
                                catalogue complet.
                            </p>
                        </div>
                    ) : (
                        <>
                            {results.categories.length > 0 && (
                                <div className="border-b border-[#E9ECEF] px-5 py-3">
                                    <p className="mb-2 text-[10px] font-bold tracking-widest text-[#4A4A6A] uppercase">
                                        Catégories
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {results.categories.map((category) => (
                                            <button
                                                key={category.id}
                                                type="button"
                                                onClick={() =>
                                                    go(
                                                        `/?categorie=${category.id}#produits`,
                                                    )
                                                }
                                                className="rounded-full border border-[#E9ECEF] bg-[#F8F9FA] px-3 py-1.5 text-xs font-semibold text-[#1A1A2E] transition-colors hover:border-[#25D366] hover:bg-[#E8F5E9]"
                                            >
                                                {category.label}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {hits.length > 0 && (
                                <ul className="py-2">
                                    {hits.map((hit, index) => (
                                        <li key={hit.id}>
                                            <button
                                                type="button"
                                                onMouseEnter={() =>
                                                    setHighlight(index)
                                                }
                                                onClick={() =>
                                                    go(productUrl(hit.slug))
                                                }
                                                className={`flex w-full items-center gap-3 px-5 py-2.5 text-left transition-colors ${
                                                    index === highlight
                                                        ? 'bg-[#E8F5E9]'
                                                        : 'hover:bg-[#F8F9FA]'
                                                }`}
                                            >
                                                <img
                                                    src={hit.img}
                                                    alt=""
                                                    loading="lazy"
                                                    className="h-11 w-11 flex-shrink-0 rounded-lg border border-[#E9ECEF] bg-white object-cover"
                                                />
                                                <span className="min-w-0 flex-1">
                                                    <span className="block truncate text-sm font-semibold text-[#1A1A2E]">
                                                        {hit.name}
                                                    </span>
                                                    <span className="block truncate text-xs text-[#4A4A6A]">
                                                        {hit.category}
                                                        {hit.stock === 0 &&
                                                            ' — rupture de stock'}
                                                    </span>
                                                </span>
                                                <span className="flex-shrink-0 text-sm font-bold text-[#25D366]">
                                                    {formatPrice(hit.price)}
                                                </span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </>
                    )}
                </div>

                {/* Aide clavier */}
                <div className="hidden items-center gap-4 border-t border-[#E9ECEF] bg-[#F8F9FA] px-5 py-2.5 text-[10px] text-[#4A4A6A] sm:flex">
                    <span>
                        <kbd className="rounded border border-[#E9ECEF] bg-white px-1.5 py-0.5 font-sans">
                            ↑
                        </kbd>{' '}
                        <kbd className="rounded border border-[#E9ECEF] bg-white px-1.5 py-0.5 font-sans">
                            ↓
                        </kbd>{' '}
                        naviguer
                    </span>
                    <span>
                        <kbd className="rounded border border-[#E9ECEF] bg-white px-1.5 py-0.5 font-sans">
                            ⏎
                        </kbd>{' '}
                        ouvrir la fiche
                    </span>
                </div>
            </div>
        </div>
    );
}
