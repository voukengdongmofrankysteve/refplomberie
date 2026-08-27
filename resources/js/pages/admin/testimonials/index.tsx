import { Head, router, useForm } from '@inertiajs/react';
import { MessagesSquare, Plus, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type AdminTestimonial = {
    id: number;
    name: string;
    role: string | null;
    text: string;
    rating: number;
    position: number;
    isActive: boolean;
};

const BLANK = {
    name: '',
    role: '',
    text: '',
    rating: 5,
    position: 0,
    is_active: true,
};

export default function AdminTestimonials({
    testimonials,
}: {
    testimonials: AdminTestimonial[];
}) {
    const [editing, setEditing] = useState<number | null>(null);
    const { data, setData, post, put, processing, errors, reset } =
        useForm(BLANK);

    const startCreate = () => {
        setEditing(null);
        reset();
    };

    const startEdit = (testimonial: AdminTestimonial) => {
        setEditing(testimonial.id);
        setData({
            name: testimonial.name,
            role: testimonial.role ?? '',
            text: testimonial.text,
            rating: testimonial.rating,
            position: testimonial.position,
            is_active: testimonial.isActive,
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
            post(admin.testimonials.store().url, options);
        } else {
            put(admin.testimonials.update(editing).url, options);
        }
    };

    const destroy = (testimonial: AdminTestimonial) => {
        if (
            window.confirm(
                `Supprimer le témoignage de « ${testimonial.name} » ?`,
            )
        ) {
            router.delete(admin.testimonials.destroy(testimonial.id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Témoignages" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">Témoignages</h1>
                        <p className="text-sm text-muted-foreground">
                            {testimonials.length} témoignage
                            {testimonials.length !== 1 ? 's' : ''}. Sans
                            témoignage actif, la section n’apparaît pas sur la
                            vitrine.
                        </p>
                    </div>
                    <Button variant="secondary" onClick={startCreate}>
                        <Plus className="size-4" />
                        Nouveau témoignage
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_380px]">
                    {/* Liste */}
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        {testimonials.length === 0 ? (
                            <EmptyState
                                icon={MessagesSquare}
                                title="Aucun témoignage"
                                description="Ajoutez les retours de vos clients les plus satisfaits pour rassurer les visiteurs."
                            />
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {testimonials.map((testimonial) => (
                                    <li
                                        key={testimonial.id}
                                        className="flex flex-wrap items-start gap-3 p-4"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-sm font-semibold">
                                                    {testimonial.name}
                                                </span>
                                                {testimonial.role && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {testimonial.role}
                                                    </span>
                                                )}
                                                <span className="flex items-center gap-0.5 text-amber-500">
                                                    {Array.from({
                                                        length: testimonial.rating,
                                                    }).map((_, i) => (
                                                        <Star
                                                            key={i}
                                                            className="size-3 fill-current"
                                                        />
                                                    ))}
                                                </span>
                                                {!testimonial.isActive && (
                                                    <Badge
                                                        variant="secondary"
                                                        className="bg-muted text-muted-foreground"
                                                    >
                                                        Masqué
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                                {testimonial.text}
                                            </p>
                                        </div>

                                        <div className="flex shrink-0 gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    startEdit(testimonial)
                                                }
                                            >
                                                Modifier
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    destroy(testimonial)
                                                }
                                                aria-label={`Supprimer ${testimonial.name}`}
                                            >
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    {/* Formulaire */}
                    <section className="h-fit rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">
                            {editing === null
                                ? 'Nouveau témoignage'
                                : 'Modifier le témoignage'}
                        </h2>

                        <form onSubmit={handleSubmit} className="space-y-3">
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="testimonial-name">
                                        Nom *
                                    </Label>
                                    <Input
                                        id="testimonial-name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        placeholder="Marc Dupont"
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-xs text-destructive">
                                            {errors.name}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="testimonial-role">
                                        Fonction
                                    </Label>
                                    <Input
                                        id="testimonial-role"
                                        value={data.role}
                                        onChange={(e) =>
                                            setData('role', e.target.value)
                                        }
                                        placeholder="Propriétaire, Yaoundé"
                                    />
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="testimonial-text">
                                    Témoignage *
                                </Label>
                                <textarea
                                    id="testimonial-text"
                                    rows={4}
                                    value={data.text}
                                    onChange={(e) =>
                                        setData('text', e.target.value)
                                    }
                                    className="mt-1 w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none"
                                    placeholder="Commande passée le matin, livrée le lendemain…"
                                />
                                {errors.text && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.text}
                                    </p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="testimonial-rating">
                                        Note (1 à 5)
                                    </Label>
                                    <Input
                                        id="testimonial-rating"
                                        type="number"
                                        min={1}
                                        max={5}
                                        value={data.rating}
                                        onChange={(e) =>
                                            setData(
                                                'rating',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="testimonial-position">
                                        Ordre d’affichage
                                    </Label>
                                    <Input
                                        id="testimonial-position"
                                        type="number"
                                        min={0}
                                        value={data.position}
                                        onChange={(e) =>
                                            setData(
                                                'position',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="testimonial-active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="testimonial-active"
                                    className="font-normal"
                                >
                                    Visible sur la vitrine
                                </Label>
                            </div>

                            <div className="flex gap-2 pt-1">
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Enregistrement…'
                                        : editing === null
                                          ? 'Créer'
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

AdminTestimonials.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Témoignages', href: admin.testimonials.index() },
    ],
};
