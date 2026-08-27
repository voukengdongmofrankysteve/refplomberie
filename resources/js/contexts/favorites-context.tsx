import { router, usePage } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { useShopAuth } from '@/contexts/auth-modal-context';
import { toggle } from '@/routes/favorites';
import type { Product } from '@/types/shop';

type FavoritesContextValue = {
    /** Produits favoris du compte connecté, partagés par le serveur. */
    favorites: Product[];
    toggleFavorite: (product: Product) => void;
    isFavorite: (id: number) => boolean;
    isOpen: boolean;
    setIsOpen: (open: boolean) => void;
};

const FavoritesContext = createContext<FavoritesContextValue | null>(null);

export function FavoritesProvider({ children }: { children: ReactNode }) {
    const { favorites } = usePage().props;
    const { user, setAuthModal } = useShopAuth();
    const [isOpen, setIsOpen] = useState(false);

    /**
     * Les favoris vivent en base : un visiteur non connecté est invité à
     * s'authentifier plutôt que de perdre sa sélection.
     */
    const toggleFavorite = useCallback(
        (product: Product) => {
            if (!user) {
                setAuthModal('login');

                return;
            }

            // La route lie le produit par son slug.
            router.post(
                toggle.url(product.slug),
                {},
                { preserveScroll: true, preserveState: true },
            );
        },
        [user, setAuthModal],
    );

    const isFavorite = useCallback(
        (id: number) => favorites.some((product) => product.id === id),
        [favorites],
    );

    const value = useMemo<FavoritesContextValue>(
        () => ({ favorites, toggleFavorite, isFavorite, isOpen, setIsOpen }),
        [favorites, toggleFavorite, isFavorite, isOpen],
    );

    return (
        <FavoritesContext.Provider value={value}>
            {children}
        </FavoritesContext.Provider>
    );
}

export function useFavorites(): FavoritesContextValue {
    const ctx = useContext(FavoritesContext);

    if (!ctx) {
        throw new Error('useFavorites must be used inside FavoritesProvider');
    }

    return ctx;
}
