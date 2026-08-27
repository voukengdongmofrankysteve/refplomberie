import {
    COMPARISON_MAX_ITEMS,
    useComparison,
} from '@/contexts/comparison-context';

/**
 * Bandeau flottant qui accompagne la sélection en cours.
 *
 * N'apparaît qu'une fois un premier produit ajouté : proposer de comparer
 * une seule fiche n'a pas de sens, mais montrer qu'on est en train d'en
 * construire une le rassure sur ce que fait le bouton « Comparer ».
 */
export default function ComparisonBar() {
    const { items, toggleCompare, clear, setIsOpen } = useComparison();

    if (items.length === 0) {
        return null;
    }

    return (
        <div className="fixed inset-x-0 bottom-0 z-40 border-t border-[#E9ECEF] bg-white/95 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] backdrop-blur-sm">
            <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 md:px-8">
                <div className="flex items-center gap-3">
                    <div className="flex -space-x-2">
                        {items.map((item) => (
                            <button
                                key={item.id}
                                onClick={() => toggleCompare(item)}
                                title={`Retirer ${item.name} de la comparaison`}
                                className="group relative size-10 shrink-0 overflow-hidden rounded-lg border-2 border-white shadow"
                            >
                                <img
                                    src={item.img}
                                    alt={item.name}
                                    className="h-full w-full object-cover"
                                />
                                <span className="absolute inset-0 flex items-center justify-center bg-black/60 text-white opacity-0 transition-opacity group-hover:opacity-100">
                                    ✕
                                </span>
                            </button>
                        ))}
                    </div>
                    <p className="text-sm text-[#4A4A6A]">
                        <span className="font-bold text-[#1A1A2E]">
                            {items.length}
                        </span>{' '}
                        / {COMPARISON_MAX_ITEMS} produits à comparer
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        onClick={clear}
                        className="text-sm font-semibold text-[#4A4A6A] underline-offset-2 hover:underline"
                    >
                        Vider
                    </button>
                    <button
                        onClick={() => setIsOpen(true)}
                        disabled={items.length < 2}
                        className="rounded-xl bg-[#25D366] px-5 py-2.5 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Comparer
                    </button>
                </div>
            </div>
        </div>
    );
}
