#!/usr/bin/env bash
#
# Déploiement Réf. Plomberie — à lancer sur le serveur, depuis la racine du
# projet (le dossier qui contient `artisan`).
#
#   cd /home/u270852666/domains/vyloxi.com/public_html/refplomberie
#   bash deploy.sh
#
# Le script est idempotent : on peut le relancer sans risque.
#
# ⚠ Ne téléversez JAMAIS le dossier `bootstrap/cache/` depuis votre machine.
#   Il contient le manifeste des paquets généré en environnement de
#   développement ; sur un serveur installé sans les dépendances de dev, il
#   fait planter le démarrage (« Class Laravel\Pail\PailServiceProvider not
#   found »). Le script le régénère lui-même ci-dessous.

set -euo pipefail

cd "$(dirname "$0")"

php_bin="${PHP_BIN:-php}"

echo "▶ Purge des caches compilés"
# En tout premier, et avec `rm` plutôt qu'`artisan` : ces fichiers sont lus au
# démarrage du framework. S'ils sont périmés, plus aucune commande artisan
# — ni le `package:discover` déclenché par Composer — ne peut s'exécuter.
rm -f bootstrap/cache/packages.php \
      bootstrap/cache/services.php \
      bootstrap/cache/config.php \
      bootstrap/cache/routes-*.php \
      bootstrap/cache/events.php

echo "▶ Dépendances PHP"
composer install --no-dev --optimize-autoloader --no-interaction

echo "▶ Purge des caches applicatifs (config, vues, événements)"
"$php_bin" artisan optimize:clear

echo "▶ Migrations"
"$php_bin" artisan migrate --force

echo "▶ Données de référence (réglages boutique, compte admin)"
# Ces seeders sont ré-exécutables et ne réécrasent pas ce qui a été modifié
# depuis le back-office.
"$php_bin" artisan db:seed --class=StoreSettingSeeder --force
"$php_bin" artisan db:seed --class=AdminUserSeeder --force

echo "▶ Lien public vers le stockage des images"
"$php_bin" artisan storage:link || {
    echo "  ⚠ Symlink refusé par l'hébergeur." >&2
    echo "    Les images téléversées ne s'afficheront pas tant que" >&2
    echo "    public/storage ne pointe pas vers storage/app/public." >&2
}

echo "▶ Icônes de marque (favicon, PWA, image de partage)"
"$php_bin" artisan brand:assets

echo "▶ Remise en cache pour la production"
"$php_bin" artisan config:cache
"$php_bin" artisan route:cache
"$php_bin" artisan view:cache

echo "✅ Déploiement terminé."
echo
echo "Contrôles rapides :"
echo "  $php_bin artisan about --only=environment"
echo "  $php_bin artisan route:list --path=admin/products"
echo "  $php_bin artisan tinker --execute=\"echo App\\Models\\Product::count();\""
