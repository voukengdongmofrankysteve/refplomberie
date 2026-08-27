import { Head, Link } from '@inertiajs/react';
import {
    ClipboardList,
    FileText,
    MessageSquare,
    Package,
    PackageX,
    ReceiptText,
    TriangleAlert,
    Users,
    Wallet,
} from 'lucide-react';
import StatCard from '@/components/dashboard/stat-card';
import StatusBadge from '@/components/dashboard/status-badge';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';
import type { LowStockProduct, Order, TechnicianRequest } from '@/types/shop';

type Props = {
    stats: {
        revenue: number;
        orders: number;
        pendingOrders: number;
        products: number;
        outOfStock: number;
        lowStock: number;
        pendingQuotes: number;
        customers: number;
        pendingRequests: number;
        newMessages: number;
    };
    lowStockProducts: LowStockProduct[];
    recentOrders: Order[];
    recentRequests: TechnicianRequest[];
    ordersByStatus: { status: string; label: string; count: number }[];
};

export default function AdminDashboard({
    stats,
    lowStockProducts,
    recentOrders,
    recentRequests,
    ordersByStatus,
}: Props) {
    const maxCount = Math.max(...ordersByStatus.map((s) => s.count), 1);

    return (
        <>
            <Head title="Administration" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                <div>
                    <h1 className="text-lg font-semibold">Vue d’ensemble</h1>
                    <p className="text-sm text-muted-foreground">
                        Activité de la boutique Réf. Plomberie.
                    </p>
                </div>

                {/* Alerte de réapprovisionnement */}
                {lowStockProducts.length > 0 && (
                    <section className="overflow-hidden rounded-xl border border-amber-300 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10">
                        <header className="flex items-center justify-between gap-3 border-b border-amber-300/70 px-4 py-3 dark:border-amber-500/30">
                            <h2 className="flex items-center gap-2 text-sm font-semibold text-amber-900 dark:text-amber-200">
                                <TriangleAlert className="h-4 w-4" />
                                {stats.lowStock} produit
                                {stats.lowStock > 1 ? 's' : ''} à recommander
                            </h2>
                            <Link
                                href={admin.products.index({
                                    query: { stock: 'low' },
                                })}
                                className="shrink-0 text-xs font-medium text-amber-900 underline-offset-2 hover:underline dark:text-amber-200"
                            >
                                Tout voir
                            </Link>
                        </header>

                        <ul className="divide-y divide-amber-300/60 dark:divide-amber-500/20">
                            {lowStockProducts.map((product) => (
                                <li key={product.id}>
                                    <Link
                                        href={admin.products.edit(product.slug)}
                                        className="flex items-center gap-3 px-4 py-2.5 transition-colors hover:bg-amber-100/70 dark:hover:bg-amber-500/15"
                                    >
                                        <img
                                            src={product.image}
                                            alt=""
                                            loading="lazy"
                                            className="h-9 w-9 shrink-0 rounded-md border border-amber-300/70 bg-white object-cover"
                                        />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium text-amber-950 dark:text-amber-100">
                                                {product.name}
                                            </p>
                                            <p className="truncate text-xs text-amber-800/80 dark:text-amber-200/70">
                                                {product.category}
                                            </p>
                                        </div>
                                        <span
                                            className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                product.level === 'out'
                                                    ? 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300'
                                                    : 'bg-amber-200 text-amber-900 dark:bg-amber-500/25 dark:text-amber-100'
                                            }`}
                                        >
                                            {product.level === 'out'
                                                ? 'Rupture'
                                                : `${product.stock} / ${product.threshold}`}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {/* Chiffres clés */}
                <div className="grid auto-rows-min gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="Chiffre d’affaires"
                        value={formatPrice(stats.revenue)}
                        hint="Commandes non annulées"
                        icon={Wallet}
                    />
                    <StatCard
                        label="Commandes"
                        value={stats.orders}
                        hint={`${stats.pendingOrders} en attente`}
                        icon={ReceiptText}
                    />
                    <StatCard
                        label="Produits"
                        value={stats.products}
                        hint={`${stats.outOfStock} en rupture`}
                        icon={stats.outOfStock > 0 ? PackageX : Package}
                    />
                    <StatCard
                        label="Stock à réapprovisionner"
                        value={stats.lowStock}
                        hint={`dont ${stats.outOfStock} en rupture`}
                        icon={stats.lowStock > 0 ? TriangleAlert : Package}
                    />
                    <StatCard
                        label="Devis à traiter"
                        value={stats.pendingQuotes}
                        icon={FileText}
                    />
                    <StatCard
                        label="Comptes"
                        value={stats.customers}
                        icon={Users}
                    />
                    <StatCard
                        label="Interventions à traiter"
                        value={stats.pendingRequests}
                        icon={ClipboardList}
                    />
                    <StatCard
                        label="Nouveaux messages"
                        value={stats.newMessages}
                        icon={MessageSquare}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Répartition par statut */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <header className="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                            <h2 className="text-sm font-semibold">
                                Commandes par statut
                            </h2>
                        </header>
                        <ul className="space-y-3 p-4">
                            {ordersByStatus.map((row) => (
                                <li key={row.status}>
                                    <div className="mb-1 flex items-center justify-between text-xs">
                                        <span className="text-muted-foreground">
                                            {row.label}
                                        </span>
                                        <span className="font-semibold">
                                            {row.count}
                                        </span>
                                    </div>
                                    <div
                                        className="h-1.5 rounded-full bg-muted"
                                        role="presentation"
                                    >
                                        <div
                                            className="h-1.5 rounded-full bg-primary"
                                            style={{
                                                width: `${(row.count / maxCount) * 100}%`,
                                            }}
                                        />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </section>

                    {/* Dernières commandes */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card lg:col-span-2 dark:border-sidebar-border">
                        <header className="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                            <h2 className="text-sm font-semibold">
                                Dernières commandes
                            </h2>
                            <Link
                                href={admin.orders.index()}
                                className="text-xs font-medium text-primary hover:underline"
                            >
                                Tout voir
                            </Link>
                        </header>

                        {recentOrders.length === 0 ? (
                            <p className="px-4 py-10 text-center text-sm text-muted-foreground">
                                Aucune commande pour le moment.
                            </p>
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {recentOrders.map((order) => (
                                    <li key={order.id}>
                                        <Link
                                            href={admin.orders.show(order.id)}
                                            className="flex items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/50"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    {order.reference}
                                                </p>
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {order.customerName} ·{' '}
                                                    {order.createdAt}
                                                </p>
                                            </div>
                                            <div className="flex shrink-0 items-center gap-3">
                                                <span className="text-sm font-semibold">
                                                    {formatPrice(order.total)}
                                                </span>
                                                <StatusBadge
                                                    status={order.status}
                                                    label={order.statusLabel}
                                                />
                                            </div>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                {/* Dernières interventions */}
                <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    <header className="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                        <h2 className="text-sm font-semibold">
                            Dernières demandes d’intervention
                        </h2>
                        <Link
                            href={admin.technicianRequests.index()}
                            className="text-xs font-medium text-primary hover:underline"
                        >
                            Tout voir
                        </Link>
                    </header>

                    {recentRequests.length === 0 ? (
                        <p className="px-4 py-10 text-center text-sm text-muted-foreground">
                            Aucune demande pour le moment.
                        </p>
                    ) : (
                        <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            {recentRequests.map((request) => (
                                <li key={request.id}>
                                    <Link
                                        href={admin.technicianRequests.show(
                                            request.id,
                                        )}
                                        className="flex items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/50"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {request.service}
                                            </p>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {request.customerName} ·{' '}
                                                {request.technicianName ??
                                                    'Technicien à assigner'}
                                            </p>
                                        </div>
                                        <StatusBadge
                                            status={request.status}
                                            label={request.statusLabel}
                                        />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Administration', href: admin.dashboard() }],
};
