import { Head, Link, router } from '@inertiajs/react';
import { Package, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import PdfExportButton from '@/components/dashboard/pdf-export-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';
import type { Paginated } from '@/types/shop';

type AdminProduct = {
    id: number;
    slug: string;
    name: string;
    category: string;
    price: number;
    stock: number;
    stockLevel: 'out' | 'low' | 'ok';
    lowStockThreshold: number;
    badge: string | null;
    image: string;
    isActive: boolean;
};

type Props = {
    products: Paginated<AdminProduct>;
    categories: { value: number; label: string; slug: string }[];
    filters: { search: string; category: string; stock: string };
};

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function AdminProducts({
    products,
    categories,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [category, setCategory] = useState(filters.category);
    const [stock, setStock] = useState(filters.stock);

    const applyFilters = (next: {
        search?: string;
        category?: string;
        stock?: string;
    }) =>
        router.get(
            admin.products.index().url,
            { search, category, stock, ...next },
            { preserveState: true, replace: true },
        );

    const handleSearch = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        applyFilters({});
    };

    const destroy = (product: AdminProduct) => {
        if (
            window.confirm(
                `Supprimer définitivement « ${product.name} » ? Cette action est irréversible.`,
            )
        ) {
            router.delete(admin.products.destroy(product.slug).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Produits" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">Produits</h1>
                        <p className="text-sm text-muted-foreground">
                            {products.total} référence
                            {products.total !== 1 ? 's' : ''} au catalogue.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <PdfExportButton
                            href={
                                admin.products.export({
                                    query: { search, category, stock },
                                }).url
                            }
                        />
                        <Button asChild>
                            <Link href={admin.products.create()}>
                                <Plus className="size-4" />
                                Nouveau produit
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Filtres */}
                <form
                    onSubmit={handleSearch}
                    className="flex flex-wrap items-center gap-2"
                >
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Rechercher un produit…"
                        className="w-full sm:w-64"
                        aria-label="Rechercher un produit"
                    />
                    <select
                        className={SELECT_CLASS}
                        value={category}
                        onChange={(e) => {
                            setCategory(e.target.value);
                            applyFilters({ category: e.target.value });
                        }}
                        aria-label="Filtrer par catégorie"
                    >
                        <option value="">Toutes les catégories</option>
                        {categories.map((c) => (
                            <option key={c.value} value={c.slug}>
                                {c.label}
                            </option>
                        ))}
                    </select>
                    <select
                        className={SELECT_CLASS}
                        value={stock}
                        onChange={(e) => {
                            setStock(e.target.value);
                            applyFilters({ stock: e.target.value });
                        }}
                        aria-label="Filtrer par niveau de stock"
                    >
                        <option value="">Tous les stocks</option>
                        <option value="low">À réapprovisionner</option>
                        <option value="out">En rupture</option>
                    </select>
                    <Button type="submit" variant="secondary">
                        Filtrer
                    </Button>
                </form>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {products.data.length === 0 ? (
                        <EmptyState
                            icon={Package}
                            title="Aucun produit"
                            description="Ajustez vos filtres ou créez une nouvelle référence."
                        />
                    ) : (
                        <>
                            {/* Mobile : la table déborde de l'écran et rend
                                les actions inatteignables — on la remplace
                                par des cartes empilées. */}
                            <ul className="divide-y divide-sidebar-border/70 md:hidden">
                                {products.data.map((product) => (
                                    <li
                                        key={product.id}
                                        className="flex gap-3 p-4"
                                    >
                                        <img
                                            src={product.image}
                                            alt=""
                                            className="size-16 shrink-0 rounded-md object-cover"
                                        />
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium">
                                                {product.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {product.category}
                                            </p>
                                            <div className="mt-1 flex flex-wrap items-center gap-2 text-sm">
                                                <span className="font-semibold">
                                                    {formatPrice(product.price)}
                                                </span>
                                                <span
                                                    className={`text-xs ${
                                                        product.stockLevel ===
                                                        'out'
                                                            ? 'font-semibold text-destructive'
                                                            : product.stockLevel ===
                                                                'low'
                                                              ? 'font-semibold text-amber-600 dark:text-amber-400'
                                                              : 'text-muted-foreground'
                                                    }`}
                                                >
                                                    {product.stockLevel ===
                                                    'out'
                                                        ? 'Rupture'
                                                        : product.stockLevel ===
                                                            'low'
                                                          ? `${product.stock} en stock — à recommander`
                                                          : `${product.stock} en stock`}
                                                </span>
                                                <Badge
                                                    variant={
                                                        product.isActive
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {product.isActive
                                                        ? 'En ligne'
                                                        : 'Masqué'}
                                                </Badge>
                                            </div>

                                            <div className="mt-3 flex gap-2">
                                                <Button
                                                    asChild
                                                    variant="secondary"
                                                    size="sm"
                                                    className="flex-1"
                                                >
                                                    <Link
                                                        href={admin.products.edit(
                                                            product.slug,
                                                        )}
                                                    >
                                                        <Pencil className="size-4" />
                                                        Modifier
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        destroy(product)
                                                    }
                                                    aria-label={`Supprimer ${product.name}`}
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                    Supprimer
                                                </Button>
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>

                            <div className="hidden overflow-x-auto md:block">
                                <table className="w-full text-sm">
                                    <thead className="border-b border-sidebar-border/70 bg-muted/50 text-left text-xs text-muted-foreground dark:border-sidebar-border">
                                        <tr>
                                            <th className="px-4 py-2.5 font-medium">
                                                Produit
                                            </th>
                                            <th className="px-4 py-2.5 font-medium">
                                                Catégorie
                                            </th>
                                            <th className="px-4 py-2.5 text-right font-medium">
                                                Prix
                                            </th>
                                            <th className="px-4 py-2.5 text-right font-medium">
                                                Stock
                                            </th>
                                            <th className="px-4 py-2.5 font-medium">
                                                État
                                            </th>
                                            <th className="px-4 py-2.5 text-right font-medium">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                        {products.data.map((product) => (
                                            <tr key={product.id}>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-3">
                                                        <img
                                                            src={product.image}
                                                            alt=""
                                                            className="size-10 shrink-0 rounded-md object-cover"
                                                        />
                                                        <div className="min-w-0">
                                                            <p className="truncate font-medium">
                                                                {product.name}
                                                            </p>
                                                            {product.badge && (
                                                                <span className="text-xs text-muted-foreground">
                                                                    {
                                                                        product.badge
                                                                    }
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {product.category}
                                                </td>
                                                <td className="px-4 py-3 text-right font-medium">
                                                    {formatPrice(product.price)}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <span
                                                        className={
                                                            product.stockLevel ===
                                                            'out'
                                                                ? 'font-semibold text-destructive'
                                                                : product.stockLevel ===
                                                                    'low'
                                                                  ? 'font-semibold text-amber-600 dark:text-amber-400'
                                                                  : ''
                                                        }
                                                        title={
                                                            product.lowStockThreshold >
                                                            0
                                                                ? `Seuil d’alerte : ${product.lowStockThreshold}`
                                                                : undefined
                                                        }
                                                    >
                                                        {product.stock}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={
                                                            product.isActive
                                                                ? 'secondary'
                                                                : 'outline'
                                                        }
                                                    >
                                                        {product.isActive
                                                            ? 'En ligne'
                                                            : 'Masqué'}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            asChild
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <Link
                                                                href={admin.products.edit(
                                                                    product.slug,
                                                                )}
                                                                aria-label={`Modifier ${product.name}`}
                                                            >
                                                                <Pencil className="size-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                destroy(product)
                                                            }
                                                            aria-label={`Supprimer ${product.name}`}
                                                        >
                                                            <Trash2 className="size-4 text-destructive" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination
                                links={products.links}
                                from={products.from}
                                to={products.to}
                                total={products.total}
                            />
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

AdminProducts.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Produits', href: admin.products.index() },
    ],
};
