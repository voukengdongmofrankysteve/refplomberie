import { Head, router } from '@inertiajs/react';
import { Download, TrendingDown, TrendingUp, Wallet } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import EmptyState from '@/components/dashboard/empty-state';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';

type Summary = {
    revenue: number;
    costs: number;
    margin: number;
    ordersCount: number;
    purchaseOrdersCount: number;
};

type SeriesPoint = { label: string; revenue: number; costs: number };

type LedgerRow = {
    date: string;
    journal: string;
    reference: string;
    party: string;
    label: string;
    debit: number;
    credit: number;
};

type Props = {
    period: { key: string; label: string; from: string; to: string };
    periods: { value: string; label: string }[];
    summary: Summary;
    series: SeriesPoint[];
    ledger: LedgerRow[];
};

function StatCard({
    label,
    value,
    hint,
    icon: Icon,
}: {
    label: string;
    value: number;
    hint: string;
    icon: LucideIcon;
}) {
    return (
        <div className="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
            <div className="flex items-center justify-between">
                <p className="text-sm text-muted-foreground">{label}</p>
                <Icon className="size-4 text-muted-foreground" />
            </div>
            <p className="mt-2 text-2xl font-bold">{formatPrice(value)}</p>
            <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
        </div>
    );
}

export default function AdminAccounting({
    period,
    periods,
    summary,
    series,
    ledger,
}: Props) {
    return (
        <>
            <Head title="Comptabilité" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                <header className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">
                            Comptabilité
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Ventes et achats du {period.from} au {period.to}.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <select
                            value={period.key}
                            onChange={(event) =>
                                router.get(
                                    admin.accounting.index().url,
                                    { periode: event.target.value },
                                    { preserveScroll: true },
                                )
                            }
                            className="h-9 rounded-lg border border-sidebar-border/70 bg-background px-3 text-sm dark:border-sidebar-border"
                            aria-label="Période"
                        >
                            {periods.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>

                        <a
                            href={
                                admin.accounting.export({
                                    query: { periode: period.key },
                                }).url
                            }
                            className="flex h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                            title="Journal comptable de la période, au format CSV"
                        >
                            <Download className="size-4" />
                            Export comptable
                        </a>
                    </div>
                </header>

                <section className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        label="Chiffre d’affaires"
                        value={summary.revenue}
                        hint={`${summary.ordersCount} commande${summary.ordersCount !== 1 ? 's' : ''}`}
                        icon={TrendingUp}
                    />
                    <StatCard
                        label="Achats"
                        value={summary.costs}
                        hint={`${summary.purchaseOrdersCount} bon${summary.purchaseOrdersCount !== 1 ? 's' : ''} reçu${summary.purchaseOrdersCount !== 1 ? 's' : ''}`}
                        icon={TrendingDown}
                    />
                    <StatCard
                        label="Marge brute"
                        value={summary.margin}
                        hint="Chiffre d’affaires moins achats"
                        icon={Wallet}
                    />
                </section>

                <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {series.length === 0 ? (
                        <EmptyState
                            icon={Wallet}
                            title="Aucun mouvement"
                            description="Aucune vente ni aucun achat reçu sur cette période."
                        />
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b border-sidebar-border/70 text-left text-xs text-muted-foreground dark:border-sidebar-border">
                                <tr>
                                    <th className="p-3 font-medium">
                                        Période
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Ventes
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Achats
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-sidebar-border/50">
                                {series.map((point) => (
                                    <tr key={point.label}>
                                        <td className="p-3">
                                            {point.label}
                                        </td>
                                        <td className="p-3 text-right">
                                            {formatPrice(point.revenue)}
                                        </td>
                                        <td className="p-3 text-right">
                                            {formatPrice(point.costs)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </section>

                <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    <h2 className="border-b border-sidebar-border/70 p-3 text-sm font-semibold dark:border-sidebar-border">
                        Journal comptable
                    </h2>
                    {ledger.length === 0 ? (
                        <p className="p-8 text-center text-sm text-muted-foreground">
                            Aucune écriture pour l’instant.
                        </p>
                    ) : (
                        <div className="max-h-96 overflow-y-auto">
                            <table className="w-full text-sm">
                                <thead className="sticky top-0 border-b border-sidebar-border/70 bg-card text-left text-xs text-muted-foreground dark:border-sidebar-border">
                                    <tr>
                                        <th className="p-3 font-medium">
                                            Date
                                        </th>
                                        <th className="p-3 font-medium">
                                            Pièce
                                        </th>
                                        <th className="p-3 font-medium">
                                            Tiers
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Débit
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Crédit
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-sidebar-border/50">
                                    {ledger.map((row, index) => (
                                        <tr key={`${row.reference}-${index}`}>
                                            <td className="p-3 text-xs text-muted-foreground">
                                                {row.date}
                                            </td>
                                            <td className="p-3">
                                                {row.reference}
                                            </td>
                                            <td className="p-3">
                                                {row.party}
                                            </td>
                                            <td className="p-3 text-right">
                                                {row.debit > 0
                                                    ? formatPrice(row.debit)
                                                    : '—'}
                                            </td>
                                            <td className="p-3 text-right">
                                                {row.credit > 0
                                                    ? formatPrice(row.credit)
                                                    : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

AdminAccounting.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Comptabilité', href: admin.accounting.index() },
    ],
};
