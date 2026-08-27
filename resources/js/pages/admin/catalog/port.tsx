import { Head, useForm, usePage } from '@inertiajs/react';
import { Download, FileSpreadsheet, Upload } from 'lucide-react';
import type { ChangeEvent, FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

/** Colonnes du fichier, décrites pour l'utilisateur. */
const COLUMNS: { name: string; role: string }[] = [
    { name: 'slug', role: 'Identifiant de la fiche — ne pas modifier' },
    { name: 'nom', role: 'Nom affiché du produit' },
    { name: 'categorie', role: 'Identifiant de la catégorie' },
    { name: 'prix', role: 'Prix en francs CFA, sans décimale' },
    { name: 'ancien_prix', role: 'Prix barré, vide si pas de promotion' },
    { name: 'stock', role: 'Quantité disponible' },
    { name: 'seuil_alerte', role: 'Déclenche l’alerte de réapprovisionnement' },
    { name: 'badge', role: 'Étiquette affichée sur la carte produit' },
    { name: 'actif', role: '« oui » ou « non »' },
    { name: 'description', role: 'Texte de la fiche produit' },
];

export default function AdminCatalogPort({
    productsCount,
}: {
    productsCount: number;
}) {
    const { flash } = usePage().props;
    const { setData, post, processing, errors, reset } = useForm<{
        file: File | null;
    }>({ file: null });

    const handleFile = (e: ChangeEvent<HTMLInputElement>) =>
        setData('file', e.target.files?.[0] ?? null);

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(admin.catalog.import().url, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <>
            <Head title="Import / export du catalogue" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">
                        Import / export du catalogue
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Mettez à jour les {productsCount} fiches dans un tableur
                        plutôt qu’une par une.
                    </p>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Export */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border">
                        <h2 className="flex items-center gap-2 text-sm font-semibold">
                            <Download className="size-4 text-primary" />
                            1. Exporter
                        </h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Téléchargez le catalogue complet au format CSV, puis
                            ouvrez-le dans Excel ou LibreOffice. Le fichier est
                            encodé pour qu’un simple double-clic affiche les
                            accents et les colonnes correctement.
                        </p>
                        <Button className="mt-4" asChild>
                            <a href={admin.catalog.export().url}>
                                <Download className="size-4" />
                                Télécharger le catalogue
                            </a>
                        </Button>
                    </section>

                    {/* Import */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border">
                        <h2 className="flex items-center gap-2 text-sm font-semibold">
                            <Upload className="size-4 text-primary" />
                            2. Réimporter
                        </h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Renvoyez le fichier modifié. Les fiches sont
                            reconnues par leur colonne{' '}
                            <code className="rounded bg-muted px-1 py-0.5 text-xs">
                                slug
                            </code>
                            , et une cellule laissée vide ne remplace rien.
                        </p>

                        <form
                            onSubmit={handleSubmit}
                            className="mt-4 space-y-3"
                        >
                            <div>
                                <Label htmlFor="catalog-file">
                                    Fichier CSV
                                </Label>
                                <input
                                    id="catalog-file"
                                    type="file"
                                    accept=".csv,text/csv"
                                    onChange={handleFile}
                                    className="mt-1 block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-foreground"
                                />
                                {errors.file && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.file}
                                    </p>
                                )}
                            </div>

                            <Button type="submit" disabled={processing}>
                                <Upload className="size-4" />
                                {processing ? 'Import en cours…' : 'Importer'}
                            </Button>
                        </form>

                        {flash.importErrors.length > 0 && (
                            <div className="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 dark:border-amber-500/40 dark:bg-amber-500/10">
                                <p className="mb-1.5 text-xs font-semibold text-amber-900 dark:text-amber-200">
                                    Lignes ignorées
                                </p>
                                <ul className="space-y-1 text-xs text-amber-900/90 dark:text-amber-200/80">
                                    {flash.importErrors.map((message, i) => (
                                        <li key={i}>{message}</li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </section>
                </div>

                {/* Repères */}
                <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    <header className="flex items-center gap-2 border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                        <FileSpreadsheet className="size-4 text-muted-foreground" />
                        <h2 className="text-sm font-semibold">
                            Colonnes du fichier
                        </h2>
                    </header>

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {COLUMNS.map((column) => (
                                    <tr key={column.name}>
                                        <td className="w-40 px-4 py-2 font-mono text-xs whitespace-nowrap">
                                            {column.name}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {column.role}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <p className="border-t border-sidebar-border/70 px-4 py-3 text-xs text-muted-foreground dark:border-sidebar-border">
                        L’import ne crée pas de nouvelle fiche : une image et
                        une description soignée se travaillent depuis le
                        formulaire produit. Les lignes dont le{' '}
                        <code className="rounded bg-muted px-1 py-0.5">
                            slug
                        </code>{' '}
                        est inconnu sont signalées, pas inventées.
                    </p>
                </section>
            </div>
        </>
    );
}

AdminCatalogPort.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Import / export', href: admin.catalog.index() },
    ],
};
