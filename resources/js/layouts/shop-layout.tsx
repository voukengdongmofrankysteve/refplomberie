import type { ReactNode } from 'react';
import FlashToaster from '@/components/flash-toaster';
import AuthModal from '@/components/shop/auth-modal';
import CartPanel from '@/components/shop/cart-panel';
import CartToast from '@/components/shop/cart-toast';
import ChatWidget from '@/components/shop/chat-widget';
import ComparisonBar from '@/components/shop/comparison-bar';
import ComparisonModal from '@/components/shop/comparison-modal';
import FavoritesPanel from '@/components/shop/favorites-panel';
import Footer from '@/components/shop/footer';
import NavBar from '@/components/shop/navbar';
import { AuthModalProvider } from '@/contexts/auth-modal-context';
import { CartProvider } from '@/contexts/cart-context';
import { ComparisonProvider } from '@/contexts/comparison-context';
import { FavoritesProvider } from '@/contexts/favorites-context';

/**
 * Layout persistant de la vitrine.
 *
 * Déclaré dans `app.tsx` pour toutes les pages `shop/*`, il survit aux
 * navigations Inertia : le panier et les favoris ne sont donc pas remis à zéro
 * quand on passe de l'accueil à une fiche produit.
 */
export default function ShopLayout({ children }: { children: ReactNode }) {
    return (
        <AuthModalProvider>
            <CartProvider>
                <FavoritesProvider>
                    <ComparisonProvider>
                        <div className="min-h-screen bg-white text-[#1A1A2E] antialiased">
                            <NavBar />
                            {children}
                            <Footer />
                            <CartPanel />
                            <FavoritesPanel />
                            <CartToast />
                            <AuthModal />
                            <ComparisonBar />
                            <ComparisonModal />
                            <FlashToaster />
                            <ChatWidget />
                        </div>
                    </ComparisonProvider>
                </FavoritesProvider>
            </CartProvider>
        </AuthModalProvider>
    );
}
