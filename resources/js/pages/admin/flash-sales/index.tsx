import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Timer, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type AdminFlashSale = {
    id: number;
    title: string;
    startsAt: string;
    endsAt: string;
    isActive: boolean;
    isRunning: boolean;
    productsCount: number;
};

const BLANK = {
    title: '',
    starts_at: '',
    ends_at: '',
    is_active: true,
};

/** ISO complet vers le format qu'attend un `<input type="datetime-local">`. */
function toLocalInput(iso: string): string {
    return iso.slice(0, 16);
}

export default function AdminFlashSales({
    sales,
}: {
    sales: AdminFlashSale[];
}) {
    const [editing, setEditing] = useState<number | null>(null);
    const { data, setData, post, put, processing, errors, reset } =
        useForm(BLANK);

    const startCreate = () => {
        setEditing(null);
        reset();
    };

    const startEdit = (sale: AdminFlashSale) => {
        setEditing(sale.id);
        setData({
            title: sale.title,
            starts_at: toLocalInput(sale.startsAt),
            ends_at: toLocalInput(sale.endsAt),
            is_active: sale.isActive,
        });
    };

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setEditing(null);
                reset();
            },
        };

        if (editing === null) {
            post(admin.flashSales.store().url, options);
        } else {
            put(admin.flashSales.update(editing).url, options);
        }
    };

    const destroy = (sale: AdminFlashSale) => {
        if (window.confirm(`Supprimer la vente « ${sale.title} » ?`)) {
            router.delete(admin.flashSales.destroy(sale.id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Ventes flash" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">
                            Ventes flash
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {sales.length} vente{sales.length !== 1 ? 's' : ''}
                            . Une seule tourne à la fois sur la vitrine.
                        </p>
                    </div>
                    <Button variant="secondary" onClick={startCreate}>
                        <Plus className="size-4" />
                        Nouvelle vente
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_380px]">
                    {/* Liste */}
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        {sales.length === 0 ? (
                            <EmptyState
                                icon={Timer}
                                title="Aucune vente flash"
                                description="Créez une vente, puis ajoutez-y des produits à prix réduit."
                            />
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {sales.map((sale) => (
                                    <li
                                        key={sale.id}
                                        className="flex flex-wrap items-center gap-3 p-4"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Link
                                                    href={admin.flashSales.show(
                                                        sale.id,
                                                    )}
                                                    className="text-sm font-semibold hover:underline"
                                                >
                                                    {sale.title}
                                                </Link>
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
                                                ) : null}
                                            </div>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {new Date(
                                                    sale.startsAt,
                                                ).toLocaleString('fr-FR')}{' '}
                                                →{' '}
                                                {new Date(
                                                    sale.endsAt,
                                                ).toLocaleString('fr-FR')}{' '}
                                                · {sale.productsCount} produit
                                                {sale.productsCount !== 1
                                                    ? 's'
                                                    : ''}
                                            </p>
                                        </div>

                                        <div className="flex shrink-0 gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={admin.flashSales.show(
                                                        sale.id,
                                                    )}
                                                >
                                                    Produits
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    startEdit(sale)
                                                }
                                            >
                                                Modifier
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    destroy(sale)
                                                }
                                                aria-label={`Supprimer ${sale.title}`}
                                            >
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    {/* Formulaire */}
                    <section className="h-fit rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">
                            {editing === null
                                ? 'Nouvelle vente'
                                : 'Modifier la vente'}
                        </h2>

                        <form onSubmit={handleSubmit} className="space-y-3">
                            <div>
                                <Label htmlFor="sale-title">Titre *</Label>
                                <Input
                                    id="sale-title"
                                    value={data.title}
                                    onChange={(e) =>
                                        setData('title', e.target.value)
                                    }
                                    placeholder="Vente flash du week-end"
                                />
                                {errors.title && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.title}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="sale-start">Début *</Label>
                                <Input
                                    id="sale-start"
                                    type="datetime-local"
                                    value={data.starts_at}
                                    onChange={(e) =>
                                        setData('starts_at', e.target.value)
                                    }
                                />
                                {errors.starts_at && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.starts_at}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="sale-end">Fin *</Label>
                                <Input
                                    id="sale-end"
                                    type="datetime-local"
                                    value={data.ends_at}
                                    onChange={(e) =>
                                        setData('ends_at', e.target.value)
                                    }
                                />
                                {errors.ends_at && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.ends_at}
                                    </p>
                                )}
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="sale-active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="sale-active"
                                    className="font-normal"
                                >
                                    Vente activée
                                </Label>
                            </div>

                            <div className="flex gap-2 pt-1">
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Enregistrement…'
                                        : editing === null
                                          ? 'Créer'
                                          : 'Mettre à jour'}
                                </Button>
                                {editing !== null && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={startCreate}
                                    >
                                        Annuler
                                    </Button>
                                )}
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </>
    );
}

AdminFlashSales.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Ventes flash', href: admin.flashSales.index() },
    ],
};
