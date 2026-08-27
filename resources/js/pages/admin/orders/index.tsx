import { Head, Link, router } from '@inertiajs/react';
import { ReceiptText } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import PdfExportButton from '@/components/dashboard/pdf-export-button';
import StatusBadge from '@/components/dashboard/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';
import type { Order, PaginatedResource, StatusOption } from '@/types/shop';

type Props = {
    orders: PaginatedResource<Order>;
    statuses: StatusOption[];
    filters: { search: string; status: string };
};

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function AdminOrders({ orders, statuses, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

    const applyFilters = (next: { search?: string; status?: string }) =>
        router.get(
            admin.orders.index().url,
            { search, status, ...next },
            { preserveState: true, replace: true },
        );

    const handleSearch = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        applyFilters({});
    };

    return (
        <>
            <Head title="Commandes" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">Commandes</h1>
                        <p className="text-sm text-muted-foreground">
                            {orders.meta.total} commande
                            {orders.meta.total !== 1 ? 's' : ''} enregistrée
                            {orders.meta.total !== 1 ? 's' : ''}.
                        </p>
                    </div>
                    <PdfExportButton
                        href={admin.orders.export({ query: { search, status } }).url}
                    />
                </div>

                <form
                    onSubmit={handleSearch}
                    className="flex flex-wrap items-center gap-2"
                >
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Référence, client, téléphone…"
                        className="w-full sm:w-72"
                        aria-label="Rechercher une commande"
                    />
                    <select
                        className={SELECT_CLASS}
                        value={status}
                        onChange={(e) => {
                            setStatus(e.target.value);
                            applyFilters({ status: e.target.value });
                        }}
                        aria-label="Filtrer par statut"
                    >
                        <option value="">Tous les statuts</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" variant="secondary">
                        Filtrer
                    </Button>
                </form>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {orders.data.length === 0 ? (
                        <EmptyState
                            icon={ReceiptText}
                            title="Aucune commande"
                            description="Aucune commande ne correspond à ces filtres."
                        />
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b border-sidebar-border/70 bg-muted/50 text-left text-xs text-muted-foreground dark:border-sidebar-border">
                                        <tr>
                                            <th className="px-4 py-2.5 font-medium">
                                                Référence
                                            </th>
                                            <th className="px-4 py-2.5 font-medium">
                                                Client
                                            </th>
                                            <th className="px-4 py-2.5 text-right font-medium">
                                                Articles
                                            </th>
                                            <th className="px-4 py-2.5 text-right font-medium">
                                                Total
                                            </th>
                                            <th className="px-4 py-2.5 font-medium">
                                                Statut
                                            </th>
                                            <th className="px-4 py-2.5 font-medium">
                                                Date
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                        {orders.data.map((order) => (
                                            <tr
                                                key={order.id}
                                                className="transition-colors hover:bg-muted/50"
                                            >
                                                <td className="px-4 py-3 font-medium">
                                                    <Link
                                                        href={admin.orders.show(
                                                            order.id,
                                                        )}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {order.reference}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <p>{order.customerName}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {order.customerPhone}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-3 text-right text-muted-foreground">
                                                    {order.itemsCount ?? 0}
                                                </td>
                                                <td className="px-4 py-3 text-right font-semibold">
                                                    {formatPrice(order.total)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <StatusBadge
                                                        status={order.status}
                                                        label={
                                                            order.statusLabel
                                                        }
                                                    />
                                                </td>
                                                <td className="px-4 py-3 text-xs text-muted-foreground">
                                                    {order.createdAt}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination
                                links={orders.meta.links}
                                from={orders.meta.from}
                                to={orders.meta.to}
                                total={orders.meta.total}
                            />
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

AdminOrders.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Commandes', href: admin.orders.index() },
    ],
};
