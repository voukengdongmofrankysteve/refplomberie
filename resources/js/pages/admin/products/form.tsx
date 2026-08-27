import { Head, useForm } from '@inertiajs/react';
import { ImagePlus, Plus, Trash2, Upload } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type PriceTierInput = {
    min_qty: number;
    max_qty: number | null;
    price: number;
};

type GalleryImage = {
    /** Valeur stockée en base (chemin sur le disque public, ou URL externe). */
    path: string;
    /** URL affichable. */
    url: string;
};

type ProductInput = {
    id: number;
    category_id: number;
    slug: string;
    name: string;
    description: string;
    video_url: string | null;
    price: number;
    old_price: number | null;
    badge: string | null;
    warranty_badges: string[];
    image: string;
    imageUrl: string;
    stock: number;
    low_stock_threshold: number;
    is_active: boolean;
    images: GalleryImage[];
    price_tiers: PriceTierInput[];
};

type Props = {
    /** `null` en création. */
    product: ProductInput | null;
    categories: { value: number; label: string; slug: string }[];
    warrantyBadges: { value: string; label: string }[];
};

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

const TEXTAREA_CLASS =
    'w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

/** Transforme un libellé en identifiant URL. */
function slugify(value: string): string {
    return (
        value
            .normalize('NFD')
            // Retire les diacritiques (é → e, à → a…).
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
    );
}

export default function AdminProductForm({
    product,
    categories,
    warrantyBadges,
}: Props) {
    const isEdit = product !== null;

    // Le slug suit le nom saisi, jusqu'à ce que l'admin le modifie lui-même.
    const [slugLocked, setSlugLocked] = useState(false);

    // Aperçus locaux des fichiers choisis, avant envoi au serveur.
    const [mainPreview, setMainPreview] = useState<string | null>(null);
    const [galleryPreviews, setGalleryPreviews] = useState<string[]>([]);

    const { data, setData, post, processing, errors, transform } = useForm({
        category_id: product?.category_id ?? categories[0]?.value ?? 0,
        name: product?.name ?? '',
        slug: product?.slug ?? '',
        description: product?.description ?? '',
        video_url: product?.video_url ?? null,
        price: product?.price ?? 0,
        old_price: product?.old_price ?? null,
        badge: product?.badge ?? '',
        warranty_badges: product?.warranty_badges ?? [],
        stock: product?.stock ?? 0,
        low_stock_threshold: product?.low_stock_threshold ?? 5,
        is_active: product?.is_active ?? true,
        image: product?.image ?? '',
        image_file: null as File | null,
        images: (product?.images ?? []) as GalleryImage[],
        gallery_files: [] as File[],
        price_tiers: product?.price_tiers ?? [],
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        // Les fichiers imposent un envoi multipart : Laravel reçoit donc un
        // POST, et `_method` rétablit le verbe PUT lors d'une modification.
        transform((payload) => ({
            ...payload,
            // Seuls les chemins voyagent : les URL d'affichage restent locales.
            images: payload.images.map((image) => image.path),
            ...(isEdit ? { _method: 'put' } : {}),
        }));

        post(
            isEdit
                ? admin.products.update(product.slug).url
                : admin.products.store().url,
            { forceFormData: true, preserveScroll: true },
        );
    };

    const handleMainFile = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] ?? null;

        setData('image_file', file);
        setMainPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleGalleryFiles = (e: ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(e.target.files ?? []);

        setData('gallery_files', [...data.gallery_files, ...files]);
        setGalleryPreviews((previews) => [
            ...previews,
            ...files.map((file) => URL.createObjectURL(file)),
        ]);

        e.target.value = '';
    };

    const removePendingFile = (index: number) => {
        setData(
            'gallery_files',
            data.gallery_files.filter((_, i) => i !== index),
        );
        setGalleryPreviews((previews) =>
            previews.filter((_, i) => i !== index),
        );
    };

    const updateTier = (index: number, patch: Partial<PriceTierInput>): void =>
        setData(
            'price_tiers',
            data.price_tiers.map((tier, i) =>
                i === index ? { ...tier, ...patch } : tier,
            ),
        );

    const currentMainImage = mainPreview ?? product?.imageUrl ?? null;

    return (
        <>
            <Head title={isEdit ? 'Modifier un produit' : 'Nouveau produit'} />

            <form onSubmit={handleSubmit} className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">
                            {isEdit ? data.name : 'Nouveau produit'}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Les images téléversées sont optimisées et marquées
                            du filigrane Réf.Plomberie automatiquement.
                        </p>
                    </div>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Enregistrement…' : 'Enregistrer'}
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Informations générales */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card lg:col-span-2">
                        <header className="border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="text-sm font-semibold">
                                Informations
                            </h2>
                        </header>

                        <div className="grid gap-4 p-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom du produit</Label>
                                <Input
                                    id="name"
                                    required
                                    value={data.name}
                                    onChange={(e) => {
                                        setData('name', e.target.value);

                                        if (!slugLocked) {
                                            setData(
                                                'slug',
                                                slugify(e.target.value),
                                            );
                                        }
                                    }}
                                />
                                {errors.name && (
                                    <p className="text-xs text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="slug">
                                        Identifiant URL
                                    </Label>
                                    {slugLocked && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setSlugLocked(false);
                                                setData(
                                                    'slug',
                                                    slugify(data.name),
                                                );
                                            }}
                                            className="text-xs font-medium text-primary hover:underline"
                                        >
                                            Régénérer depuis le nom
                                        </button>
                                    )}
                                </div>
                                <Input
                                    id="slug"
                                    required
                                    value={data.slug}
                                    onChange={(e) => {
                                        // Saisie manuelle : le slug cesse de
                                        // suivre le nom du produit.
                                        setSlugLocked(true);
                                        setData('slug', e.target.value);
                                    }}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Adresse publique : /produit/
                                    {data.slug || '…'}
                                    {isEdit &&
                                        ' — la modifier change l’adresse publique du produit.'}
                                </p>
                                {errors.slug && (
                                    <p className="text-xs text-destructive">
                                        {errors.slug}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea
                                    id="description"
                                    rows={4}
                                    required
                                    className={TEXTAREA_CLASS}
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                />
                                {errors.description && (
                                    <p className="text-xs text-destructive">
                                        {errors.description}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="video_url">
                                    Vidéo tutoriel{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (facultatif)
                                    </span>
                                </Label>
                                <Input
                                    id="video_url"
                                    type="url"
                                    placeholder="https://www.youtube.com/watch?v=…"
                                    value={data.video_url ?? ''}
                                    onChange={(e) =>
                                        setData(
                                            'video_url',
                                            e.target.value || null,
                                        )
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Un lien YouTube s’affiche intégré sur la
                                    fiche produit. Laissez vide pour ne rien
                                    afficher.
                                </p>
                                {errors.video_url && (
                                    <p className="text-xs text-destructive">
                                        {errors.video_url}
                                    </p>
                                )}
                            </div>

                            {/* Image principale */}
                            <div className="grid gap-2">
                                <Label htmlFor="image_file">
                                    Image principale
                                </Label>
                                <div className="flex flex-wrap items-center gap-4">
                                    <div className="flex size-28 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-sidebar-border bg-muted">
                                        {currentMainImage ? (
                                            <img
                                                src={currentMainImage}
                                                alt="Aperçu de l’image principale"
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <ImagePlus className="size-6 text-muted-foreground" />
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Input
                                            id="image_file"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            onChange={handleMainFile}
                                            className="max-w-xs"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            JPEG, PNG ou WebP — 8 Mo maximum.
                                            Redimensionnée en 1600 px et
                                            convertie en WebP.
                                        </p>
                                    </div>
                                </div>
                                {(errors.image_file || errors.image) && (
                                    <p className="text-xs text-destructive">
                                        {errors.image_file ?? errors.image}
                                    </p>
                                )}
                            </div>
                        </div>
                    </section>

                    {/* Prix et disponibilité */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card">
                        <header className="border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="text-sm font-semibold">
                                Prix &amp; disponibilité
                            </h2>
                        </header>

                        <div className="grid gap-4 p-4">
                            <div className="grid gap-2">
                                <Label htmlFor="category">Catégorie</Label>
                                <select
                                    id="category"
                                    className={SELECT_CLASS}
                                    value={data.category_id}
                                    onChange={(e) =>
                                        setData(
                                            'category_id',
                                            Number(e.target.value),
                                        )
                                    }
                                >
                                    {categories.map((c) => (
                                        <option key={c.value} value={c.value}>
                                            {c.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="price">Prix (FCFA)</Label>
                                <Input
                                    id="price"
                                    type="number"
                                    min={0}
                                    required
                                    value={data.price}
                                    onChange={(e) =>
                                        setData('price', Number(e.target.value))
                                    }
                                />
                                {errors.price && (
                                    <p className="text-xs text-destructive">
                                        {errors.price}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="old_price">
                                    Ancien prix (optionnel)
                                </Label>
                                <Input
                                    id="old_price"
                                    type="number"
                                    min={0}
                                    value={data.old_price ?? ''}
                                    onChange={(e) =>
                                        setData(
                                            'old_price',
                                            e.target.value === ''
                                                ? null
                                                : Number(e.target.value),
                                        )
                                    }
                                />
                                {errors.old_price && (
                                    <p className="text-xs text-destructive">
                                        {errors.old_price}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="stock">Stock</Label>
                                <Input
                                    id="stock"
                                    type="number"
                                    min={0}
                                    required
                                    value={data.stock}
                                    onChange={(e) =>
                                        setData('stock', Number(e.target.value))
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="low-stock">
                                    Seuil d’alerte
                                </Label>
                                <Input
                                    id="low-stock"
                                    type="number"
                                    min={0}
                                    required
                                    value={data.low_stock_threshold}
                                    onChange={(e) =>
                                        setData(
                                            'low_stock_threshold',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Au-dessous de ce stock, le produit remonte
                                    dans l’alerte du tableau de bord. 0
                                    désactive la surveillance.
                                </p>
                                {errors.low_stock_threshold && (
                                    <p className="text-xs text-destructive">
                                        {errors.low_stock_threshold}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="badge">
                                    Badge (Promo, Nouveau…)
                                </Label>
                                <Input
                                    id="badge"
                                    value={data.badge ?? ''}
                                    onChange={(e) =>
                                        setData('badge', e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label>Garantie et authenticité</Label>
                                <div className="space-y-2">
                                    {warrantyBadges.map((option) => (
                                        <div
                                            key={option.value}
                                            className="flex items-center gap-2"
                                        >
                                            <Checkbox
                                                id={`warranty-${option.value}`}
                                                checked={data.warranty_badges.includes(
                                                    option.value,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    setData(
                                                        'warranty_badges',
                                                        checked === true
                                                            ? [
                                                                  ...data.warranty_badges,
                                                                  option.value,
                                                              ]
                                                            : data.warranty_badges.filter(
                                                                  (value) =>
                                                                      value !==
                                                                      option.value,
                                                              ),
                                                    )
                                                }
                                            />
                                            <Label
                                                htmlFor={`warranty-${option.value}`}
                                                className="font-normal"
                                            >
                                                {option.label}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                <Label htmlFor="is_active">
                                    Visible dans la boutique
                                </Label>
                            </div>
                        </div>
                    </section>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Galerie */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card">
                        <header className="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="text-sm font-semibold">Galerie</h2>
                            <Label
                                htmlFor="gallery_files"
                                className="inline-flex cursor-pointer items-center gap-2 rounded-md bg-secondary px-3 py-1.5 text-xs font-medium text-secondary-foreground hover:bg-secondary/80"
                            >
                                <Upload className="size-4" />
                                Ajouter des images
                            </Label>
                            <input
                                id="gallery_files"
                                type="file"
                                multiple
                                accept="image/jpeg,image/png,image/webp"
                                onChange={handleGalleryFiles}
                                className="sr-only"
                            />
                        </header>

                        <div className="p-4">
                            {data.images.length === 0 &&
                                galleryPreviews.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        Aucune image secondaire. L’image
                                        principale sera utilisée seule.
                                    </p>
                                )}

                            <div className="grid grid-cols-3 gap-3 sm:grid-cols-4">
                                {/* Images déjà enregistrées */}
                                {data.images.map((image, index) => (
                                    <figure
                                        key={image.path}
                                        className="group relative aspect-square overflow-hidden rounded-lg border border-sidebar-border/70"
                                    >
                                        <img
                                            src={image.url}
                                            alt=""
                                            className="size-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setData(
                                                    'images',
                                                    data.images.filter(
                                                        (_, i) => i !== index,
                                                    ),
                                                )
                                            }
                                            className="absolute top-1 right-1 rounded-md bg-background/90 p-1 opacity-0 transition-opacity group-hover:opacity-100"
                                            aria-label="Retirer cette image"
                                        >
                                            <Trash2 className="size-4 text-destructive" />
                                        </button>
                                    </figure>
                                ))}

                                {/* Fichiers en attente d'envoi */}
                                {galleryPreviews.map((preview, index) => (
                                    <figure
                                        key={preview}
                                        className="group relative aspect-square overflow-hidden rounded-lg border border-dashed border-primary"
                                    >
                                        <img
                                            src={preview}
                                            alt=""
                                            className="size-full object-cover"
                                        />
                                        <figcaption className="absolute inset-x-0 bottom-0 bg-primary/90 py-0.5 text-center text-[10px] font-medium text-primary-foreground">
                                            À envoyer
                                        </figcaption>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                removePendingFile(index)
                                            }
                                            className="absolute top-1 right-1 rounded-md bg-background/90 p-1"
                                            aria-label="Annuler cet envoi"
                                        >
                                            <Trash2 className="size-4 text-destructive" />
                                        </button>
                                    </figure>
                                ))}
                            </div>

                            {errors.gallery_files && (
                                <p className="mt-2 text-xs text-destructive">
                                    {errors.gallery_files}
                                </p>
                            )}
                        </div>
                    </section>

                    {/* Tarifs dégressifs */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card">
                        <header className="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="text-sm font-semibold">
                                Tarifs dégressifs
                            </h2>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                onClick={() =>
                                    setData('price_tiers', [
                                        ...data.price_tiers,
                                        {
                                            min_qty: 1,
                                            max_qty: null,
                                            price: data.price,
                                        },
                                    ])
                                }
                            >
                                <Plus className="size-4" />
                                Ajouter
                            </Button>
                        </header>

                        <div className="space-y-3 p-4">
                            {data.price_tiers.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    Aucun palier. Le prix unitaire s’applique
                                    quelle que soit la quantité.
                                </p>
                            )}

                            {data.price_tiers.map((tier, index) => (
                                <div
                                    key={index}
                                    className="grid grid-cols-[1fr_1fr_1fr_auto] items-end gap-2"
                                >
                                    <div className="grid gap-1">
                                        <Label
                                            htmlFor={`tier-min-${index}`}
                                            className="text-xs"
                                        >
                                            Qté min
                                        </Label>
                                        <Input
                                            id={`tier-min-${index}`}
                                            type="number"
                                            min={1}
                                            value={tier.min_qty}
                                            onChange={(e) =>
                                                updateTier(index, {
                                                    min_qty: Number(
                                                        e.target.value,
                                                    ),
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-1">
                                        <Label
                                            htmlFor={`tier-max-${index}`}
                                            className="text-xs"
                                        >
                                            Qté max
                                        </Label>
                                        <Input
                                            id={`tier-max-${index}`}
                                            type="number"
                                            min={1}
                                            value={tier.max_qty ?? ''}
                                            placeholder="∞"
                                            onChange={(e) =>
                                                updateTier(index, {
                                                    max_qty:
                                                        e.target.value === ''
                                                            ? null
                                                            : Number(
                                                                  e.target
                                                                      .value,
                                                              ),
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-1">
                                        <Label
                                            htmlFor={`tier-price-${index}`}
                                            className="text-xs"
                                        >
                                            Prix
                                        </Label>
                                        <Input
                                            id={`tier-price-${index}`}
                                            type="number"
                                            min={0}
                                            value={tier.price}
                                            onChange={(e) =>
                                                updateTier(index, {
                                                    price: Number(
                                                        e.target.value,
                                                    ),
                                                })
                                            }
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() =>
                                            setData(
                                                'price_tiers',
                                                data.price_tiers.filter(
                                                    (_, i) => i !== index,
                                                ),
                                            )
                                        }
                                        aria-label="Retirer ce palier"
                                    >
                                        <Trash2 className="size-4 text-destructive" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </form>
        </>
    );
}

AdminProductForm.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Produits', href: admin.products.index() },
    ],
};
