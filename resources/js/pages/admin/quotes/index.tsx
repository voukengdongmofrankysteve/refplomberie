import { Head, router } from '@inertiajs/react';
import { Download, FileText, ShoppingCart, Trash2 } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import PdfExportButton from '@/components/dashboard/pdf-export-button';
import StatusBadge from '@/components/dashboard/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';
import type { PaginatedResource, Quote, StatusOption } from '@/types/shop';

type Props = {
    quotes: PaginatedResource<Quote>;
    statuses: StatusOption[];
    filters: { search: string; status: string };
};

export default function AdminQuotes({ quotes, statuses, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    // Le détail des lignes s'ouvre à la demande : la liste reste lisible.
    const [expanded, setExpanded] = useState<number | null>(null);

    const applyFilters = (next: Partial<typeof filters>) => {
        router.get(
            admin.quotes.index().url,
            { ...filters, ...next },
            { preserveState: true, replace: true },
        );
    };

    const setStatus = (quote: Quote, status: string) => {
        router.put(
            admin.quotes.update(quote.id).url,
            { status, note: quote.note ?? '' },
            { preserveScroll: true },
        );
    };

    const convert = (quote: Quote) => {
        if (
            window.confirm(
                `Créer une commande à partir du devis ${quote.reference} ?`,
            )
        ) {
            router.post(admin.quotes.convert(quote.id).url);
        }
    };

    const destroy = (quote: Quote) => {
        if (window.confirm(`Supprimer le devis ${quote.reference} ?`)) {
            router.delete(admin.quotes.destroy(quote.id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Devis" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">Devis</h1>
                        <p className="text-sm text-muted-foreground">
                            {quotes.meta.total} devis établi
                            {quotes.meta.total !== 1 ? 's' : ''} depuis la
                            vitrine.
                        </p>
                    </div>
                    <PdfExportButton
                        href={admin.quotes.export({ query: filters }).url}
                    />
                </div>

                {/* Filtres */}
                <div className="flex flex-wrap gap-2">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters({ search });
                        }}
                        className="flex flex-1 gap-2 sm:max-w-sm"
                    >
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Référence, client, société…"
                        />
                        <Button type="submit" variant="secondary">
                            Filtrer
                        </Button>
                    </form>

                    <select
                        value={filters.status}
                        onChange={(e) =>
                            applyFilters({ status: e.target.value })
                        }
                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:outline-none"
                        aria-label="Filtrer par statut"
                    >
                        <option value="">Tous les statuts</option>
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </select>
                </div>

                <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {quotes.data.length === 0 ? (
                        <EmptyState
                            icon={FileText}
                            title="Aucun devis"
                            description="Les devis demandés depuis le panier apparaîtront ici."
                        />
                    ) : (
                        <>
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {quotes.data.map((quote) => (
                                    <li key={quote.id} className="p-4">
                                        <div className="flex flex-wrap items-start gap-3">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setExpanded((id) =>
                                                        id === quote.id
                                                            ? null
                                                            : quote.id,
                                                    )
                                                }
                                                className="min-w-0 flex-1 text-left"
                                            >
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-mono text-sm font-semibold">
                                                        {quote.reference}
                                                    </span>
                                                    <StatusBadge
                                                        status={quote.status}
                                                        label={
                                                            quote.statusLabel
                                                        }
                                                    />
                                                    {quote.isExpired && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="bg-muted text-muted-foreground"
                                                        >
                                                            Validité dépassée
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="mt-1 truncate text-xs text-muted-foreground">
                                                    {quote.customerCompany
                                                        ? `${quote.customerCompany} — `
                                                        : ''}
                                                    {quote.customerName} ·{' '}
                                                    {quote.customerPhone} ·{' '}
                                                    {quote.createdAt}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Valable jusqu’au{' '}
                                                    {quote.validUntil} ·{' '}
                                                    {quote.items?.length ?? 0}{' '}
                                                    ligne
                                                    {(quote.items?.length ??
                                                        0) !== 1
                                                        ? 's'
                                                        : ''}
                                                </p>
                                            </button>

                                            <div className="shrink-0 text-right">
                                                <p className="text-sm font-semibold">
                                                    {formatPrice(quote.total)}
                                                </p>
                                            </div>

                                            <div className="flex shrink-0 flex-wrap items-center gap-1">
                                                <select
                                                    value={quote.status}
                                                    onChange={(e) =>
                                                        setStatus(
                                                            quote,
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="h-8 rounded-md border border-input bg-transparent px-2 text-xs focus-visible:outline-none"
                                                    aria-label={`Statut du devis ${quote.reference}`}
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

                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                    aria-label={`Télécharger le devis ${quote.reference}`}
                                                >
                                                    <a href={quote.pdfUrl}>
                                                        <Download className="size-4" />
                                                    </a>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        convert(quote)
                                                    }
                                                    aria-label={`Convertir ${quote.reference} en commande`}
                                                >
                                                    <ShoppingCart className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        destroy(quote)
                                                    }
                                                    aria-label={`Supprimer ${quote.reference}`}
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </div>

                                        {expanded === quote.id && (
                                            <div className="mt-3 rounded-lg border border-sidebar-border/70 bg-muted/40 p-3 dark:border-sidebar-border">
                                                <ul className="space-y-1.5 text-xs">
                                                    {quote.items?.map(
                                                        (item) => (
                                                            <li
                                                                key={item.id}
                                                                className="flex justify-between gap-3"
                                                            >
                                                                <span className="min-w-0 truncate">
                                                                    {
                                                                        item.productName
                                                                    }{' '}
                                                                    ×{' '}
                                                                    {
                                                                        item.quantity
                                                                    }
                                                                </span>
                                                                <span className="shrink-0 font-medium">
                                                                    {formatPrice(
                                                                        item.lineTotal,
                                                                    )}
                                                                </span>
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                                <div className="mt-2 flex justify-between border-t border-sidebar-border/70 pt-2 text-xs dark:border-sidebar-border">
                                                    <span className="text-muted-foreground">
                                                        Livraison
                                                    </span>
                                                    <span>
                                                        {quote.shipping === 0
                                                            ? 'Offerte'
                                                            : formatPrice(
                                                                  quote.shipping,
                                                              )}
                                                    </span>
                                                </div>
                                                {quote.note && (
                                                    <p className="mt-2 text-xs text-muted-foreground">
                                                        <span className="font-medium">
                                                            Observations :
                                                        </span>{' '}
                                                        {quote.note}
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>

                            <Pagination
                                links={quotes.meta.links}
                                from={quotes.meta.from}
                                to={quotes.meta.to}
                                total={quotes.meta.total}
                            />
                        </>
                    )}
                </section>
            </div>
        </>
    );
}

AdminQuotes.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Devis', href: admin.quotes.index() },
    ],
};
