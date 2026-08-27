import { Head, Link, router } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import EmptyState from '@/components/dashboard/empty-state';
import { Button } from '@/components/ui/button';
import { formatPrice, productUrl } from '@/lib/shop';
import { dashboard, home } from '@/routes';
import * as account from '@/routes/account';
import { toggle } from '@/routes/favorites';
import type { Product } from '@/types/shop';

export default function AccountFavorites({
    favorites,
}: {
    favorites: Product[];
}) {
    const removeFavorite = (product: Product) =>
        router.post(toggle.url(product.slug), {}, { preserveScroll: true });

    return (
        <>
            <Head title="Mes favoris" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">Mes favoris</h1>
                    <p className="text-sm text-muted-foreground">
                        {favorites.length} produit
                        {favorites.length !== 1 ? 's' : ''} enregistré
                        {favorites.length !== 1 ? 's' : ''} sur votre compte.
                    </p>
                </div>

                {favorites.length === 0 ? (
                    <div className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <EmptyState
                            icon={Heart}
                            title="Aucun favori"
                            description="Cliquez sur le cœur d’un produit du catalogue pour le retrouver ici."
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
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {favorites.map((product) => (
                            <article
                                key={product.id}
                                className="flex flex-col overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border"
                            >
                                <Link href={productUrl(product.slug)}>
                                    <img
                                        src={product.img}
                                        alt={product.name}
                                        className="aspect-[4/3] w-full object-cover"
                                    />
                                </Link>
                                <div className="flex flex-1 flex-col p-4">
                                    <p className="text-xs text-muted-foreground">
                                        {product.categoryLabel}
                                    </p>
                                    <Link
                                        href={productUrl(product.slug)}
                                        className="mt-1 line-clamp-2 text-sm font-semibold hover:text-primary"
                                    >
                                        {product.name}
                                    </Link>
                                    <p className="mt-2 text-base font-bold">
                                        {formatPrice(product.price)}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {product.stock > 0
                                            ? `${product.stock} en stock`
                                            : 'Rupture de stock'}
                                    </p>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="mt-4"
                                        onClick={() => removeFavorite(product)}
                                    >
                                        Retirer des favoris
                                    </Button>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

AccountFavorites.layout = {
    breadcrumbs: [
        { title: 'Mon espace', href: dashboard() },
        { title: 'Mes favoris', href: account.favorites() },
    ],
};
