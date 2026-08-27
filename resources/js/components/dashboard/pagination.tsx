import { Link } from '@inertiajs/react';
import type { PaginationLink } from '@/types/shop';

type PaginationProps = {
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

/** Barre de pagination rendue à partir des liens fournis par Laravel. */
export default function Pagination({
    links,
    from,
    to,
    total,
}: PaginationProps) {
    if (total === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
            <p className="text-xs text-muted-foreground">
                {from ?? 0}–{to ?? 0} sur {total}
            </p>
            <div className="flex flex-wrap gap-1">
                {links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className={`rounded-md px-3 py-1.5 text-xs font-medium transition-colors ${
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-muted'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={index}
                            className="rounded-md px-3 py-1.5 text-xs text-muted-foreground/50"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
            </div>
        </div>
    );
}
