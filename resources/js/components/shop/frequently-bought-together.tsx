import { useState } from 'react';
import { useCart } from '@/contexts/cart-context';
import { formatPrice, productUrl } from '@/lib/shop';
import type { Product } from '@/types/shop';

/**
 * « Fréquemment achetés ensemble » — fondé sur les commandes passées, pas sur
 * la catégorie : un tuyau et son collier de serrage se retrouvent ici même
 * s'ils ne partagent aucun rayon.
 *
 * Tout est coché par défaut, comme chez les grandes enseignes : le client
 * décoche ce qu'il ne veut pas plutôt que de devoir tout sélectionner.
 */
export default function FrequentlyBoughtTogether({
    items,
}: {
    items: Product[];
}) {
    const { addItem, setIsOpen: openCart } = useCart();
    const [checked, setChecked] = useState<Set<number>>(
        () => new Set(items.map((item) => item.id)),
    );

    if (items.length === 0) {
        return null;
    }

    const selected = items.filter((item) => checked.has(item.id));
    const total = selected.reduce((sum, item) => sum + item.price, 0);

    const toggle = (id: number) => {
        setChecked((prev) => {
            const next = new Set(prev);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });
    };

    const addSelection = () => {
        selected.forEach((item) => addItem(item));
        openCart(true);
    };

    return (
        <div className="mt-8 rounded-2xl border border-[#E9ECEF] bg-[#F8F9FA] p-5">
            <h3 className="mb-4 font-display text-xl font-bold text-[#1A1A2E]">
                Souvent achetés ensemble
            </h3>

            <div className="flex flex-wrap gap-4">
                {items.map((item) => (
                    <label
                        key={item.id}
                        className="flex w-[calc(50%-8px)] cursor-pointer items-start gap-3 rounded-xl border border-[#E9ECEF] bg-white p-3 transition-colors hover:border-[#25D366] sm:w-40 sm:flex-col"
                    >
                        <input
                            type="checkbox"
                            checked={checked.has(item.id)}
                            onChange={() => toggle(item.id)}
                            className="mt-1 size-4 shrink-0 accent-[#25D366] sm:mt-0 sm:self-end"
                        />
                        <a
                            href={productUrl(item.slug)}
                            className="flex min-w-0 flex-1 items-center gap-3 sm:flex-col sm:items-start"
                            // Le clic sur l'image ouvre la fiche ; celui sur la
                            // case au-dessus ne doit pas déclencher cette
                            // navigation.
                            onClick={(e) => e.stopPropagation()}
                        >
                            <img
                                src={item.img}
                                alt={item.name}
                                className="size-14 shrink-0 rounded-lg object-cover sm:aspect-square sm:size-full"
                            />
                            <span className="min-w-0">
                                <span className="line-clamp-2 block text-sm font-medium text-[#1A1A2E]">
                                    {item.name}
                                </span>
                                <span className="mt-0.5 block text-sm font-bold text-[#25D366]">
                                    {formatPrice(item.price)}
                                </span>
                            </span>
                        </a>
                    </label>
                ))}
            </div>

            <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[#E9ECEF] pt-4">
                <p className="text-sm text-[#4A4A6A]">
                    {selected.length} article{selected.length > 1 ? 's' : ''}{' '}
                    sélectionné{selected.length > 1 ? 's' : ''} —{' '}
                    <span className="font-bold text-[#1A1A2E]">
                        {formatPrice(total)}
                    </span>
                </p>
                <button
                    type="button"
                    onClick={addSelection}
                    disabled={selected.length === 0}
                    className="rounded-xl bg-[#25D366] px-5 py-2.5 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Ajouter la sélection au panier
                </button>
            </div>
        </div>
    );
}
