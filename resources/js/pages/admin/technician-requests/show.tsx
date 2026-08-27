import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import StatusBadge from '@/components/dashboard/status-badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';
import type { StatusOption, TechnicianRequest } from '@/types/shop';

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

type Props = {
    request: TechnicianRequest;
    statuses: StatusOption[];
    technicians: { value: number; label: string; available: boolean }[];
};

export default function AdminTechnicianRequestShow({
    request,
    statuses,
    technicians,
}: Props) {
    const { data, setData, put, processing, errors } = useForm({
        status: request.status,
        technician_id: request.technicianId,
        admin_note: request.adminNote ?? '',
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        put(admin.technicianRequests.update(request.id).url, {
            preserveScroll: true,
        });
    };

    const destroy = () => {
        if (
            window.confirm(
                `Supprimer définitivement la demande ${request.reference} ?`,
            )
        ) {
            router.delete(admin.technicianRequests.destroy(request.id).url);
        }
    };

    return (
        <>
            <Head title={`Demande ${request.reference}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-lg font-semibold">
                                {request.reference}
                            </h1>
                            <StatusBadge
                                status={request.status}
                                label={request.statusLabel}
                            />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Reçue le {request.createdAt}
                        </p>
                    </div>
                    <Button variant="outline" onClick={destroy}>
                        <Trash2 className="size-4 text-destructive" />
                        Supprimer
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Détail de la demande */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card lg:col-span-2 dark:border-sidebar-border">
                        <header className="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                            <h2 className="text-sm font-semibold">
                                Détail de la demande
                            </h2>
                        </header>

                        <dl className="grid gap-4 p-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Client
                                </dt>
                                <dd className="font-medium">
                                    {request.customerName}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Téléphone
                                </dt>
                                <dd className="font-medium">
                                    {request.customerPhone}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Compte
                                </dt>
                                <dd className="font-medium">
                                    {request.accountEmail ?? 'Demande invité'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Service
                                </dt>
                                <dd className="font-medium">
                                    {request.service}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Adresse
                                </dt>
                                <dd className="font-medium">
                                    {request.address}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Créneau souhaité
                                </dt>
                                <dd className="font-medium">
                                    {request.preferredDate ?? 'Non précisé'}
                                    {request.preferredTime
                                        ? ` à ${request.preferredTime}`
                                        : ''}
                                </dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-xs text-muted-foreground">
                                    Description
                                </dt>
                                <dd className="mt-1 rounded-lg bg-muted px-3 py-2 leading-relaxed">
                                    {request.description}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    {/* Traitement */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <header className="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                            <h2 className="text-sm font-semibold">
                                Traitement
                            </h2>
                        </header>

                        <form
                            onSubmit={handleSubmit}
                            className="grid gap-4 p-4"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="technician">
                                    Technicien assigné
                                </Label>
                                <select
                                    id="technician"
                                    className={SELECT_CLASS}
                                    value={data.technician_id ?? ''}
                                    onChange={(e) =>
                                        setData(
                                            'technician_id',
                                            e.target.value === ''
                                                ? null
                                                : Number(e.target.value),
                                        )
                                    }
                                >
                                    <option value="">Aucun</option>
                                    {technicians.map((t) => (
                                        <option key={t.value} value={t.value}>
                                            {t.label}
                                            {t.available ? '' : ' (occupé)'}
                                        </option>
                                    ))}
                                </select>
                                {errors.technician_id && (
                                    <p className="text-xs text-destructive">
                                        {errors.technician_id}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Statut</Label>
                                <select
                                    id="status"
                                    className={SELECT_CLASS}
                                    value={data.status}
                                    onChange={(e) =>
                                        setData('status', e.target.value)
                                    }
                                >
                                    {statuses.map((s) => (
                                        <option key={s.value} value={s.value}>
                                            {s.label}
                                        </option>
                                    ))}
                                </select>
                                {errors.status && (
                                    <p className="text-xs text-destructive">
                                        {errors.status}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="admin_note">
                                    Note pour le client
                                </Label>
                                <textarea
                                    id="admin_note"
                                    rows={4}
                                    className="w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    value={data.admin_note}
                                    onChange={(e) =>
                                        setData('admin_note', e.target.value)
                                    }
                                    placeholder="Visible dans l’espace client…"
                                />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? 'Enregistrement…'
                                    : 'Mettre à jour'}
                            </Button>
                        </form>
                    </section>
                </div>
            </div>
        </>
    );
}

AdminTechnicianRequestShow.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Interventions', href: admin.technicianRequests.index() },
    ],
};
