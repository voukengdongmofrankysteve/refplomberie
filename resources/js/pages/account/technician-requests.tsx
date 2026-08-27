import { Head, useForm } from '@inertiajs/react';
import { Wrench } from 'lucide-react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import StatusBadge from '@/components/dashboard/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import * as account from '@/routes/account';
import { store as storeRequest } from '@/routes/technician-requests';
import type { PaginatedResource, TechnicianRequest } from '@/types/shop';

const HOURS = [
    '07:00',
    '08:00',
    '09:00',
    '10:00',
    '11:00',
    '12:00',
    '13:00',
    '14:00',
    '15:00',
    '16:00',
    '17:00',
];

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

type Props = {
    requests: PaginatedResource<TechnicianRequest>;
    services: string[];
    defaults: {
        customer_name: string;
        customer_phone: string;
        address: string;
    };
};

export default function AccountTechnicianRequests({
    requests,
    services,
    defaults,
}: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        ...defaults,
        service: '',
        preferred_date: '',
        preferred_time: '',
        description: '',
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(storeRequest.url(), {
            preserveScroll: true,
            onSuccess: () =>
                reset(
                    'service',
                    'preferred_date',
                    'preferred_time',
                    'description',
                ),
        });
    };

    return (
        <>
            <Head title="Mes interventions" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-lg font-semibold">Mes interventions</h1>
                    <p className="text-sm text-muted-foreground">
                        Demandez un technicien et suivez le traitement de vos
                        demandes.
                    </p>
                </div>

                <div className="grid gap-4 lg:grid-cols-[380px_1fr]">
                    {/* Nouvelle demande */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <header className="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                            <h2 className="text-sm font-semibold">
                                Demander un technicien
                            </h2>
                        </header>

                        <form onSubmit={handleSubmit} className="space-y-4 p-4">
                            <div className="grid gap-2">
                                <Label htmlFor="req-name">Nom complet</Label>
                                <Input
                                    id="req-name"
                                    required
                                    value={data.customer_name}
                                    onChange={(e) =>
                                        setData('customer_name', e.target.value)
                                    }
                                />
                                {errors.customer_name && (
                                    <p className="text-xs text-destructive">
                                        {errors.customer_name}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="req-phone">Téléphone</Label>
                                <Input
                                    id="req-phone"
                                    type="tel"
                                    required
                                    value={data.customer_phone}
                                    onChange={(e) =>
                                        setData(
                                            'customer_phone',
                                            e.target.value,
                                        )
                                    }
                                />
                                {errors.customer_phone && (
                                    <p className="text-xs text-destructive">
                                        {errors.customer_phone}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="req-address">
                                    Adresse d’intervention
                                </Label>
                                <Input
                                    id="req-address"
                                    required
                                    value={data.address}
                                    onChange={(e) =>
                                        setData('address', e.target.value)
                                    }
                                />
                                {errors.address && (
                                    <p className="text-xs text-destructive">
                                        {errors.address}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="req-service">Service</Label>
                                <select
                                    id="req-service"
                                    required
                                    className={SELECT_CLASS}
                                    value={data.service}
                                    onChange={(e) =>
                                        setData('service', e.target.value)
                                    }
                                >
                                    <option value="">Sélectionner…</option>
                                    {services.map((service) => (
                                        <option key={service} value={service}>
                                            {service}
                                        </option>
                                    ))}
                                </select>
                                {errors.service && (
                                    <p className="text-xs text-destructive">
                                        {errors.service}
                                    </p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="req-date">
                                        Date souhaitée
                                    </Label>
                                    <Input
                                        id="req-date"
                                        type="date"
                                        min={
                                            new Date()
                                                .toISOString()
                                                .split('T')[0]
                                        }
                                        value={data.preferred_date}
                                        onChange={(e) =>
                                            setData(
                                                'preferred_date',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="req-time">Heure</Label>
                                    <select
                                        id="req-time"
                                        className={SELECT_CLASS}
                                        value={data.preferred_time}
                                        onChange={(e) =>
                                            setData(
                                                'preferred_time',
                                                e.target.value,
                                            )
                                        }
                                    >
                                        <option value="">Choisir</option>
                                        {HOURS.map((hour) => (
                                            <option key={hour} value={hour}>
                                                {hour}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="req-description">
                                    Description
                                </Label>
                                <textarea
                                    id="req-description"
                                    rows={4}
                                    required
                                    className="w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                    placeholder="Décrivez le problème ou les travaux à effectuer…"
                                />
                                {errors.description && (
                                    <p className="text-xs text-destructive">
                                        {errors.description}
                                    </p>
                                )}
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing ? 'Envoi…' : 'Envoyer la demande'}
                            </Button>
                        </form>
                    </section>

                    {/* Historique */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <header className="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                            <h2 className="text-sm font-semibold">
                                Historique de mes demandes
                            </h2>
                        </header>

                        {requests.data.length === 0 ? (
                            <EmptyState
                                icon={Wrench}
                                title="Aucune demande"
                                description="Utilisez le formulaire pour demander une intervention."
                            />
                        ) : (
                            <>
                                <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                    {requests.data.map((request) => (
                                        <li key={request.id} className="p-4">
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <p className="text-sm font-semibold">
                                                        {request.service}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {request.reference} ·
                                                        déposée le{' '}
                                                        {request.createdAt}
                                                    </p>
                                                </div>
                                                <StatusBadge
                                                    status={request.status}
                                                    label={request.statusLabel}
                                                />
                                            </div>

                                            <p className="mt-2 text-sm text-muted-foreground">
                                                {request.description}
                                            </p>

                                            <dl className="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-3">
                                                <div>
                                                    <dt className="font-medium text-foreground">
                                                        Technicien
                                                    </dt>
                                                    <dd>
                                                        {request.technicianName ??
                                                            'À assigner'}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="font-medium text-foreground">
                                                        Créneau
                                                    </dt>
                                                    <dd>
                                                        {request.preferredDate ??
                                                            'Non précisé'}
                                                        {request.preferredTime
                                                            ? ` à ${request.preferredTime}`
                                                            : ''}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="font-medium text-foreground">
                                                        Adresse
                                                    </dt>
                                                    <dd>{request.address}</dd>
                                                </div>
                                            </dl>

                                            {request.adminNote && (
                                                <p className="mt-3 rounded-lg bg-muted px-3 py-2 text-xs">
                                                    <span className="font-medium">
                                                        Note de l’équipe :
                                                    </span>{' '}
                                                    {request.adminNote}
                                                </p>
                                            )}
                                        </li>
                                    ))}
                                </ul>

                                <Pagination
                                    links={requests.meta.links}
                                    from={requests.meta.from}
                                    to={requests.meta.to}
                                    total={requests.meta.total}
                                />
                            </>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

AccountTechnicianRequests.layout = {
    breadcrumbs: [
        { title: 'Mon espace', href: dashboard() },
        { title: 'Mes interventions', href: account.technicianRequests() },
    ],
};
