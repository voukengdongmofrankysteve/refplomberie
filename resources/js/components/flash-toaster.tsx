import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

/**
 * Relaie les messages flash de session (`success` / `error`) dans un toast.
 *
 * Monté une seule fois au niveau de l'application : chaque réponse Inertia
 * apporte de nouvelles props `flash`, ce qui déclenche l'affichage.
 */
export default function FlashToaster() {
    const { flash } = usePage().props;
    const lastShown = useRef<string | null>(null);

    useEffect(() => {
        const message = flash.success ?? flash.error;

        if (!message || message === lastShown.current) {
            return;
        }

        lastShown.current = message;

        if (flash.success) {
            toast.success(flash.success);
        } else if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    return null;
}
