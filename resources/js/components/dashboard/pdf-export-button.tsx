import { FileText } from 'lucide-react';

/**
 * Lien de téléchargement PDF, commun aux listes du back-office.
 *
 * Un simple `<a>` plutôt qu'un bouton Inertia : c'est un téléchargement de
 * fichier, pas une navigation, et le navigateur sait déjà quoi en faire.
 */
export default function PdfExportButton({ href }: { href: string }) {
    return (
        <a
            href={href}
            className="flex h-9 items-center gap-1.5 rounded-lg border border-sidebar-border/70 px-3 text-sm font-medium hover:bg-accent dark:border-sidebar-border"
            title="Exporter cette liste en PDF"
        >
            <FileText className="size-4" />
            PDF
        </a>
    );
}
