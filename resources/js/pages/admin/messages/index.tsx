import { Head, router } from '@inertiajs/react';
import { MessageSquare, Trash2 } from 'lucide-react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import StatusBadge from '@/components/dashboard/status-badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';
import type {
    ContactMessage,
    PaginatedResource,
    StatusOption,
} from '@/types/shop';

const SELECT_CLASS =
    'h-8 rounded-md border border-input bg-transparent px-2 text-xs shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function AdminMessages({
    messages,
    statuses,
}: {
    messages: PaginatedResource<ContactMessage>;
    statuses: StatusOption[];
}) {
    const updateStatus = (message: ContactMessage, status: string) =>
        router.put(
            admin.messages.update(message.id).url,
            { status },
            { preserveScroll: true },
        );

    const destroy = (message: ContactMessage) => {
        if (window.confirm(`Supprimer le message de ${message.name} ?`)) {
            router.delete(admin.messages.destroy(message.id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Messages" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">
                        Messages &amp; demandes de devis
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {messages.meta.total} message
                        {messages.meta.total !== 1 ? 's' : ''} reçu
                        {messages.meta.total !== 1 ? 's' : ''}.
                    </p>
                </div>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {messages.data.length === 0 ? (
                        <EmptyState
                            icon={MessageSquare}
                            title="Aucun message"
                            description="Les demandes envoyées depuis le formulaire de contact apparaîtront ici."
                        />
                    ) : (
                        <>
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {messages.data.map((message) => (
                                    <li key={message.id} className="p-4">
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="font-medium">
                                                        {message.name}
                                                    </p>
                                                    <StatusBadge
                                                        status={message.status}
                                                        label={
                                                            message.statusLabel
                                                        }
                                                    />
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    {message.phone}
                                                    {message.email
                                                        ? ` · ${message.email}`
                                                        : ''}{' '}
                                                    · {message.createdAt}
                                                </p>
                                            </div>

                                            <div className="flex shrink-0 items-center gap-2">
                                                <select
                                                    className={SELECT_CLASS}
                                                    value={message.status}
                                                    onChange={(e) =>
                                                        updateStatus(
                                                            message,
                                                            e.target.value,
                                                        )
                                                    }
                                                    aria-label={`Statut du message de ${message.name}`}
                                                >
                                                    {statuses.map((s) => (
                                                        <option
                                                            key={s.value}
                                                            value={s.value}
                                                        >
                                                            {s.label}
                                                        </option>
                                                    ))}
                                                </select>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        destroy(message)
                                                    }
                                                    aria-label={`Supprimer le message de ${message.name}`}
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </div>

                                        {message.subject && (
                                            <p className="mt-2 text-xs font-medium text-muted-foreground uppercase">
                                                Sujet : {message.subject}
                                            </p>
                                        )}
                                        <p className="mt-2 rounded-lg bg-muted px-3 py-2 text-sm leading-relaxed">
                                            {message.message}
                                        </p>
                                    </li>
                                ))}
                            </ul>

                            <Pagination
                                links={messages.meta.links}
                                from={messages.meta.from}
                                to={messages.meta.to}
                                total={messages.meta.total}
                            />
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

AdminMessages.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Messages', href: admin.messages.index() },
    ],
};
