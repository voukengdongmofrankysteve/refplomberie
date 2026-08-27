import { usePage } from '@inertiajs/react';
import { createContext, useContext, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import type { User } from '@/types/auth';

export type AuthModalMode = 'login' | 'register';

type AuthModalContextValue = {
    /** Utilisateur authentifié, partagé par HandleInertiaRequests. */
    user: User | null;
    /** Mode courant de la modale, `null` quand elle est fermée. */
    authModal: AuthModalMode | null;
    setAuthModal: (mode: AuthModalMode | null) => void;
};

const AuthModalContext = createContext<AuthModalContextValue | null>(null);

export function AuthModalProvider({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;
    const [authModal, setAuthModal] = useState<AuthModalMode | null>(null);

    const user = (auth?.user ?? null) as User | null;

    const value = useMemo<AuthModalContextValue>(
        () => ({ user, authModal, setAuthModal }),
        [user, authModal],
    );

    return (
        <AuthModalContext.Provider value={value}>
            {children}
        </AuthModalContext.Provider>
    );
}

export function useShopAuth(): AuthModalContextValue {
    const ctx = useContext(AuthModalContext);

    if (!ctx) {
        throw new Error('useShopAuth must be used inside AuthModalProvider');
    }

    return ctx;
}
