import { Head, Link } from '@inertiajs/react';
import { ReceiptText } from 'lucide-react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import StatusBadge from '@/components/dashboard/status-badge';
import { formatPrice } from '@/lib/shop';
import { dashboard, home } from '@/routes';
import * as account from '@/routes/account';
import type { Order, PaginatedResource } from '@/types/shop';

export default function AccountOrders({
    orders,
}: {
    orders: PaginatedResource<Order>;
}) {
    return (
        <>
            <Head title="Mes commandes" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">Mes commandes</h1>
                    <p className="text-sm text-muted-foreground">
                        Suivez l’avancement de chacune de vos commandes.
                    </p>
                </div>

                {orders.data.length === 0 ? (
                    <div className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <EmptyState
                            icon={ReceiptText}
                            title="Aucune commande"
                            description="Passez votre première commande depuis le catalogue."
                            action={
                                <Link
                                    href={home()}
                                    className="rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground"
                                >
                                    Parcourir le catalogue
                                </Link>
                            }
                        />
                    </div>
                ) : (
                    <div className="space-y-4">
                        {orders.data.map((order) => (
                            <article
                                key={order.id}
                                className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border"
                            >
                                <header className="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                                    <div>
                                        <p className="text-sm font-semibold">
                                            {order.reference}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Passée le {order.createdAt}
                                        </p>
                                    </div>
                                    <StatusBadge
                                        status={order.status}
                                        label={order.statusLabel}
                                    />
                                </header>

                                <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                    {(order.items ?? []).map((item) => (
                                        <li
                                            key={item.id}
                                            className="flex items-center justify-between gap-3 px-4 py-2.5 text-sm"
                                        >
                                            <span className="min-w-0 flex-1 truncate">
                                                {item.productName}
                                            </span>
                                            <span className="shrink-0 text-muted-foreground">
                                                {item.quantity} ×{' '}
                                                {formatPrice(item.unitPrice)}
                                            </span>
                                            <span className="w-28 shrink-0 text-right font-medium">
                                                {formatPrice(item.lineTotal)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>

                                <footer className="space-y-1 border-t border-sidebar-border/70 px-4 py-3 text-sm dark:border-sidebar-border">
                                    <div className="flex justify-between text-muted-foreground">
                                        <span>Sous-total</span>
                                        <span>
                                            {formatPrice(order.subtotal)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-muted-foreground">
                                        <span>Livraison</span>
                                        <span>
                                            {order.shipping === 0
                                                ? 'Gratuite'
                                                : formatPrice(order.shipping)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between font-semibold">
                                        <span>Total</span>
                                        <span>{formatPrice(order.total)}</span>
                                    </div>
                                    {order.customerAddress && (
                                        <p className="pt-1 text-xs text-muted-foreground">
                                            Livraison : {order.customerAddress}
                                        </p>
                                    )}
                                </footer>
                            </article>
                        ))}

                        <div className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                            <Pagination
                                links={orders.meta.links}
                                from={orders.meta.from}
                                to={orders.meta.to}
                                total={orders.meta.total}
                            />
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

AccountOrders.layout = {
    breadcrumbs: [
        { title: 'Mon espace', href: dashboard() },
        { title: 'Mes commandes', href: account.orders() },
    ],
};
