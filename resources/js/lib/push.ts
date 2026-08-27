import { initializeApp } from 'firebase/app';
import type { FirebaseApp } from 'firebase/app';
import {
    getMessaging,
    getToken,
    isSupported,
    onMessage,
} from 'firebase/messaging';
import type { Messaging } from 'firebase/messaging';

/** Configuration Firebase partagée par le serveur. */
export type FirebaseConfig = {
    apiKey: string | null;
    authDomain: string | null;
    projectId: string | null;
    storageBucket: string | null;
    messagingSenderId: string | null;
    appId: string | null;
    vapidKey: string | null;
};

/** Une notification telle qu'elle arrive quand l'onglet est au premier plan. */
export type PushPayload = {
    title: string;
    body: string;
    url: string | null;
};

let app: FirebaseApp | null = null;
let messaging: Messaging | null = null;

/** La configuration est-elle complète ? */
export function isPushConfigured(config: FirebaseConfig): boolean {
    // La clé VAPID est indispensable côté web : sans elle le navigateur ne
    // peut pas s'abonner, alors que l'application mobile n'en a pas besoin.
    return Boolean(config.projectId && config.apiKey && config.vapidKey);
}

/**
 * Prépare Firebase Messaging, ou renvoie `null` si le navigateur ne le
 * supporte pas — Safari en navigation privée, par exemple.
 */
async function prepare(config: FirebaseConfig): Promise<Messaging | null> {
    if (!isPushConfigured(config) || !(await isSupported())) {
        return null;
    }

    app ??= initializeApp({
        apiKey: config.apiKey!,
        authDomain: config.authDomain ?? undefined,
        projectId: config.projectId!,
        storageBucket: config.storageBucket ?? undefined,
        messagingSenderId: config.messagingSenderId ?? undefined,
        appId: config.appId ?? undefined,
    });

    messaging ??= getMessaging(app);

    return messaging;
}

/**
 * Enregistre le service worker.
 *
 * La configuration voyage en paramètres d'URL : un service worker n'a pas
 * accès aux variables de l'application, et coder les clés en dur dans un
 * fichier statique interdirait d'en changer sans le réécrire.
 */
async function registerWorker(
    config: FirebaseConfig,
): Promise<ServiceWorkerRegistration> {
    const params = new URLSearchParams({
        apiKey: config.apiKey ?? '',
        authDomain: config.authDomain ?? '',
        projectId: config.projectId ?? '',
        storageBucket: config.storageBucket ?? '',
        messagingSenderId: config.messagingSenderId ?? '',
        appId: config.appId ?? '',
    });

    return navigator.serviceWorker.register(
        `/firebase-messaging-sw.js?${params.toString()}`,
        { scope: '/' },
    );
}

/**
 * Demande l'autorisation et renvoie le jeton de cet appareil.
 *
 * Renvoie `null` si le client refuse : c'est un choix légitime, pas une
 * erreur, et l'appelant doit pouvoir l'afficher tel quel.
 */
export async function requestPushToken(
    config: FirebaseConfig,
): Promise<string | null> {
    const instance = await prepare(config);

    if (instance === null) {
        return null;
    }

    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        return null;
    }

    try {
        const token = await getToken(instance, {
            vapidKey: config.vapidKey!,
            serviceWorkerRegistration: await registerWorker(config),
        });

        return token || null;
    } catch {
        // Jeton refusé par Firebase : sans clé VAPID valable, notamment.
        return null;
    }
}

/** Autorisation déjà accordée lors d'une visite précédente ? */
export function pushAlreadyGranted(): boolean {
    return (
        typeof Notification !== 'undefined' &&
        Notification.permission === 'granted'
    );
}

/** Le client a-t-il explicitement refusé ? Le navigateur ne redemandera pas. */
export function pushBlocked(): boolean {
    return (
        typeof Notification !== 'undefined' &&
        Notification.permission === 'denied'
    );
}

/**
 * Écoute les messages reçus pendant que l'onglet est au premier plan.
 *
 * Firebase n'affiche alors aucune bannière : c'est à l'application de le
 * faire, sinon le client ne verrait rien.
 */
export async function listenToPush(
    config: FirebaseConfig,
    onReceived: (payload: PushPayload) => void,
): Promise<() => void> {
    const instance = await prepare(config);

    if (instance === null) {
        return () => {};
    }

    return onMessage(instance, (message) => {
        onReceived({
            title: message.notification?.title ?? 'Réf. Plomberie',
            body: message.notification?.body ?? '',
            url: (message.data?.url as string | undefined) ?? null,
        });
    });
}
