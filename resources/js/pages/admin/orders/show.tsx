import { Head, router, useForm } from '@inertiajs/react';
import { Download, MessageCircle, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import StatusBadge from '@/components/dashboard/status-badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';
import type { Order, StatusOption } from '@/types/shop';

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function AdminOrderShow({
    order,
    statuses,
}: {
    order: Order;
    statuses: StatusOption[];
}) {
    const { data, setData, put, processing, errors } = useForm({
        status: order.status,
        note: order.note ?? '',
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        put(admin.orders.update(order.id).url, { preserveScroll: true });
    };

    const destroy = () => {
        if (
            window.confirm(
                `Supprimer définitivement la commande ${order.reference} ?`,
            )
        ) {
            router.delete(admin.orders.destroy(order.id).url);
        }
    };

    return (
        <>
            <Head title={`Commande ${order.reference}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-lg font-semibold">
                                {order.reference}
                            </h1>
                            <StatusBadge
                                status={order.status}
                                label={order.statusLabel}
                            />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Passée le {order.createdAt}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild>
                            <a
                                href={order.whatsAppUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <MessageCircle className="size-4" />
                                Prévenir sur WhatsApp
                            </a>
                        </Button>
                        <Button variant="secondary" asChild>
                            <a href={admin.orders.pdf(order.id).url}>
                                <Download className="size-4" />
                                Facture pro forma
                            </a>
                        </Button>
                        <Button variant="outline" onClick={destroy}>
                            <Trash2 className="size-4 text-destructive" />
                            Supprimer
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Lignes de commande */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card lg:col-span-2 dark:border-sidebar-border">
                        <header className="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                            <h2 className="text-sm font-semibold">
                                Articles commandés
                            </h2>
                        </header>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b border-sidebar-border/70 bg-muted/50 text-left text-xs text-muted-foreground dark:border-sidebar-border">
                                    <tr>
                                        <th className="px-4 py-2.5 font-medium">
                                            Produit
                                        </th>
                                        <th className="px-4 py-2.5 text-right font-medium">
                                            Prix unitaire
                                        </th>
                                        <th className="px-4 py-2.5 text-right font-medium">
                                            Qté
                                        </th>
                                        <th className="px-4 py-2.5 text-right font-medium">
                                            Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                    {(order.items ?? []).map((item) => (
                                        <tr key={item.id}>
                                            <td className="px-4 py-3">
                                                {item.productName}
                                            </td>
                                            <td className="px-4 py-3 text-right text-muted-foreground">
                                                {formatPrice(item.unitPrice)}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {item.quantity}
                                            </td>
                                            <td className="px-4 py-3 text-right font-medium">
                                                {formatPrice(item.lineTotal)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="space-y-1 border-t border-sidebar-border/70 px-4 py-3 text-sm dark:border-sidebar-border">
                            <div className="flex justify-between text-muted-foreground">
                                <span>Sous-total</span>
                                <span>{formatPrice(order.subtotal)}</span>
                            </div>
                            {order.discount > 0 && (
                                <div className="flex justify-between font-medium text-green-600 dark:text-green-400">
                                    <span>Remise {order.promoCode}</span>
                                    <span>− {formatPrice(order.discount)}</span>
                                </div>
                            )}
                            <div className="flex justify-between text-muted-foreground">
                                <span>Livraison</span>
                                <span>
                                    {order.shipping === 0
                                        ? 'Gratuite'
                                        : formatPrice(order.shipping)}
                                </span>
                            </div>
                            <div className="flex justify-between text-base font-semibold">
                                <span>Total</span>
                                <span>{formatPrice(order.total)}</span>
                            </div>
                        </div>
                    </section>

                    <div className="space-y-4">
                        {/* Client */}
                        <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                            <header className="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                                <h2 className="text-sm font-semibold">
                                    Client
                                </h2>
                            </header>
                            <dl className="space-y-3 p-4 text-sm">
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Nom
                                    </dt>
                                    <dd className="font-medium">
                                        {order.customerName}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Téléphone
                                    </dt>
                                    <dd className="font-medium">
                                        {order.customerPhone}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Adresse
                                    </dt>
                                    <dd className="font-medium">
                                        {order.customerAddress ??
                                            'Non précisée'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Compte
                                    </dt>
                                    <dd className="font-medium">
                                        {order.accountEmail ??
                                            'Commande invité'}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        {/* Traitement */}
                        <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                            <header className="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                                <h2 className="text-sm font-semibold">
                                    Traitement
                                </h2>
                            </header>

                            <form
                                onSubmit={handleSubmit}
                                className="grid gap-4 p-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Statut</Label>
                                    <select
                                        id="status"
                                        className={SELECT_CLASS}
                                        value={data.status}
                                        onChange={(e) =>
                                            setData('status', e.target.value)
                                        }
                                    >
                                        {statuses.map((s) => (
                                            <option
                                                key={s.value}
                                                value={s.value}
                                            >
                                                {s.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.status && (
                                        <p className="text-xs text-destructive">
                                            {errors.status}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="note">Note interne</Label>
                                    <textarea
                                        id="note"
                                        rows={4}
                                        className="w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        value={data.note}
                                        onChange={(e) =>
                                            setData('note', e.target.value)
                                        }
                                        placeholder="Visible uniquement par l’équipe…"
                                    />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Enregistrement…'
                                        : 'Mettre à jour'}
                                </Button>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </>
    );
}

AdminOrderShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Commandes', href: admin.orders.index() },
    ],
};
