/*
 * Service worker des notifications push du site.
 *
 * Il tourne hors de la page : c'est lui qui reçoit les messages quand l'onglet
 * est fermé, et lui seul peut alors afficher une bannière. Il doit vivre à la
 * racine du domaine pour couvrir tout le site, d'où sa place dans `public/`
 * plutôt que dans les sources compilées.
 *
 * La configuration est passée en paramètres d'URL à l'enregistrement : un
 * service worker n'a pas accès aux variables d'environnement de l'application.
 */

importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js');

const params = new URL(self.location.href).searchParams;

const config = {
    apiKey: params.get('apiKey'),
    authDomain: params.get('authDomain'),
    projectId: params.get('projectId'),
    storageBucket: params.get('storageBucket'),
    messagingSenderId: params.get('messagingSenderId'),
    appId: params.get('appId'),
};

if (config.projectId && config.apiKey) {
    firebase.initializeApp(config);

    const messaging = firebase.messaging();

    // Message reçu alors qu'aucun onglet du site n'est au premier plan.
    messaging.onBackgroundMessage((payload) => {
        const notification = payload.notification || {};
        const data = payload.data || {};

        self.registration.showNotification(notification.title || 'Réf. Plomberie', {
            body: notification.body || '',
            icon: '/favicon-192.png',
            badge: '/favicon-192.png',
            image: notification.image || undefined,
            // Regroupe les notifications d'une même commande plutôt que
            // d'empiler cinq bannières successives.
            tag: data.type ? `${data.type}-${data.orderId || data.campaignId || ''}` : undefined,
            data: { url: data.url || '/' },
        });
    });
}

// Au clic : on réutilise un onglet déjà ouvert sur le site plutôt que d'en
// ouvrir un de plus.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = new URL(event.notification.data?.url || '/', self.location.origin).href;

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clients) => {
                for (const client of clients) {
                    if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                        client.navigate(target);

                        return client.focus();
                    }
                }

                return self.clients.openWindow(target);
            }),
    );
});
