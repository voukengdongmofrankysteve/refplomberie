import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';

type Sale = {
    id: number;
    title: string;
    startsAt: string;
    endsAt: string;
    isActive: boolean;
    isRunning: boolean;
};

type SaleProduct = {
    id: number;
    slug: string;
    name: string;
    category: string;
    price: number;
    salePrice: number;
    image: string;
};

type CatalogProduct = { id: number; name: string; price: number };

type Props = {
    sale: Sale;
    products: SaleProduct[];
    catalog: CatalogProduct[];
};

export default function AdminFlashSaleShow({ sale, products, catalog }: Props) {
    const [filter, setFilter] = useState('');
    const [editingPrice, setEditingPrice] = useState<number | null>(null);

    const addForm = useForm({ product_id: '', sale_price: '' });
    const priceForm = useForm({ sale_price: '' });

    const filteredCatalog = catalog.filter((product) =>
        product.name.toLowerCase().includes(filter.toLowerCase()),
    );

    const submitAdd = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        addForm.post(admin.flashSales.products.store(sale.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                addForm.reset();
                setFilter('');
            },
        });
    };

    const startEditPrice = (product: SaleProduct) => {
        setEditingPrice(product.id);
        priceForm.setData('sale_price', String(product.salePrice));
    };

    const submitPrice = (e: FormEvent<HTMLFormElement>, slug: string) => {
        e.preventDefault();

        priceForm.put(admin.flashSales.products.update([sale.id, slug]).url, {
            preserveScroll: true,
            onSuccess: () => setEditingPrice(null),
        });
    };

    const remove = (product: SaleProduct) => {
        if (window.confirm(`Retirer « ${product.name} » de la vente flash ?`)) {
            router.delete(
                admin.flashSales.products.destroy([sale.id, product.slug]).url,
                { preserveScroll: true },
            );
        }
    };

    return (
        <>
            <Head title={`Vente flash — ${sale.title}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <Link
                        href={admin.flashSales.index()}
                        className="mb-2 flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-3.5" />
                        Ventes flash
                    </Link>
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-lg font-semibold">{sale.title}</h1>
                        {sale.isRunning ? (
                            <Badge className="bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300">
                                En cours
                            </Badge>
                        ) : !sale.isActive ? (
                            <Badge
                                variant="secondary"
                                className="bg-muted text-muted-foreground"
                            >
                                Désactivée
                            </Badge>
                        ) : (
                            <Badge variant="secondary">Programmée</Badge>
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {new Date(sale.startsAt).toLocaleString('fr-FR')} →{' '}
                        {new Date(sale.endsAt).toLocaleString('fr-FR')}
                    </p>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_380px]">
                    {/* Produits de la vente */}
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        {products.length === 0 ? (
                            <p className="p-8 text-center text-sm text-muted-foreground">
                                Aucun produit pour l’instant. Ajoutez-en un
                                depuis le formulaire.
                            </p>
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {products.map((product) => {
                                    const discount = Math.round(
                                        (1 -
                                            product.salePrice / product.price) *
                                            100,
                                    );

                                    return (
                                        <li
                                            key={product.id}
                                            className="flex flex-wrap items-center gap-3 p-4"
                                        >
                                            <img
                                                src={product.image}
                                                alt=""
                                                className="size-12 shrink-0 rounded-lg object-cover"
                                            />
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-semibold">
                                                    {product.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {product.category}
                                                </p>
                                            </div>

                                            {editingPrice === product.id ? (
                                                <form
                                                    onSubmit={(e) =>
                                                        submitPrice(
                                                            e,
                                                            product.slug,
                                                        )
                                                    }
                                                    className="flex items-center gap-2"
                                                >
                                                    <Input
                                                        type="number"
                                                        min={1}
                                                        value={
                                                            priceForm.data
                                                                .sale_price
                                                        }
                                                        onChange={(e) =>
                                                            priceForm.setData(
                                                                'sale_price',
                                                                e.target.value,
                                                            )
                                                        }
                                                        className="h-8 w-28"
                                                        autoFocus
                                                    />
                                                    <Button
                                                        type="submit"
                                                        size="sm"
                                                        disabled={
                                                            priceForm.processing
                                                        }
                                                    >
                                                        OK
                                                    </Button>
                                                </form>
                                            ) : (
                                                <button
                                                    onClick={() =>
                                                        startEditPrice(product)
                                                    }
                                                    className="shrink-0 text-right"
                                                    title="Modifier le prix de vente flash"
                                                >
                                                    <p className="text-sm font-bold text-red-600">
                                                        {formatPrice(
                                                            product.salePrice,
                                                        )}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground line-through">
                                                        {formatPrice(
                                                            product.price,
                                                        )}
                                                    </p>
                                                </button>
                                            )}

                                            <Badge
                                                variant="secondary"
                                                className="bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300"
                                            >
                                                −{discount}%
                                            </Badge>

                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => remove(product)}
                                                aria-label={`Retirer ${product.name}`}
                                            >
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </section>

                    {/* Ajouter un produit */}
                    <section className="h-fit rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">
                            Ajouter un produit
                        </h2>

                        <form onSubmit={submitAdd} className="space-y-3">
                            <div>
                                <Label htmlFor="sale-product-filter">
                                    Rechercher
                                </Label>
                                <Input
                                    id="sale-product-filter"
                                    value={filter}
                                    onChange={(e) => setFilter(e.target.value)}
                                    placeholder="Nom du produit…"
                                />
                            </div>

                            <div>
                                <Label htmlFor="sale-product">Produit *</Label>
                                <select
                                    id="sale-product"
                                    value={addForm.data.product_id}
                                    onChange={(e) =>
                                        addForm.setData(
                                            'product_id',
                                            e.target.value,
                                        )
                                    }
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                >
                                    <option value="">
                                        — Choisir un produit —
                                    </option>
                                    {filteredCatalog.map((product) => (
                                        <option
                                            key={product.id}
                                            value={product.id}
                                        >
                                            {product.name} (
                                            {formatPrice(product.price)})
                                        </option>
                                    ))}
                                </select>
                                {addForm.errors.product_id && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {addForm.errors.product_id}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="sale-product-price">
                                    Prix de vente flash (FCFA) *
                                </Label>
                                <Input
                                    id="sale-product-price"
                                    type="number"
                                    min={1}
                                    value={addForm.data.sale_price}
                                    onChange={(e) =>
                                        addForm.setData(
                                            'sale_price',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Doit être inférieur au prix actuel"
                                />
                                {addForm.errors.sale_price && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {addForm.errors.sale_price}
                                    </p>
                                )}
                            </div>

                            <Button type="submit" disabled={addForm.processing}>
                                {addForm.processing
                                    ? 'Ajout…'
                                    : 'Ajouter à la vente'}
                            </Button>
                        </form>
                    </section>
                </div>
            </div>
        </>
    );
}

AdminFlashSaleShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Ventes flash', href: admin.flashSales.index() },
    ],
};
