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

type Order = {
    id: number;
    reference: string;
    supplier: string;
    status: string;
    statusLabel: string;
    expectedAt: string | null;
    note: string | null;
    total: number;
    isEditable: boolean;
};

type Item = {
    id: number;
    productName: string;
    unitCost: number;
    quantity: number;
    lineTotal: number;
};

type CatalogProduct = { id: number; name: string; price: number };

type Props = {
    order: Order;
    items: Item[];
    statuses: { value: string; label: string }[];
    catalog: CatalogProduct[];
};

const STATUS_TONE: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    ordered: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    received:
        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
};

export default function AdminPurchaseOrderShow({
    order,
    items,
    statuses,
    catalog,
}: Props) {
    const [filter, setFilter] = useState('');

    const statusForm = useForm({
        status: order.status,
        expected_at: order.expectedAt ?? '',
        note: order.note ?? '',
    });
    const addForm = useForm({ product_id: '', quantity: '1', unit_cost: '' });

    const filteredCatalog = catalog.filter((product) =>
        product.name.toLowerCase().includes(filter.toLowerCase()),
    );

    const submitStatus = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        statusForm.put(admin.purchaseOrders.update(order.id).url, {
            preserveScroll: true,
        });
    };

    const submitAdd = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        addForm.post(admin.purchaseOrders.items.store(order.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                addForm.reset();
                setFilter('');
            },
        });
    };

    const removeItem = (item: Item) => {
        if (window.confirm(`Retirer « ${item.productName} » du bon ?`)) {
            router.delete(
                admin.purchaseOrders.items.destroy([order.id, item.id]).url,
                { preserveScroll: true },
            );
        }
    };

    return (
        <>
            <Head title={`Bon de commande — ${order.reference}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <Link
                        href={admin.purchaseOrders.index()}
                        className="mb-2 flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-3.5" />
                        Bons de commande
                    </Link>
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-lg font-semibold">
                            {order.reference}
                        </h1>
                        <Badge
                            variant="secondary"
                            className={STATUS_TONE[order.status]}
                        >
                            {order.statusLabel}
                        </Badge>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {order.supplier} · {formatPrice(order.total)}
                    </p>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_380px]">
                    <div className="space-y-4">
                        {/* Lignes du bon */}
                        <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                            {items.length === 0 ? (
                                <p className="p-8 text-center text-sm text-muted-foreground">
                                    Aucun article pour l’instant. Ajoutez-en
                                    un depuis le formulaire.
                                </p>
                            ) : (
                                <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                    {items.map((item) => (
                                        <li
                                            key={item.id}
                                            className="flex flex-wrap items-center gap-3 p-4"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-semibold">
                                                    {item.productName}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {item.quantity} ×{' '}
                                                    {formatPrice(
                                                        item.unitCost,
                                                    )}
                                                </p>
                                            </div>

                                            <p className="shrink-0 text-sm font-bold">
                                                {formatPrice(item.lineTotal)}
                                            </p>

                                            {order.isEditable && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        removeItem(item)
                                                    }
                                                    aria-label={`Retirer ${item.productName}`}
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        {order.isEditable && (
                            <section className="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                                <h2 className="mb-4 text-sm font-semibold">
                                    Ajouter un article
                                </h2>

                                <form
                                    onSubmit={submitAdd}
                                    className="grid gap-3 sm:grid-cols-[1fr_auto_auto_auto]"
                                >
                                    <div className="sm:col-span-4">
                                        <Input
                                            value={filter}
                                            onChange={(e) =>
                                                setFilter(e.target.value)
                                            }
                                            placeholder="Rechercher un produit…"
                                        />
                                    </div>

                                    <div className="sm:col-span-2">
                                        <select
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
                                                — Produit —
                                            </option>
                                            {filteredCatalog.map(
                                                (product) => (
                                                    <option
                                                        key={product.id}
                                                        value={product.id}
                                                    >
                                                        {product.name}
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                        {addForm.errors.product_id && (
                                            <p className="mt-1 text-xs text-destructive">
                                                {addForm.errors.product_id}
                                            </p>
                                        )}
                                    </div>

                                    <Input
                                        type="number"
                                        min={1}
                                        value={addForm.data.quantity}
                                        onChange={(e) =>
                                            addForm.setData(
                                                'quantity',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Qté"
                                        className="w-24"
                                    />

                                    <Input
                                        type="number"
                                        min={0}
                                        value={addForm.data.unit_cost}
                                        onChange={(e) =>
                                            addForm.setData(
                                                'unit_cost',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Coût unitaire"
                                        className="w-36"
                                    />

                                    <Button
                                        type="submit"
                                        disabled={addForm.processing}
                                        className="sm:col-span-4"
                                    >
                                        {addForm.processing
                                            ? 'Ajout…'
                                            : 'Ajouter au bon'}
                                    </Button>
                                </form>
                            </section>
                        )}
                    </div>

                    {/* Statut */}
                    <section className="h-fit rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">Statut</h2>

                        {order.isEditable ? (
                            <form
                                onSubmit={submitStatus}
                                className="space-y-3"
                            >
                                <div>
                                    <Label htmlFor="po-status">
                                        Statut *
                                    </Label>
                                    <select
                                        id="po-status"
                                        value={statusForm.data.status}
                                        onChange={(e) =>
                                            statusForm.setData(
                                                'status',
                                                e.target.value,
                                            )
                                        }
                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                    >
                                        {statuses.map((status) => (
                                            <option
                                                key={status.value}
                                                value={status.value}
                                            >
                                                {status.label}
                                            </option>
                                        ))}
                                    </select>
                                    {statusForm.data.status === 'received' && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Le stock des articles sera
                                            incrémenté à l’enregistrement.
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="po-expected">
                                        Livraison attendue
                                    </Label>
                                    <Input
                                        id="po-expected"
                                        type="date"
                                        value={statusForm.data.expected_at}
                                        onChange={(e) =>
                                            statusForm.setData(
                                                'expected_at',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="po-note">Note</Label>
                                    <textarea
                                        id="po-note"
                                        rows={3}
                                        value={statusForm.data.note}
                                        onChange={(e) =>
                                            statusForm.setData(
                                                'note',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none"
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={statusForm.processing}
                                >
                                    {statusForm.processing
                                        ? 'Enregistrement…'
                                        : 'Mettre à jour'}
                                </Button>
                            </form>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Ce bon est clos et ne peut plus être modifié.
                                {order.note && (
                                    <>
                                        <br />
                                        <span className="italic">
                                            « {order.note} »
                                        </span>
                                    </>
                                )}
                            </p>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

AdminPurchaseOrderShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Bons de commande', href: admin.purchaseOrders.index() },
    ],
};
