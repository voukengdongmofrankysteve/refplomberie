/*
 * Déclaration des actions qui n'existent que dans le navigateur.
 *
 * Ajouter au panier, cliquer sur WhatsApp, regarder un statut : le serveur
 * n'en sait rien. Ce module lui en dit le strict nécessaire — un type, parfois
 * un produit — sans jamais rien envoyer ailleurs qu'à notre propre domaine.
 */

export type TrackableEvent =
    | 'add_to_cart'
    | 'checkout_started'
    | 'whatsapp_click'
    | 'phone_click'
    | 'story_view'
    | 'product_view';

type Payload = {
    subject?: 'product' | 'story';
    id?: number;
    label?: string;
    value?: number;
    path?: string;
};

/**
 * Envoie l'action, sans jamais faire attendre l'interface.
 *
 * `sendBeacon` remet la requête au navigateur, qui la poste même si la page
 * se ferme dans la seconde — le cas du clic WhatsApp, qui quitte le site.
 * L'échec est silencieux : une mesure perdue ne vaut pas un message d'erreur
 * à un client venu acheter un robinet.
 */
export function track(type: TrackableEvent, payload: Payload = {}): void {
    if (typeof window === 'undefined') {
        return;
    }

    const body = JSON.stringify({
        type,
        path: window.location.pathname,
        ...payload,
    });

    // Même jeton que celui qu'Inertia renvoie : Laravel dépose XSRF-TOKEN en
    // clair dans un cookie, et attend qu'on le lui retourne dans l'en-tête.
    const cookie = document.cookie
        .split('; ')
        .find((part) => part.startsWith('XSRF-TOKEN='));

    const token = cookie ? decodeURIComponent(cookie.split('=')[1]) : null;

    void fetch('/mesure', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
        },
        body,
        // La page peut disparaître pendant l'envoi : le navigateur termine
        // quand même la requête.
        keepalive: true,
        credentials: 'same-origin',
    }).catch(() => {
        // Mesure perdue, tant pis.
    });
}
