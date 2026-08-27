import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Trash2, Truck } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type Supplier = {
    id: number;
    name: string;
    contactName: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    notes: string | null;
    purchaseOrdersCount: number;
};

const BLANK = {
    name: '',
    contact_name: '',
    phone: '',
    email: '',
    address: '',
    notes: '',
};

export default function AdminSuppliers({
    suppliers,
}: {
    suppliers: Supplier[];
}) {
    const [editing, setEditing] = useState<number | null>(null);
    const { data, setData, post, put, processing, errors, reset } =
        useForm(BLANK);

    const startCreate = () => {
        setEditing(null);
        reset();
    };

    const startEdit = (supplier: Supplier) => {
        setEditing(supplier.id);
        setData({
            name: supplier.name,
            contact_name: supplier.contactName ?? '',
            phone: supplier.phone ?? '',
            email: supplier.email ?? '',
            address: supplier.address ?? '',
            notes: supplier.notes ?? '',
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
            post(admin.suppliers.store().url, options);
        } else {
            put(admin.suppliers.update(editing).url, options);
        }
    };

    const destroy = (supplier: Supplier) => {
        if (window.confirm(`Supprimer le fournisseur « ${supplier.name} » ?`)) {
            router.delete(admin.suppliers.destroy(supplier.id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Fournisseurs" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">
                            Fournisseurs
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {suppliers.length} fournisseur
                            {suppliers.length !== 1 ? 's' : ''}.{' '}
                            <Link
                                href={admin.purchaseOrders.index()}
                                className="text-primary hover:underline"
                            >
                                Voir les bons de commande
                            </Link>
                        </p>
                    </div>
                    <Button variant="secondary" onClick={startCreate}>
                        <Plus className="size-4" />
                        Nouveau fournisseur
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_380px]">
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        {suppliers.length === 0 ? (
                            <EmptyState
                                icon={Truck}
                                title="Aucun fournisseur"
                                description="Ajoutez vos fournisseurs pour pouvoir leur passer des bons de commande."
                            />
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {suppliers.map((supplier) => (
                                    <li
                                        key={supplier.id}
                                        className="flex flex-wrap items-start gap-3 p-4"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-sm font-semibold">
                                                    {supplier.name}
                                                </span>
                                                {supplier.purchaseOrdersCount >
                                                    0 && (
                                                    <Badge variant="secondary">
                                                        {
                                                            supplier.purchaseOrdersCount
                                                        }{' '}
                                                        bon
                                                        {supplier.purchaseOrdersCount !==
                                                        1
                                                            ? 's'
                                                            : ''}
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {[
                                                    supplier.contactName,
                                                    supplier.phone,
                                                    supplier.email,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ') ||
                                                    'Aucun contact renseigné'}
                                            </p>
                                        </div>

                                        <div className="flex shrink-0 gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    startEdit(supplier)
                                                }
                                            >
                                                Modifier
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    destroy(supplier)
                                                }
                                                aria-label={`Supprimer ${supplier.name}`}
                                            >
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="h-fit rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">
                            {editing === null
                                ? 'Nouveau fournisseur'
                                : 'Modifier le fournisseur'}
                        </h2>

                        <form onSubmit={handleSubmit} className="space-y-3">
                            <div>
                                <Label htmlFor="supplier-name">Nom *</Label>
                                <Input
                                    id="supplier-name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder="Quincaillerie du Centre"
                                />
                                {errors.name && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="supplier-contact">
                                    Contact
                                </Label>
                                <Input
                                    id="supplier-contact"
                                    value={data.contact_name}
                                    onChange={(e) =>
                                        setData(
                                            'contact_name',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Nom de la personne à contacter"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="supplier-phone">
                                        Téléphone
                                    </Label>
                                    <Input
                                        id="supplier-phone"
                                        value={data.phone}
                                        onChange={(e) =>
                                            setData('phone', e.target.value)
                                        }
                                        placeholder="690000000"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="supplier-email">
                                        Email
                                    </Label>
                                    <Input
                                        id="supplier-email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                    />
                                    {errors.email && (
                                        <p className="mt-1 text-xs text-destructive">
                                            {errors.email}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="supplier-address">
                                    Adresse
                                </Label>
                                <Input
                                    id="supplier-address"
                                    value={data.address}
                                    onChange={(e) =>
                                        setData('address', e.target.value)
                                    }
                                />
                            </div>

                            <div>
                                <Label htmlFor="supplier-notes">Notes</Label>
                                <textarea
                                    id="supplier-notes"
                                    rows={3}
                                    value={data.notes}
                                    onChange={(e) =>
                                        setData('notes', e.target.value)
                                    }
                                    className="mt-1 w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none"
                                />
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

AdminSuppliers.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Fournisseurs', href: admin.suppliers.index() },
    ],
};
