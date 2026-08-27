import { Head, Link } from '@inertiajs/react';
import { Heart, ReceiptText, Wallet, Wrench } from 'lucide-react';
import EmptyState from '@/components/dashboard/empty-state';
import StatCard from '@/components/dashboard/stat-card';
import StatusBadge from '@/components/dashboard/status-badge';
import { formatPrice, productUrl } from '@/lib/shop';
import { dashboard, home } from '@/routes';
import * as account from '@/routes/account';
import type { Order, Product, TechnicianRequest } from '@/types/shop';

type Props = {
    stats: {
        favorites: number;
        orders: number;
        openRequests: number;
        totalSpent: number;
    };
    favorites: Product[];
    orders: Order[];
    requests: TechnicianRequest[];
};

export default function AccountDashboard({
    stats,
    favorites,
    orders,
    requests,
}: Props) {
    return (
        <>
            <Head title="Mon espace" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                {/* Chiffres clés */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    <StatCard
                        label="Favoris"
                        value={stats.favorites}
                        icon={Heart}
                    />
                    <StatCard
                        label="Commandes"
                        value={stats.orders}
                        icon={ReceiptText}
                    />
                    <StatCard
                        label="Interventions en cours"
                        value={stats.openRequests}
                        icon={Wrench}
                    />
                    <StatCard
                        label="Total dépensé"
                        value={formatPrice(stats.totalSpent)}
                        icon={Wallet}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Dernières commandes */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <header className="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                            <h2 className="text-sm font-semibold">
                                Dernières commandes
                            </h2>
                            <Link
                                href={account.orders()}
                                className="text-xs font-medium text-primary hover:underline"
                            >
                                Tout voir
                            </Link>
                        </header>

                        {orders.length === 0 ? (
                            <EmptyState
                                icon={ReceiptText}
                                title="Aucune commande"
                                description="Vos commandes apparaîtront ici une fois passées."
                                action={
                                    <Link
                                        href={home()}
                                        className="rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground"
                                    >
                                        Parcourir le catalogue
                                    </Link>
                                }
                            />
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {orders.map((order) => (
                                    <li
                                        key={order.id}
                                        className="flex items-center justify-between gap-3 px-4 py-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {order.reference}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {order.createdAt} ·{' '}
                                                {order.itemsCount ?? 0} article
                                                {(order.itemsCount ?? 0) > 1
                                                    ? 's'
                                                    : ''}
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
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    {/* Dernières interventions */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <header className="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                            <h2 className="text-sm font-semibold">
                                Mes demandes d’intervention
                            </h2>
                            <Link
                                href={account.technicianRequests()}
                                className="text-xs font-medium text-primary hover:underline"
                            >
                                Tout voir
                            </Link>
                        </header>

                        {requests.length === 0 ? (
                            <EmptyState
                                icon={Wrench}
                                title="Aucune demande"
                                description="Demandez un technicien pour une installation ou un dépannage."
                                action={
                                    <Link
                                        href={account.technicianRequests()}
                                        className="rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground"
                                    >
                                        Demander un technicien
                                    </Link>
                                }
                            />
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {requests.map((request) => (
                                    <li
                                        key={request.id}
                                        className="flex items-center justify-between gap-3 px-4 py-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {request.service}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {request.reference} ·{' '}
                                                {request.technicianName ??
                                                    'Technicien à assigner'}
                                            </p>
                                        </div>
                                        <StatusBadge
                                            status={request.status}
                                            label={request.statusLabel}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                {/* Favoris */}
                <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    <header className="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                        <h2 className="text-sm font-semibold">Mes favoris</h2>
                        <Link
                            href={account.favorites()}
                            className="text-xs font-medium text-primary hover:underline"
                        >
                            Tout voir
                        </Link>
                    </header>

                    {favorites.length === 0 ? (
                        <EmptyState
                            icon={Heart}
                            title="Aucun favori"
                            description="Cliquez sur le cœur d’un produit pour le retrouver ici."
                            action={
                                <Link
                                    href={home()}
                                    className="rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground"
                                >
                                    Parcourir le catalogue
                                </Link>
                            }
                        />
                    ) : (
                        <div className="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4">
                            {favorites.map((product) => (
                                <Link
                                    key={product.id}
                                    href={productUrl(product.slug)}
                                    className="overflow-hidden rounded-lg border border-sidebar-border/70 transition-colors hover:border-primary dark:border-sidebar-border"
                                >
                                    <img
                                        src={product.img}
                                        alt={product.name}
                                        className="aspect-[4/3] w-full object-cover"
                                    />
                                    <div className="p-3">
                                        <p className="line-clamp-1 text-sm font-medium">
                                            {product.name}
                                        </p>
                                        <p className="mt-1 text-xs font-semibold text-primary">
                                            {formatPrice(product.price)}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

AccountDashboard.layout = {
    breadcrumbs: [{ title: 'Mon espace', href: dashboard() }],
};
