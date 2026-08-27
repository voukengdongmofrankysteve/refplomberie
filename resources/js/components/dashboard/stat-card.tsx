import type { LucideIcon } from 'lucide-react';

type StatCardProps = {
    label: string;
    value: string | number;
    hint?: string;
    icon?: LucideIcon;
};

/** Tuile de chiffre clé, utilisée en tête des deux tableaux de bord. */
export default function StatCard({
    label,
    value,
    hint,
    icon: Icon,
}: StatCardProps) {
    return (
        <div className="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate text-xs font-medium text-muted-foreground">
                        {label}
                    </p>
                    <p className="mt-1 text-2xl font-bold tracking-tight">
                        {value}
                    </p>
                    {hint && (
                        <p className="mt-1 text-xs text-muted-foreground">
                            {hint}
                        </p>
                    )}
                </div>
                {Icon && (
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Icon className="size-4" />
                    </span>
                )}
            </div>
        </div>
    );
}
