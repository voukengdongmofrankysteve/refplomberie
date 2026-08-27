import { Head, usePage } from '@inertiajs/react';

/**
 * Titre d'onglet des pages publiques.
 *
 * La valeur est calculée côté serveur (titre SEO du back-office pour
 * l'accueil, nom du produit pour une fiche) : la balise rendue avant l'envoi
 * et celle mise à jour lors d'une navigation Inertia restent ainsi identiques.
 */
export default function SeoTitle() {
    const { seoTitle } = usePage().props;

    return (
        <Head>
            <title>{seoTitle}</title>
        </Head>
    );
}
