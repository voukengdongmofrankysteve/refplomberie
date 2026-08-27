import { Head, useForm } from '@inertiajs/react';
import { ExternalLink, ImagePlus, MapPin } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type Settings = {
    name: string;
    address: string;
    phone: string;
    whatsapp: string;
    email: string;
    hours: string;
    latitude: number | null;
    longitude: number | null;
    map_zoom: number;
    meta_title: string | null;
    meta_description: string | null;
    meta_keywords: string | null;
    google_site_verification: string | null;
    is_indexable: boolean;
    facebook_url: string | null;
    instagram_url: string | null;
    linkedin_url: string | null;
    og_image: string | null;
    ogImageUrl: string | null;
};

type Props = {
    settings: Settings;
    mapEmbedUrl: string;
    mapLinkUrl: string;
    seoUrls: { robots: string; sitemap: string };
};

const TEXTAREA_CLASS =
    'w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function AdminSettings({
    settings,
    mapEmbedUrl,
    mapLinkUrl,
    seoUrls,
}: Props) {
    const [ogPreview, setOgPreview] = useState<string | null>(null);

    const { data, setData, post, processing, errors, transform } = useForm({
        name: settings.name,
        address: settings.address,
        phone: settings.phone,
        whatsapp: settings.whatsapp,
        email: settings.email,
        hours: settings.hours,
        latitude: settings.latitude,
        longitude: settings.longitude,
        map_zoom: settings.map_zoom,
        meta_title: settings.meta_title ?? '',
        meta_description: settings.meta_description ?? '',
        meta_keywords: settings.meta_keywords ?? '',
        google_site_verification: settings.google_site_verification ?? '',
        is_indexable: settings.is_indexable,
        facebook_url: settings.facebook_url ?? '',
        instagram_url: settings.instagram_url ?? '',
        linkedin_url: settings.linkedin_url ?? '',
        og_image_file: null as File | null,
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        // Une image peut accompagner l'envoi : multipart + `_method` pour PUT.
        transform((payload) => ({ ...payload, _method: 'put' }));

        post(admin.settings.update().url, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const handleOgFile = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] ?? null;

        setData('og_image_file', file);
        setOgPreview(file ? URL.createObjectURL(file) : null);
    };

    // Aperçu recalculé localement pendant la saisie des coordonnées.
    const livePreviewUrl =
        data.latitude !== null && data.longitude !== null
            ? `https://maps.google.com/maps?q=${data.latitude},${data.longitude}&z=${data.map_zoom}&hl=fr&ie=UTF8&iwloc=&output=embed`
            : mapEmbedUrl;

    return (
        <>
            <Head title="Réglages de la boutique" />

            <form onSubmit={handleSubmit} className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">
                            Réglages de la boutique
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Coordonnées, localisation sur la carte et
                            référencement de la vitrine.
                        </p>
                    </div>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Enregistrement…' : 'Enregistrer'}
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Coordonnées */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card">
                        <header className="border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="text-sm font-semibold">
                                Coordonnées
                            </h2>
                        </header>

                        <div className="grid gap-4 p-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom de la boutique</Label>
                                <Input
                                    id="name"
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
                                <Label htmlFor="address">Adresse</Label>
                                <Input
                                    id="address"
                                    required
                                    value={data.address}
                                    onChange={(e) =>
                                        setData('address', e.target.value)
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Sert de repli sur la carte si aucune
                                    coordonnée GPS n’est renseignée.
                                </p>
                                {errors.address && (
                                    <p className="text-xs text-destructive">
                                        {errors.address}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Téléphone</Label>
                                    <Input
                                        id="phone"
                                        required
                                        value={data.phone}
                                        onChange={(e) =>
                                            setData('phone', e.target.value)
                                        }
                                    />
                                    {errors.phone && (
                                        <p className="text-xs text-destructive">
                                            {errors.phone}
                                        </p>
                                    )}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="whatsapp">
                                        Numéro WhatsApp
                                    </Label>
                                    <Input
                                        id="whatsapp"
                                        required
                                        inputMode="numeric"
                                        placeholder="237677259585"
                                        value={data.whatsapp}
                                        onChange={(e) =>
                                            setData('whatsapp', e.target.value)
                                        }
                                    />
                                    {errors.whatsapp && (
                                        <p className="text-xs text-destructive">
                                            {errors.whatsapp}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                />
                                {errors.email && (
                                    <p className="text-xs text-destructive">
                                        {errors.email}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="hours">Horaires</Label>
                                <Input
                                    id="hours"
                                    required
                                    value={data.hours}
                                    onChange={(e) =>
                                        setData('hours', e.target.value)
                                    }
                                />
                                {errors.hours && (
                                    <p className="text-xs text-destructive">
                                        {errors.hours}
                                    </p>
                                )}
                            </div>
                        </div>
                    </section>

                    {/* Localisation */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card">
                        <header className="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="flex items-center gap-2 text-sm font-semibold">
                                <MapPin className="size-4 text-primary" />
                                Localisation Google Maps
                            </h2>
                            <a
                                href={mapLinkUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                            >
                                Ouvrir
                                <ExternalLink className="size-3" />
                            </a>
                        </header>

                        <div className="grid gap-4 p-4">
                            <div className="grid gap-3 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="latitude">Latitude</Label>
                                    <Input
                                        id="latitude"
                                        type="number"
                                        step="0.0000001"
                                        min={-90}
                                        max={90}
                                        placeholder="3.8666"
                                        value={data.latitude ?? ''}
                                        onChange={(e) =>
                                            setData(
                                                'latitude',
                                                e.target.value === ''
                                                    ? null
                                                    : Number(e.target.value),
                                            )
                                        }
                                    />
                                    {errors.latitude && (
                                        <p className="text-xs text-destructive">
                                            {errors.latitude}
                                        </p>
                                    )}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="longitude">Longitude</Label>
                                    <Input
                                        id="longitude"
                                        type="number"
                                        step="0.0000001"
                                        min={-180}
                                        max={180}
                                        placeholder="11.5167"
                                        value={data.longitude ?? ''}
                                        onChange={(e) =>
                                            setData(
                                                'longitude',
                                                e.target.value === ''
                                                    ? null
                                                    : Number(e.target.value),
                                            )
                                        }
                                    />
                                    {errors.longitude && (
                                        <p className="text-xs text-destructive">
                                            {errors.longitude}
                                        </p>
                                    )}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="map_zoom">Zoom</Label>
                                    <Input
                                        id="map_zoom"
                                        type="number"
                                        min={1}
                                        max={21}
                                        required
                                        value={data.map_zoom}
                                        onChange={(e) =>
                                            setData(
                                                'map_zoom',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <p className="text-xs text-muted-foreground">
                                Sur Google Maps, faites un clic droit sur votre
                                magasin puis copiez les deux nombres proposés :
                                le premier est la latitude, le second la
                                longitude.
                            </p>

                            <div className="overflow-hidden rounded-lg border border-sidebar-border/70">
                                <iframe
                                    key={livePreviewUrl}
                                    title="Aperçu de la localisation"
                                    src={livePreviewUrl}
                                    className="h-64 w-full"
                                    loading="lazy"
                                    referrerPolicy="no-referrer-when-downgrade"
                                />
                            </div>
                        </div>
                    </section>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Référencement */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card">
                        <header className="border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="text-sm font-semibold">
                                Référencement
                            </h2>
                        </header>

                        <div className="grid gap-4 p-4">
                            <div className="grid gap-2">
                                <Label htmlFor="meta_title">
                                    Titre SEO ({data.meta_title.length}/70)
                                </Label>
                                <Input
                                    id="meta_title"
                                    maxLength={70}
                                    value={data.meta_title}
                                    onChange={(e) =>
                                        setData('meta_title', e.target.value)
                                    }
                                />
                                {errors.meta_title && (
                                    <p className="text-xs text-destructive">
                                        {errors.meta_title}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="meta_description">
                                    Description SEO (
                                    {data.meta_description.length}/320)
                                </Label>
                                <textarea
                                    id="meta_description"
                                    rows={3}
                                    maxLength={320}
                                    className={TEXTAREA_CLASS}
                                    value={data.meta_description}
                                    onChange={(e) =>
                                        setData(
                                            'meta_description',
                                            e.target.value,
                                        )
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Visible sous le titre dans les résultats
                                    Google. Entre 120 et 160 caractères est
                                    l’idéal.
                                </p>
                                {errors.meta_description && (
                                    <p className="text-xs text-destructive">
                                        {errors.meta_description}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="meta_keywords">Mots-clés</Label>
                                <Input
                                    id="meta_keywords"
                                    value={data.meta_keywords}
                                    onChange={(e) =>
                                        setData('meta_keywords', e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="google_site_verification">
                                    Code de vérification Google Search Console
                                </Label>
                                <Input
                                    id="google_site_verification"
                                    value={data.google_site_verification}
                                    onChange={(e) =>
                                        setData(
                                            'google_site_verification',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_indexable"
                                    checked={data.is_indexable}
                                    onCheckedChange={(checked) =>
                                        setData(
                                            'is_indexable',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label htmlFor="is_indexable">
                                    Autoriser l’indexation par les moteurs de
                                    recherche
                                </Label>
                            </div>

                            <div className="flex flex-wrap gap-3 rounded-lg bg-muted px-3 py-2 text-xs">
                                <a
                                    href={seoUrls.robots}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1 font-medium text-primary hover:underline"
                                >
                                    robots.txt
                                    <ExternalLink className="size-3" />
                                </a>
                                <a
                                    href={seoUrls.sitemap}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1 font-medium text-primary hover:underline"
                                >
                                    sitemap.xml
                                    <ExternalLink className="size-3" />
                                </a>
                                <span className="text-muted-foreground">
                                    générés automatiquement
                                </span>
                            </div>
                        </div>
                    </section>

                    {/* Partage social */}
                    <section className="rounded-xl border border-sidebar-border/70 bg-card">
                        <header className="border-b border-sidebar-border/70 px-4 py-3">
                            <h2 className="text-sm font-semibold">
                                Partage social
                            </h2>
                        </header>

                        <div className="grid gap-4 p-4">
                            <div className="grid gap-2">
                                <Label htmlFor="og_image_file">
                                    Image de partage par défaut
                                </Label>
                                <div className="flex flex-wrap items-center gap-4">
                                    <div className="flex h-24 w-44 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-dashed border-sidebar-border bg-muted">
                                        {(ogPreview ?? settings.ogImageUrl) ? (
                                            <img
                                                src={
                                                    ogPreview ??
                                                    settings.ogImageUrl ??
                                                    ''
                                                }
                                                alt="Aperçu du partage"
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <ImagePlus className="size-6 text-muted-foreground" />
                                        )}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Input
                                            id="og_image_file"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            onChange={handleOgFile}
                                            className="max-w-xs"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Affichée quand un lien du site est
                                            partagé sur WhatsApp, Facebook ou X.
                                            Format conseillé : 1200 × 630.
                                        </p>
                                    </div>
                                </div>
                                {errors.og_image_file && (
                                    <p className="text-xs text-destructive">
                                        {errors.og_image_file}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="facebook_url">Facebook</Label>
                                <Input
                                    id="facebook_url"
                                    type="url"
                                    placeholder="https://facebook.com/…"
                                    value={data.facebook_url}
                                    onChange={(e) =>
                                        setData('facebook_url', e.target.value)
                                    }
                                />
                                {errors.facebook_url && (
                                    <p className="text-xs text-destructive">
                                        {errors.facebook_url}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="instagram_url">Instagram</Label>
                                <Input
                                    id="instagram_url"
                                    type="url"
                                    placeholder="https://instagram.com/…"
                                    value={data.instagram_url}
                                    onChange={(e) =>
                                        setData('instagram_url', e.target.value)
                                    }
                                />
                                {errors.instagram_url && (
                                    <p className="text-xs text-destructive">
                                        {errors.instagram_url}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="linkedin_url">LinkedIn</Label>
                                <Input
                                    id="linkedin_url"
                                    type="url"
                                    placeholder="https://linkedin.com/company/…"
                                    value={data.linkedin_url}
                                    onChange={(e) =>
                                        setData('linkedin_url', e.target.value)
                                    }
                                />
                                {errors.linkedin_url && (
                                    <p className="text-xs text-destructive">
                                        {errors.linkedin_url}
                                    </p>
                                )}
                            </div>

                            <p className="text-xs text-muted-foreground">
                                Ces profils alimentent les données structurées
                                de la boutique, que Google utilise pour relier
                                votre site à vos pages sociales.
                            </p>
                        </div>
                    </section>
                </div>
            </form>
        </>
    );
}

AdminSettings.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Réglages', href: admin.settings.edit() },
    ],
};
