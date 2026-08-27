import type { Auth } from '@/types/auth';
import type { Product, StoreInfo } from '@/types/shop';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth & { isAdmin: boolean; isStaff: boolean; permissions: string[] };
            seoTitle: string;
            sidebarOpen: boolean;
            store: StoreInfo;
            favorites: Product[];
            /** Le serveur a-t-il les identifiants OAuth de Google ? */
            googleEnabled: boolean;
            firebase: {
                apiKey: string | null;
                authDomain: string | null;
                projectId: string | null;
                storageBucket: string | null;
                messagingSenderId: string | null;
                appId: string | null;
                vapidKey: string | null;
            };
            flash: {
                success: string | null;
                error: string | null;
                orderReference: string | null;
                quoteReference: string | null;
                quoteUrl: string | null;
                importErrors: string[];
            };
            [key: string]: unknown;
        };
    }
}
