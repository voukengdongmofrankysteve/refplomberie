import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type AdminTechnician = {
    id: number;
    name: string;
    specialty: string;
    experience: string;
    rating: number;
    jobsCount: number;
    photo: string;
    isAvailable: boolean;
    requestsCount: number;
};

const BLANK = {
    name: '',
    specialty: '',
    experience: '',
    rating: 5,
    jobs_count: 0,
    photo: '',
    is_available: true,
};

export default function AdminTechnicians({
    technicians,
}: {
    technicians: AdminTechnician[];
}) {
    // `null` = création, sinon identifiant du technicien édité.
    const [editing, setEditing] = useState<number | null>(null);
    const { data, setData, post, put, processing, errors, reset } =
        useForm(BLANK);

    const startCreate = () => {
        setEditing(null);
        reset();
    };

    const startEdit = (technician: AdminTechnician) => {
        setEditing(technician.id);
        setData({
            name: technician.name,
            specialty: technician.specialty,
            experience: technician.experience,
            rating: technician.rating,
            jobs_count: technician.jobsCount,
            photo: technician.photo,
            is_available: technician.isAvailable,
        });
    };

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setEditing(null);
                reset();
            },
        };

        if (editing === null) {
            post(admin.technicians.store().url, options);
        } else {
            put(admin.technicians.update(editing).url, options);
        }
    };

    const destroy = (technician: AdminTechnician) => {
        if (window.confirm(`Supprimer « ${technician.name} » ?`)) {
            router.delete(admin.technicians.destroy(technician.id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Techniciens" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">Techniciens</h1>
                        <p className="text-sm text-muted-foreground">
                            {technicians.length} technicien
                            {technicians.length !== 1 ? 's' : ''} dans l’équipe.
                        </p>
                    </div>
                    <Button variant="secondary" onClick={startCreate}>
                        <Plus className="size-4" />
                        Nouveau technicien
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_380px]">
                    {/* Liste */}
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            {technicians.map((technician) => (
                                <li
                                    key={technician.id}
                                    className="flex items-center gap-4 p-4"
                                >
                                    <img
                                        src={technician.photo}
                                        alt=""
                                        className="size-12 shrink-0 rounded-xl object-cover"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">
                                                {technician.name}
                                            </p>
                                            <Badge
                                                variant={
                                                    technician.isAvailable
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {technician.isAvailable
                                                    ? 'Disponible'
                                                    : 'Occupé'}
                                            </Badge>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {technician.specialty} ·{' '}
                                            {technician.experience}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Note {technician.rating}/5 ·{' '}
                                            {technician.jobsCount} interventions
                                            · {technician.requestsCount} demande
                                            {technician.requestsCount !== 1
                                                ? 's'
                                                : ''}{' '}
                                            assignée
                                            {technician.requestsCount !== 1
                                                ? 's'
                                                : ''}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                startEdit(technician)
                                            }
                                        >
                                            Modifier
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => destroy(technician)}
                                            aria-label={`Supprimer ${technician.name}`}
                                        >
                                            <Trash2 className="size-4 text-destructive" />
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </section>

                    {/* Formulaire */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        <header className="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                            <h2 className="text-sm font-semibold">
                                {editing === null
                                    ? 'Ajouter un technicien'
                                    : 'Modifier le technicien'}
                            </h2>
                        </header>

                        <form
                            onSubmit={handleSubmit}
                            className="grid gap-4 p-4"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="tech-name">Nom</Label>
                                <Input
                                    id="tech-name"
                                    required
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                />
                                {errors.name && (
                                    <p className="text-xs text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tech-specialty">
                                    Spécialité
                                </Label>
                                <Input
                                    id="tech-specialty"
                                    required
                                    value={data.specialty}
                                    onChange={(e) =>
                                        setData('specialty', e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="tech-experience">
                                        Expérience
                                    </Label>
                                    <Input
                                        id="tech-experience"
                                        required
                                        placeholder="8 ans"
                                        value={data.experience}
                                        onChange={(e) =>
                                            setData(
                                                'experience',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="tech-rating">Note /5</Label>
                                    <Input
                                        id="tech-rating"
                                        type="number"
                                        step="0.1"
                                        min={0}
                                        max={5}
                                        required
                                        value={data.rating}
                                        onChange={(e) =>
                                            setData(
                                                'rating',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tech-jobs">
                                    Interventions réalisées
                                </Label>
                                <Input
                                    id="tech-jobs"
                                    type="number"
                                    min={0}
                                    required
                                    value={data.jobs_count}
                                    onChange={(e) =>
                                        setData(
                                            'jobs_count',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tech-photo">Photo (URL)</Label>
                                <Input
                                    id="tech-photo"
                                    type="url"
                                    required
                                    placeholder="https://…"
                                    value={data.photo}
                                    onChange={(e) =>
                                        setData('photo', e.target.value)
                                    }
                                />
                                {errors.photo && (
                                    <p className="text-xs text-destructive">
                                        {errors.photo}
                                    </p>
                                )}
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="tech-available"
                                    checked={data.is_available}
                                    onCheckedChange={(checked) =>
                                        setData(
                                            'is_available',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label htmlFor="tech-available">
                                    Disponible pour de nouvelles interventions
                                </Label>
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    className="flex-1"
                                    disabled={processing}
                                >
                                    {processing
                                        ? 'Enregistrement…'
                                        : editing === null
                                          ? 'Ajouter'
                                          : 'Mettre à jour'}
                                </Button>
                                {editing !== null && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={startCreate}
                                    >
                                        Annuler
                                    </Button>
                                )}
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </>
    );
}

AdminTechnicians.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Techniciens', href: admin.technicians.index() },
    ],
};
