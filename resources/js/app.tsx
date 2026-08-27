import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import ShopLayout from '@/layouts/shop-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Réf. Plomberie';

createInertiaApp({
    // Les pages vitrine fournissent un titre déjà complet (titre SEO du
    // back-office, nom du produit) : on n'y ajoute pas une seconde fois la marque.
    title: (title) => {
        if (!title) {
            return appName;
        }

        return title.includes(appName) ? title : `${title} - ${appName}`;
    },
    layout: (name) => {
        switch (true) {
            case name.startsWith('shop/'):
                return ShopLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    // Tout ce qui est rendu ici est HORS du contexte Inertia : pas de
    // `usePage()` dans ces composants (voir FlashToaster, monté dans les layouts).
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
