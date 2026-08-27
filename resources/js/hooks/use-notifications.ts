import { usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
    isPushConfigured,
    listenToPush,
    pushAlreadyGranted,
    pushBlocked,
    requestPushToken,
} from '@/lib/push';
import type { FirebaseConfig, PushPayload } from '@/lib/push';
import notifications from '@/routes/notifications';

export type AppNotification = {
    id: string;
    type: string;
    title: string;
    body: string;
    url: string | null;
    read: boolean;
    createdAt: string | null;
};

/**
 * Centre de notifications du site.
 *
 * Le journal en base est le canal de fond : il arrive quoi qu'il arrive et le
 * client ne peut pas le couper. Le push n'est qu'un rappel par-dessus, soumis
 * à son autorisation.
 */
export function useNotifications() {
    const { auth, firebase } = usePage().props;
    const [items, setItems] = useState<AppNotification[]>([]);
    const [unread, setUnread] = useState(0);
    // Vrai dès le départ : le premier chargement part au montage, afficher
    // « aucune notification » avant sa réponse serait mensonger.
    const [loading, setLoading] = useState(true);
    const [pushEnabled, setPushEnabled] = useState(pushAlreadyGranted);
    const registered = useRef(false);

    const isAuthenticated = auth.user !== null;

    /** Rechargement demandé par le client, voile de chargement compris. */
    const refresh = useCallback(async () => {
        if (!isAuthenticated) {
            return;
        }

        setLoading(true);

        const journal = await fetchJournal();

        if (journal !== null) {
            setItems(journal.items);
            setUnread(journal.unread);
        }

        setLoading(false);
    }, [isAuthenticated]);

    const markAllRead = useCallback(async () => {
        if (unread === 0) {
            return;
        }

        setItems((current) => current.map((item) => ({ ...item, read: true })));
        setUnread(0);

        await fetch(notifications.readAll.url(), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': readXsrfToken(),
            },
        }).catch(() => refresh());
    }, [unread, refresh]);

    /**
     * Autorisation demandée explicitement par le client, depuis la cloche.
     *
     * Seul ce chemin touche l'état : il part d'un clic, jamais d'un effet.
     */
    const enablePush = useCallback(async () => {
        const granted = await registerBrowserForPush(firebase);

        setPushEnabled(granted);

        return granted;
    }, [firebase]);

    // Premier chargement. Les états ne sont posés qu'après la réponse : rien
    // ne doit changer pendant le corps de l'effet.
    useEffect(() => {
        if (!isAuthenticated) {
            return;
        }

        let active = true;

        void fetchJournal().then((journal) => {
            if (!active) {
                return;
            }

            if (journal !== null) {
                setItems(journal.items);
                setUnread(journal.unread);
            }

            setLoading(false);
        });

        return () => {
            active = false;
        };
    }, [isAuthenticated]);

    // Autorisation déjà accordée lors d'une visite précédente : on réenregistre
    // le jeton, que Firebase renouvelle sans prévenir.
    useEffect(() => {
        if (
            !isAuthenticated ||
            registered.current ||
            !pushAlreadyGranted() ||
            !isPushConfigured(firebase)
        ) {
            return;
        }

        registered.current = true;

        // Purement réseau : l'autorisation étant déjà accordée, l'état affiché
        // est correct et n'a pas à être retouché.
        void registerBrowserForPush(firebase);
    }, [isAuthenticated, firebase]);

    // Messages reçus pendant que l'onglet est au premier plan : Firebase
    // n'affiche alors rien de lui-même.
    useEffect(() => {
        if (!isAuthenticated || !isPushConfigured(firebase)) {
            return;
        }

        let stop = () => {};

        void listenToPush(firebase, (payload: PushPayload) => {
            setUnread((count) => count + 1);
            setItems((current) => [
                {
                    id: `direct-${Date.now()}`,
                    type: 'push',
                    title: payload.title,
                    body: payload.body,
                    url: payload.url,
                    read: false,
                    createdAt: new Date().toISOString(),
                },
                ...current,
            ]);
        }).then((unsubscribe) => {
            stop = unsubscribe;
        });

        return () => stop();
    }, [isAuthenticated, firebase]);

    return {
        items,
        unread,
        loading,
        refresh,
        markAllRead,
        enablePush,
        pushEnabled,
        pushBlocked: pushBlocked(),
        pushAvailable: isPushConfigured(firebase),
    };
}

/**
 * Lit le journal côté serveur.
 *
 * Hors de React et sans état : appelable depuis un effet comme depuis un clic.
 * Renvoie `null` en cas d'échec — le journal est un confort, son indisponi-
 * bilité ne doit rien casser à l'écran.
 */
async function fetchJournal(): Promise<{
    items: AppNotification[];
    unread: number;
} | null> {
    try {
        const response = await fetch(notifications.index.url(), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return null;
        }

        const data = (await response.json()) as {
            data: AppNotification[];
            unread: number;
        };

        return { items: data.data, unread: data.unread };
    } catch {
        return null;
    }
}

/**
 * Demande l'autorisation puis déclare le navigateur auprès du serveur.
 *
 * Volontairement hors de React : cette fonction ne touche aucun état, ce qui
 * la rend appelable depuis un effet — pour rafraîchir un jeton — comme depuis
 * un clic.
 */
async function registerBrowserForPush(
    config: FirebaseConfig,
): Promise<boolean> {
    const token = await requestPushToken(config);

    if (token === null) {
        return false;
    }

    const response = await fetch(notifications.device.register.url(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': readXsrfToken(),
        },
        body: JSON.stringify({
            token,
            platform: 'web',
            device_name: 'Navigateur',
        }),
    });

    return response.ok;
}

/** Jeton CSRF déposé par Laravel dans les cookies. */
function readXsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}
