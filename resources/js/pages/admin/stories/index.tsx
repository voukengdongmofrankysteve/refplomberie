import { Head, router, useForm } from '@inertiajs/react';
import { Clapperboard, ImagePlus, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';
import type { Story } from '@/types/shop';

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

const BLANK = {
    title: '',
    caption: '',
    media_type: 'image' as 'image' | 'video',
    link_url: '',
    link_label: '',
    position: 0,
    is_active: true,
    media_image: null as File | null,
    media_video: null as File | null,
    poster: null as File | null,
};

export default function AdminStories({ stories }: { stories: Story[] }) {
    // `null` = création, sinon identifiant du statut en cours d'édition.
    const [editing, setEditing] = useState<number | null>(null);
    const [preview, setPreview] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset, transform } =
        useForm(BLANK);

    const startCreate = () => {
        setEditing(null);
        setPreview(null);
        reset();
    };

    const startEdit = (story: Story) => {
        setEditing(story.id);
        setPreview(story.thumbnailUrl);
        setData({
            ...BLANK,
            title: story.title,
            caption: story.caption ?? '',
            media_type: story.type,
            link_url: story.linkUrl ?? '',
            link_label: story.linkLabel ?? '',
            position: story.position,
            is_active: story.isActive,
        });
    };

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        // Média joint : envoi multipart, avec `_method` pour la mise à jour.
        transform((payload) => ({
            ...payload,
            ...(editing === null ? {} : { _method: 'put' }),
        }));

        post(
            editing === null
                ? admin.stories.store().url
                : admin.stories.update(editing).url,
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: startCreate,
            },
        );
    };

    const handleFile =
        (field: 'media_image' | 'media_video' | 'poster') =>
        (e: ChangeEvent<HTMLInputElement>) => {
            const file = e.target.files?.[0] ?? null;

            setData(field, file);

            if (file && field !== 'media_video') {
                setPreview(URL.createObjectURL(file));
            }
        };

    const destroy = (story: Story) => {
        if (window.confirm(`Supprimer le statut « ${story.title} » ?`)) {
            router.delete(admin.stories.destroy(story.id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Statuts" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">
                            Statuts de la boutique
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Le fil horizontal affiché sur la page d’accueil :
                            arrivages, chantiers, courtes vidéos.
                        </p>
                    </div>
                    <Button variant="secondary" onClick={startCreate}>
                        <Plus className="size-4" />
                        Nouveau statut
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_380px]">
                    {/* Fil existant */}
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card">
                        {stories.length === 0 ? (
                            <EmptyState
                                icon={Clapperboard}
                                title="Aucun statut"
                                description="Publiez une photo d’arrivage ou une courte vidéo pour animer la page d’accueil."
                            />
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70">
                                {stories.map((story) => (
                                    <li
                                        key={story.id}
                                        className="flex items-center gap-4 p-4"
                                    >
                                        <div className="relative aspect-[9/16] w-16 shrink-0 overflow-hidden rounded-lg bg-muted">
                                            {story.thumbnailUrl && (
                                                <img
                                                    src={story.thumbnailUrl}
                                                    alt=""
                                                    className="size-full object-cover"
                                                />
                                            )}
                                        </div>

                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-medium">
                                                    {story.title}
                                                </p>
                                                <Badge
                                                    variant={
                                                        story.isActive
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {story.isActive
                                                        ? 'En ligne'
                                                        : 'Masqué'}
                                                </Badge>
                                                {story.type === 'video' && (
                                                    <Badge variant="outline">
                                                        Vidéo
                                                    </Badge>
                                                )}
                                            </div>
                                            {story.caption && (
                                                <p className="line-clamp-1 text-sm text-muted-foreground">
                                                    {story.caption}
                                                </p>
                                            )}
                                            <p className="text-xs text-muted-foreground">
                                                Position {story.position}
                                            </p>
                                        </div>

                                        <div className="flex shrink-0 gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => startEdit(story)}
                                            >
                                                Modifier
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => destroy(story)}
                                                aria-label={`Supprimer ${story.title}`}
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
                    <section className="rounded-xl border border-sidebar-border/70 bg-card">
                        <header className="border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="text-sm font-semibold">
                                {editing === null
                                    ? 'Publier un statut'
                                    : 'Modifier le statut'}
                            </h2>
                        </header>

                        <form
                            onSubmit={handleSubmit}
                            className="grid gap-4 p-4"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="story-title">Titre</Label>
                                <Input
                                    id="story-title"
                                    required
                                    value={data.title}
                                    onChange={(e) =>
                                        setData('title', e.target.value)
                                    }
                                />
                                {errors.title && (
                                    <p className="text-xs text-destructive">
                                        {errors.title}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="story-caption">Légende</Label>
                                <Input
                                    id="story-caption"
                                    value={data.caption}
                                    onChange={(e) =>
                                        setData('caption', e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="story-type">
                                    Type de média
                                </Label>
                                <select
                                    id="story-type"
                                    className={SELECT_CLASS}
                                    value={data.media_type}
                                    onChange={(e) =>
                                        setData(
                                            'media_type',
                                            e.target.value as 'image' | 'video',
                                        )
                                    }
                                >
                                    <option value="image">Image</option>
                                    <option value="video">Vidéo</option>
                                </select>
                            </div>

                            {/* Aperçu */}
                            <div className="flex items-center gap-3">
                                <div className="flex aspect-[9/16] w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-sidebar-border bg-muted">
                                    {preview ? (
                                        <img
                                            src={preview}
                                            alt="Aperçu"
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <ImagePlus className="size-5 text-muted-foreground" />
                                    )}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Format portrait conseillé (9:16), comme un
                                    statut de téléphone.
                                </p>
                            </div>

                            {data.media_type === 'image' ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="story-image">Image</Label>
                                    <Input
                                        id="story-image"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handleFile('media_image')}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Optimisée et filigranée automatiquement.
                                    </p>
                                    {errors.media_image && (
                                        <p className="text-xs text-destructive">
                                            {errors.media_image}
                                        </p>
                                    )}
                                </div>
                            ) : (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="story-video">
                                            Vidéo (MP4, MOV, WebM — 25 Mo max)
                                        </Label>
                                        <Input
                                            id="story-video"
                                            type="file"
                                            accept="video/mp4,video/quicktime,video/webm"
                                            onChange={handleFile('media_video')}
                                        />
                                        {errors.media_video && (
                                            <p className="text-xs text-destructive">
                                                {errors.media_video}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="story-poster">
                                            Vignette de la vidéo
                                        </Label>
                                        <Input
                                            id="story-poster"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            onChange={handleFile('poster')}
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Une vidéo ne peut pas être
                                            filigranée : c’est sa vignette qui
                                            porte la marque.
                                        </p>
                                    </div>
                                </>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="story-link">
                                    Lien (optionnel)
                                </Label>
                                <Input
                                    id="story-link"
                                    placeholder="/produit/mon-produit"
                                    value={data.link_url}
                                    onChange={(e) =>
                                        setData('link_url', e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="story-link-label">
                                        Texte du bouton
                                    </Label>
                                    <Input
                                        id="story-link-label"
                                        placeholder="Voir le produit"
                                        value={data.link_label}
                                        onChange={(e) =>
                                            setData(
                                                'link_label',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="story-position">
                                        Position
                                    </Label>
                                    <Input
                                        id="story-position"
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
                                    id="story-active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                <Label htmlFor="story-active">
                                    Visible sur la page d’accueil
                                </Label>
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    className="flex-1"
                                    disabled={processing}
                                >
                                    {processing
                                        ? 'Envoi…'
                                        : editing === null
                                          ? 'Publier'
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

AdminStories.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Statuts', href: admin.stories.index() },
    ],
};
