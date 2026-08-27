import { Head, router } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import PdfExportButton from '@/components/dashboard/pdf-export-button';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';
import type { Paginated, StatusOption } from '@/types/shop';

type Customer = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: string;
    roleLabel: string;
    ordersCount: number;
    favoritesCount: number;
    requestsCount: number;
    spent: number;
    createdAt: string;
};

const SELECT_CLASS =
    'h-8 rounded-md border border-input bg-transparent px-2 text-xs shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function AdminCustomers({
    customers,
    roles,
    filters,
}: {
    customers: Paginated<Customer>;
    roles: StatusOption[];
    filters: { search: string };
}) {
    const [search, setSearch] = useState(filters.search);

    const handleSearch = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        router.get(
            admin.customers.index().url,
            { search },
            { preserveState: true, replace: true },
        );
    };

    const updateRole = (customer: Customer, role: string) =>
        router.put(
            admin.customers.update(customer.id).url,
            { role },
            { preserveScroll: true },
        );

    return (
        <>
            <Head title="Comptes" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">Comptes</h1>
                        <p className="text-sm text-muted-foreground">
                            {customers.total} compte
                            {customers.total !== 1 ? 's' : ''} enregistré
                            {customers.total !== 1 ? 's' : ''}.
                        </p>
                    </div>
                    <PdfExportButton
                        href={admin.customers.export({ query: { search } }).url}
                    />
                </div>

                <form onSubmit={handleSearch} className="flex flex-wrap gap-2">
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Nom ou email…"
                        className="w-full sm:w-72"
                        aria-label="Rechercher un compte"
                    />
                    <Button type="submit" variant="secondary">
                        Rechercher
                    </Button>
                </form>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {customers.data.length === 0 ? (
                        <EmptyState
                            icon={Users}
                            title="Aucun compte"
                            description="Aucun compte ne correspond à cette recherche."
                        />
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b border-sidebar-border/70 bg-muted/50 text-left text-xs text-muted-foreground dark:border-sidebar-border">
                                        <tr>
                                            <th className="px-4 py-2.5 font-medium">
                                                Compte
                                            </th>
                                            <th className="px-4 py-2.5 text-right font-medium">
                                                Commandes
                                            </th>
                                            <th className="px-4 py-2.5 text-right font-medium">
                                                Dépensé
                                            </th>
                                            <th className="px-4 py-2.5 text-right font-medium">
                                                Favoris
                                            </th>
                                            <th className="px-4 py-2.5 text-right font-medium">
                                                Interventions
                                            </th>
                                            <th className="px-4 py-2.5 font-medium">
                                                Rôle
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                        {customers.data.map((customer) => (
                                            <tr key={customer.id}>
                                                <td className="px-4 py-3">
                                                    <p className="font-medium">
                                                        {customer.name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {customer.email}
                                                        {customer.phone
                                                            ? ` · ${customer.phone}`
                                                            : ''}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        Inscrit le{' '}
                                                        {customer.createdAt}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {customer.ordersCount}
                                                </td>
                                                <td className="px-4 py-3 text-right font-medium">
                                                    {formatPrice(
                                                        customer.spent,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right text-muted-foreground">
                                                    {customer.favoritesCount}
                                                </td>
                                                <td className="px-4 py-3 text-right text-muted-foreground">
                                                    {customer.requestsCount}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <select
                                                        className={SELECT_CLASS}
                                                        value={customer.role}
                                                        onChange={(e) =>
                                                            updateRole(
                                                                customer,
                                                                e.target.value,
                                                            )
                                                        }
                                                        aria-label={`Rôle de ${customer.name}`}
                                                    >
                                                        {roles.map((role) => (
                                                            <option
                                                                key={role.value}
                                                                value={
                                                                    role.value
                                                                }
                                                            >
                                                                {role.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination
                                links={customers.links}
                                from={customers.from}
                                to={customers.to}
                                total={customers.total}
                            />
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

AdminCustomers.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Comptes', href: admin.customers.index() },
    ],
};
