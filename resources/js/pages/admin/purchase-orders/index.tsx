import { Head, Link, useForm } from '@inertiajs/react';
import { ClipboardList, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
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
    total: number;
    itemsCount: number;
};

type Supplier = { id: number; name: string };

type Props = {
    orders: Order[];
    suppliers: Supplier[];
    statuses: { value: string; label: string }[];
};

const STATUS_TONE: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    ordered: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    received:
        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
};

export default function AdminPurchaseOrders({ orders, suppliers }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        supplier_id: '',
        expected_at: '',
        note: '',
    });

    const submit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(admin.purchaseOrders.store().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Bons de commande" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">
                            Bons de commande
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {orders.length} bon{orders.length !== 1 ? 's' : ''}.{' '}
                            <Link
                                href={admin.suppliers.index()}
                                className="text-primary hover:underline"
                            >
                                Gérer les fournisseurs
                            </Link>
                        </p>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_380px]">
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        {orders.length === 0 ? (
                            <EmptyState
                                icon={ClipboardList}
                                title="Aucun bon de commande"
                                description="Créez-en un depuis le formulaire pour réapprovisionner votre stock."
                            />
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {orders.map((order) => (
                                    <li
                                        key={order.id}
                                        className="flex flex-wrap items-center gap-3 p-4"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Link
                                                    href={admin.purchaseOrders.show(
                                                        order.id,
                                                    )}
                                                    className="text-sm font-semibold hover:underline"
                                                >
                                                    {order.reference}
                                                </Link>
                                                <Badge
                                                    variant="secondary"
                                                    className={
                                                        STATUS_TONE[
                                                            order.status
                                                        ]
                                                    }
                                                >
                                                    {order.statusLabel}
                                                </Badge>
                                            </div>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {order.supplier} ·{' '}
                                                {order.itemsCount} article
                                                {order.itemsCount !== 1
                                                    ? 's'
                                                    : ''}
                                                {order.expectedAt &&
                                                    ` · attendu le ${new Date(order.expectedAt).toLocaleDateString('fr-FR')}`}
                                            </p>
                                        </div>

                                        <p className="shrink-0 text-sm font-bold">
                                            {formatPrice(order.total)}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="h-fit rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">
                            Nouveau bon de commande
                        </h2>

                        {suppliers.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Ajoutez d’abord un{' '}
                                <Link
                                    href={admin.suppliers.index()}
                                    className="text-primary hover:underline"
                                >
                                    fournisseur
                                </Link>
                                .
                            </p>
                        ) : (
                            <form onSubmit={submit} className="space-y-3">
                                <div>
                                    <Label htmlFor="po-supplier">
                                        Fournisseur *
                                    </Label>
                                    <select
                                        id="po-supplier"
                                        value={data.supplier_id}
                                        onChange={(e) =>
                                            setData(
                                                'supplier_id',
                                                e.target.value,
                                            )
                                        }
                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                    >
                                        <option value="">
                                            — Choisir un fournisseur —
                                        </option>
                                        {suppliers.map((supplier) => (
                                            <option
                                                key={supplier.id}
                                                value={supplier.id}
                                            >
                                                {supplier.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.supplier_id && (
                                        <p className="mt-1 text-xs text-destructive">
                                            {errors.supplier_id}
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
                                        value={data.expected_at}
                                        onChange={(e) =>
                                            setData(
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
                                        value={data.note}
                                        onChange={(e) =>
                                            setData('note', e.target.value)
                                        }
                                        className="mt-1 w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none"
                                    />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    <Plus className="size-4" />
                                    {processing ? 'Création…' : 'Créer'}
                                </Button>
                            </form>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

AdminPurchaseOrders.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Bons de commande', href: admin.purchaseOrders.index() },
    ],
};
