import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import type { Product } from '@/types/shop';

/** Au-delà, le tableau ne tiendrait plus sur un écran de téléphone. */
const MAX_ITEMS = 3;

type ComparisonContextValue = {
    items: Product[];
    toggleCompare: (product: Product) => void;
    isComparing: (id: number) => boolean;
    clear: () => void;
    isOpen: boolean;
    setIsOpen: (open: boolean) => void;
};

const ComparisonContext = createContext<ComparisonContextValue | null>(null);

/**
 * Sélection de produits à comparer côte à côte.
 *
 * Purement en mémoire, comme le panier : elle vit le temps de la visite et
 * n'a aucune raison de survivre à un rechargement complet de la page, ni
 * d'exiger un compte pour comparer deux robinets avant d'acheter.
 */
export function ComparisonProvider({ children }: { children: ReactNode }) {
    const [items, setItems] = useState<Product[]>([]);
    const [isOpen, setIsOpen] = useState(false);

    const toggleCompare = useCallback((product: Product) => {
        setItems((prev) => {
            const already = prev.some((item) => item.id === product.id);

            if (already) {
                return prev.filter((item) => item.id !== product.id);
            }

            if (prev.length >= MAX_ITEMS) {
                // Retirer le plus ancien plutôt que de bloquer : un client
                // qui compare une quatrième fiche veut clairement la voir,
                // pas lire un message d'erreur.
                return [...prev.slice(1), product];
            }

            return [...prev, product];
        });
    }, []);

    const isComparing = useCallback(
        (id: number) => items.some((item) => item.id === id),
        [items],
    );

    const clear = useCallback(() => {
        setItems([]);
        setIsOpen(false);
    }, []);

    const value = useMemo<ComparisonContextValue>(
        () => ({ items, toggleCompare, isComparing, clear, isOpen, setIsOpen }),
        [items, toggleCompare, isComparing, clear, isOpen],
    );

    return (
        <ComparisonContext.Provider value={value}>
            {children}
        </ComparisonContext.Provider>
    );
}

export function useComparison(): ComparisonContextValue {
    const ctx = useContext(ComparisonContext);

    if (!ctx) {
        throw new Error(
            'useComparison must be used inside ComparisonProvider',
        );
    }

    return ctx;
}

export { MAX_ITEMS as COMPARISON_MAX_ITEMS };
