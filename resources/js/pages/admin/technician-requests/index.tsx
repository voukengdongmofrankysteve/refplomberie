import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import StatusBadge from '@/components/dashboard/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import admin from '@/routes/admin';
import type {
    PaginatedResource,
    StatusOption,
    TechnicianRequest,
} from '@/types/shop';

type Props = {
    requests: PaginatedResource<TechnicianRequest>;
    statuses: StatusOption[];
    technicians: { value: number; label: string; available: boolean }[];
    filters: { search: string; status: string };
};

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function AdminTechnicianRequests({
    requests,
    statuses,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);

    const applyFilters = (next: { search?: string; status?: string }) =>
        router.get(
            admin.technicianRequests.index().url,
            { search, status, ...next },
            { preserveState: true, replace: true },
        );

    const handleSearch = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        applyFilters({});
    };

    return (
        <>
            <Head title="Demandes d’intervention" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">
                        Demandes d’intervention
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {requests.meta.total} demande
                        {requests.meta.total !== 1 ? 's' : ''} reçue
                        {requests.meta.total !== 1 ? 's' : ''}.
                    </p>
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
                        aria-label="Rechercher une demande"
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
                    {requests.data.length === 0 ? (
                        <EmptyState
                            icon={ClipboardList}
                            title="Aucune demande"
                            description="Aucune demande ne correspond à ces filtres."
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
                                            <th className="px-4 py-2.5 font-medium">
                                                Service
                                            </th>
                                            <th className="px-4 py-2.5 font-medium">
                                                Technicien
                                            </th>
                                            <th className="px-4 py-2.5 font-medium">
                                                Statut
                                            </th>
                                            <th className="px-4 py-2.5 font-medium">
                                                Reçue le
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                        {requests.data.map((request) => (
                                            <tr
                                                key={request.id}
                                                className="transition-colors hover:bg-muted/50"
                                            >
                                                <td className="px-4 py-3 font-medium">
                                                    <Link
                                                        href={admin.technicianRequests.show(
                                                            request.id,
                                                        )}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {request.reference}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <p>
                                                        {request.customerName}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {request.customerPhone}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {request.service}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {request.technicianName ?? (
                                                        <span className="text-amber-600">
                                                            À assigner
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <StatusBadge
                                                        status={request.status}
                                                        label={
                                                            request.statusLabel
                                                        }
                                                    />
                                                </td>
                                                <td className="px-4 py-3 text-xs text-muted-foreground">
                                                    {request.createdAt}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination
                                links={requests.meta.links}
                                from={requests.meta.from}
                                to={requests.meta.to}
                                total={requests.meta.total}
                            />
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

AdminTechnicianRequests.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Interventions', href: admin.technicianRequests.index() },
    ],
};
