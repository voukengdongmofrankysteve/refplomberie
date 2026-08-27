import { usePage } from '@inertiajs/react';
import type { StoreInfo } from '@/types/shop';

/** Coordonnées du magasin, partagées par HandleInertiaRequests. */
export function useStoreInfo(): StoreInfo {
    return usePage().props.store;
}
