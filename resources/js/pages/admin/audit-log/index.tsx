import { Head, router } from '@inertiajs/react';
import { History } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type { Paginated } from '@/types/shop';

type Change = { old: unknown; new: unknown };

type AuditLogEntry = {
    id: number;
    action: 'created' | 'updated' | 'deleted';
    actionLabel: string;
    admin: string;
    subject: { label: string; name: string | null; url: string | null };
    changes: Record<string, Change> | null;
    snapshot: Record<string, unknown> | null;
    createdAt: string;
};

type Props = {
    logs: Paginated<AuditLogEntry>;
    filters: { action: string; type: string; user: string };
    types: { value: string; label: string }[];
    admins: { value: string; label: string }[];
};

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

const ACTION_TONE: Record<AuditLogEntry['action'], string> = {
    created: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    updated: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    deleted: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
};

/** Représentation lisible d'une valeur auditée — booléens et vides compris. */
function displayValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'oui' : 'non';
    }

    return String(value);
}

export default function AdminAuditLog({
    logs,
    filters,
    types,
    admins,
}: Props) {
    const [action, setAction] = useState(filters.action);
    const [type, setType] = useState(filters.type);
    const [user, setUser] = useState(filters.user);
    const [expanded, setExpanded] = useState<number | null>(null);

    const applyFilters = (next: Partial<typeof filters>) =>
        router.get(
            admin.auditLog.index().url,
            { action, type, user, ...next },
            { preserveState: true, replace: true },
        );

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        applyFilters({});
    };

    return (
        <>
            <Head title="Journal d’audit" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">
                        Journal d’audit
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {logs.total} action{logs.total !== 1 ? 's' : ''}{' '}
                        enregistrée{logs.total !== 1 ? 's' : ''}. Créations,
                        modifications et suppressions faites par l’équipe.
                    </p>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="flex flex-wrap items-center gap-2"
                >
                    <select
                        className={SELECT_CLASS}
                        value={action}
                        onChange={(e) => {
                            setAction(e.target.value);
                            applyFilters({ action: e.target.value });
                        }}
                        aria-label="Filtrer par type d’action"
                    >
                        <option value="">Toutes les actions</option>
                        <option value="created">Créations</option>
                        <option value="updated">Modifications</option>
                        <option value="deleted">Suppressions</option>
                    </select>

                    <select
                        className={SELECT_CLASS}
                        value={type}
                        onChange={(e) => {
                            setType(e.target.value);
                            applyFilters({ type: e.target.value });
                        }}
                        aria-label="Filtrer par type de fiche"
                    >
                        <option value="">Toutes les fiches</option>
                        {types.map((t) => (
                            <option key={t.value} value={t.value}>
                                {t.label}
                            </option>
                        ))}
                    </select>

                    <select
                        className={SELECT_CLASS}
                        value={user}
                        onChange={(e) => {
                            setUser(e.target.value);
                            applyFilters({ user: e.target.value });
                        }}
                        aria-label="Filtrer par administrateur"
                    >
                        <option value="">Tous les administrateurs</option>
                        {admins.map((a) => (
                            <option key={a.value} value={a.value}>
                                {a.label}
                            </option>
                        ))}
                    </select>
                </form>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {logs.data.length === 0 ? (
                        <EmptyState
                            icon={History}
                            title="Aucune action enregistrée"
                            description="Rien ne correspond à ces filtres pour l’instant."
                        />
                    ) : (
                        <>
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {logs.data.map((log) => {
                                    const changeCount = log.changes
                                        ? Object.keys(log.changes).length
                                        : 0;
                                    const isExpanded = expanded === log.id;

                                    return (
                                        <li key={log.id} className="p-4">
                                            <div className="flex flex-wrap items-center gap-3">
                                                <Badge
                                                    variant="secondary"
                                                    className={
                                                        ACTION_TONE[
                                                            log.action
                                                        ]
                                                    }
                                                >
                                                    {log.actionLabel}
                                                </Badge>

                                                <span className="text-sm">
                                                    <span className="font-semibold">
                                                        {log.admin}
                                                    </span>{' '}
                                                    <span className="text-muted-foreground">
                                                        sur
                                                    </span>{' '}
                                                    {log.subject.url ? (
                                                        <a
                                                            href={
                                                                log.subject
                                                                    .url
                                                            }
                                                            className="font-semibold text-primary hover:underline"
                                                        >
                                                            {log.subject.label}
                                                            {log.subject
                                                                .name &&
                                                                ` « ${log.subject.name} »`}
                                                        </a>
                                                    ) : (
                                                        <span className="font-semibold">
                                                            {log.subject.label}
                                                            {log.subject
                                                                .name &&
                                                                ` « ${log.subject.name} »`}
                                                        </span>
                                                    )}
                                                </span>

                                                <span className="ml-auto shrink-0 text-xs text-muted-foreground">
                                                    {log.createdAt}
                                                </span>

                                                {changeCount > 0 && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            setExpanded(
                                                                isExpanded
                                                                    ? null
                                                                    : log.id,
                                                            )
                                                        }
                                                    >
                                                        {changeCount} champ
                                                        {changeCount > 1
                                                            ? 's'
                                                            : ''}{' '}
                                                        {isExpanded
                                                            ? '▲'
                                                            : '▼'}
                                                    </Button>
                                                )}
                                            </div>

                                            {isExpanded && log.changes && (
                                                <table className="mt-3 w-full text-xs">
                                                    <thead className="text-muted-foreground">
                                                        <tr>
                                                            <th className="pb-1 text-left font-medium">
                                                                Champ
                                                            </th>
                                                            <th className="pb-1 text-left font-medium">
                                                                Avant
                                                            </th>
                                                            <th className="pb-1 text-left font-medium">
                                                                Après
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-sidebar-border/50">
                                                        {Object.entries(
                                                            log.changes,
                                                        ).map(
                                                            ([
                                                                field,
                                                                change,
                                                            ]) => (
                                                                <tr
                                                                    key={
                                                                        field
                                                                    }
                                                                >
                                                                    <td className="py-1 pr-3 font-mono text-muted-foreground">
                                                                        {
                                                                            field
                                                                        }
                                                                    </td>
                                                                    <td className="py-1 pr-3 text-destructive">
                                                                        {displayValue(
                                                                            change.old,
                                                                        )}
                                                                    </td>
                                                                    <td className="py-1 text-green-700 dark:text-green-400">
                                                                        {displayValue(
                                                                            change.new,
                                                                        )}
                                                                    </td>
                                                                </tr>
                                                            ),
                                                        )}
                                                    </tbody>
                                                </table>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>

                            <Pagination
                                links={logs.links}
                                from={logs.from}
                                to={logs.to}
                                total={logs.total}
                            />
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

AdminAuditLog.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Journal d’audit', href: admin.auditLog.index() },
    ],
};
