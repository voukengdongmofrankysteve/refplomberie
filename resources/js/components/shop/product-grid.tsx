import { useMemo, useState } from 'react';
import ProductCard from '@/components/shop/product-card';
import type { Category, Product } from '@/types/shop';

type SortKey = 'default' | 'price-asc' | 'price-desc' | 'rating' | 'promo';

type ProductGridProps = {
    products: Product[];
    categories: Category[];
};

export default function ProductGrid({
    products,
    categories,
}: ProductGridProps) {
    // La recherche instantanée renvoie vers `/?categorie=<slug>#produits` :
    // la grille s'ouvre alors directement sur la bonne catégorie.
    const [activeCategory, setActiveCategory] = useState(() => {
        if (typeof window === 'undefined') {
            return 'all';
        }

        return (
            new URLSearchParams(window.location.search).get('categorie') ??
            'all'
        );
    });
    const [sort, setSort] = useState<SortKey>('default');
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        let list = products;

        if (activeCategory !== 'all') {
            list = list.filter((p) => p.category === activeCategory);
        }

        if (search.trim()) {
            const q = search.toLowerCase();
            list = list.filter(
                (p) =>
                    p.name.toLowerCase().includes(q) ||
                    p.desc.toLowerCase().includes(q) ||
                    p.category.toLowerCase().includes(q),
            );
        }

        switch (sort) {
            case 'price-asc':
                return [...list].sort((a, b) => a.price - b.price);
            case 'price-desc':
                return [...list].sort((a, b) => b.price - a.price);
            case 'rating':
                return [...list].sort((a, b) => b.rating - a.rating);
            case 'promo':
                return list.filter((p) => p.oldPrice);
            default:
                return list;
        }
    }, [products, activeCategory, sort, search]);

    return (
        <section id="produits" className="bg-[#F8F9FA] py-16 md:py-24">
            <div className="mx-auto max-w-7xl px-4 md:px-8">
                {/* En-tête */}
                <div className="mb-10 flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div>
                        <p className="mb-2 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                            Notre catalogue
                        </p>
                        <h2 className="font-display text-3xl font-bold text-[#1A1A2E] md:text-4xl">
                            Nos produits plomberie
                        </h2>
                    </div>

                    {/* Recherche */}
                    <div className="relative w-full md:w-72">
                        <input
                            type="search"
                            placeholder="Rechercher un produit..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full rounded-xl border border-[#E9ECEF] bg-white py-3 pr-4 pl-10 text-sm text-[#1A1A2E] transition-all focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 focus:outline-none"
                            aria-label="Rechercher un produit"
                        />
                        <svg
                            className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[#4A4A6A]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2}
                            aria-hidden="true"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                    </div>
                </div>

                {/* Filtres par catégorie */}
                <div className="mb-6 flex flex-wrap gap-2">
                    {categories.map((cat) => (
                        <button
                            key={cat.id}
                            onClick={() => setActiveCategory(cat.id)}
                            className={`rounded-full border px-4 py-2 text-sm font-semibold transition-all ${
                                activeCategory === cat.id
                                    ? 'border-[#25D366] bg-[#25D366] text-[#1A1A2E] shadow-md'
                                    : 'border-[#E9ECEF] bg-white text-[#4A4A6A] hover:border-[#25D366] hover:text-[#1A1A2E]'
                            }`}
                        >
                            {cat.label}
                        </button>
                    ))}
                </div>

                {/* Tri + décompte */}
                <div className="mb-8 flex items-center justify-between gap-4">
                    <p className="text-sm text-[#4A4A6A]">
                        <span className="font-semibold text-[#1A1A2E]">
                            {filtered.length}
                        </span>{' '}
                        produit{filtered.length !== 1 ? 's' : ''}
                    </p>
                    <select
                        value={sort}
                        onChange={(e) => setSort(e.target.value as SortKey)}
                        className="cursor-pointer rounded-lg border border-[#E9ECEF] bg-white px-3 py-2 text-sm text-[#1A1A2E] focus:border-[#25D366] focus:outline-none"
                        aria-label="Trier les produits"
                    >
                        <option value="default">Trier par défaut</option>
                        <option value="price-asc">Prix croissant</option>
                        <option value="price-desc">Prix décroissant</option>
                        <option value="rating">Mieux notés</option>
                        <option value="promo">En promotion</option>
                    </select>
                </div>

                {/* Grille */}
                {filtered.length > 0 ? (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {filtered.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                ) : (
                    <div className="py-20 text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#E8F5E9]">
                            <svg
                                className="h-8 w-8 text-[#25D366]"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={1.5}
                                aria-hidden="true"
                            >
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                        </div>
                        <p className="mb-2 font-display text-xl font-bold text-[#1A1A2E]">
                            Aucun produit trouvé
                        </p>
                        <p className="text-sm text-[#4A4A6A]">
                            Essayez un autre terme ou une autre catégorie.
                        </p>
                        <button
                            onClick={() => {
                                setSearch('');
                                setActiveCategory('all');
                            }}
                            className="mt-4 text-sm font-semibold text-[#25D366] underline underline-offset-4"
                        >
                            Réinitialiser les filtres
                        </button>
                    </div>
                )}
            </div>
        </section>
    );
}
