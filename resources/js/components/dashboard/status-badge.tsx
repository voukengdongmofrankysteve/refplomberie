import { Badge } from '@/components/ui/badge';

/** Couleur associée à chaque statut métier (commande, intervention, message). */
const TONES: Record<string, string> = {
    pending:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    new: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    draft: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    assigned: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    read: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    sent: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
    preparing:
        'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300',
    scheduled:
        'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300',
    shipped: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300',
    delivered:
        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    completed:
        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    answered:
        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    accepted:
        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    refused: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
};

export default function StatusBadge({
    status,
    label,
}: {
    status: string;
    label: string;
}) {
    return (
        <Badge
            variant="secondary"
            className={TONES[status] ?? 'bg-muted text-muted-foreground'}
        >
            {label}
        </Badge>
    );
}
