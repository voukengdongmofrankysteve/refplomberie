<?php

namespace App\Support;

use App\Models\StoreSetting;
use Illuminate\Support\Str;

/**
 * Métadonnées de référencement rendues **côté serveur**.
 *
 * Les robots sociaux (WhatsApp, Facebook, X, LinkedIn) n'exécutent pas de
 * JavaScript : les balises `<Head>` d'Inertia leur seraient invisibles. Cette
 * classe est donc alimentée par les contrôleurs puis rendue dans le layout
 * Blade, avant tout envoi au navigateur.
 */
class Seo
{
    private ?string $title = null;

    private ?string $description = null;

    private ?string $image = null;

    private string $type = 'website';

    private ?string $canonical = null;

    private bool $indexable = true;

    /** @var array<int, array<string, mixed>> */
    private array $schemas = [];

    /** @var array<string, string> */
    private array $productMeta = [];

    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description === null
            ? null
            : Str::limit(trim(preg_replace('/\s+/', ' ', $description) ?? ''), 300, '…');

        return $this;
    }

    public function image(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function canonical(string $url): self
    {
        $this->canonical = $url;

        return $this;
    }

    /** Retire la page des index (espaces privés, back-office). */
    public function noindex(): self
    {
        $this->indexable = false;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function schema(array $schema): self
    {
        $this->schemas[] = $schema;

        return $this;
    }

    /**
     * Balises `product:` d'Open Graph (prix, devise, disponibilité).
     *
     * @param  array<string, string>  $meta
     */
    public function productMeta(array $meta): self
    {
        $this->productMeta = $meta;

        return $this;
    }

    /**
     * Rend l'ensemble des balises `<head>`.
     */
    public function toHtml(): string
    {
        $store = StoreSetting::current();

        $title = $this->title ?? $store->meta_title ?? config('app.name');
        $description = $this->description
            ?? $store->meta_description
            ?? '';
        $image = $this->image ?? $store->shareImageUrl();
        $canonical = $this->canonical ?? url()->current();
        $indexable = $this->indexable && $store->is_indexable;

        $tags = [];

        $tags[] = $this->meta('description', $description);

        if ($store->meta_keywords) {
            $tags[] = $this->meta('keywords', $store->meta_keywords);
        }

        $tags[] = $this->meta(
            'robots',
            $indexable
                ? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'
                : 'noindex, nofollow',
        );
        $tags[] = $this->meta('author', $store->name);
        $tags[] = $this->meta('theme-color', '#25D366');
        $tags[] = '<link rel="canonical" href="'.e($canonical).'">';

        if ($store->google_site_verification) {
            $tags[] = $this->meta(
                'google-site-verification',
                $store->google_site_verification,
            );
        }

        // ── Open Graph ────────────────────────────────────────────────────
        $tags[] = $this->property('og:site_name', $store->name);
        $tags[] = $this->property('og:locale', 'fr_FR');
        $tags[] = $this->property('og:type', $this->type);
        $tags[] = $this->property('og:title', $title);
        $tags[] = $this->property('og:description', $description);
        $tags[] = $this->property('og:url', $canonical);
        $tags[] = $this->property('og:image', $image);
        $tags[] = $this->property('og:image:alt', $title);
        $tags[] = $this->property('og:image:width', '1200');
        $tags[] = $this->property('og:image:height', '630');

        foreach ($this->productMeta as $key => $value) {
            $tags[] = $this->property($key, $value);
        }

        // ── X / Twitter ───────────────────────────────────────────────────
        $tags[] = $this->meta('twitter:card', 'summary_large_image');
        $tags[] = $this->meta('twitter:title', $title);
        $tags[] = $this->meta('twitter:description', $description);
        $tags[] = $this->meta('twitter:image', $image);
        $tags[] = $this->meta('twitter:image:alt', $title);

        // ── Données structurées ───────────────────────────────────────────
        foreach ($this->schemas as $schema) {
            $tags[] = '<script type="application/ld+json">'
                .json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                .'</script>';
        }

        return implode("\n        ", $tags);
    }

    /** Titre complet de l'onglet, rendu côté serveur. */
    public function documentTitle(): string
    {
        $store = StoreSetting::current();

        return $this->title ?? $store->meta_title ?? (string) config('app.name');
    }

    /**
     * Fiche de l'entreprise, injectée sur toutes les pages publiques.
     *
     * @return array<string, mixed>
     */
    public static function organisationSchema(): array
    {
        $store = StoreSetting::current();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'HardwareStore',
            'name' => $store->name,
            'description' => $store->meta_description,
            'url' => url('/'),
            'image' => $store->shareImageUrl(),
            'telephone' => $store->phone,
            'email' => $store->email,
            'priceRange' => 'FCFA',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $store->address,
                'addressCountry' => 'CM',
            ],
            'openingHours' => $store->hours,
            'hasMap' => $store->mapLinkUrl(),
        ];

        if ($store->latitude !== null && $store->longitude !== null) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $store->latitude,
                'longitude' => $store->longitude,
            ];
        }

        if ($profiles = $store->socialProfiles()) {
            $schema['sameAs'] = $profiles;
        }

        return $schema;
    }

    private function meta(string $name, string $content): string
    {
        return '<meta name="'.e($name).'" content="'.e($content).'">';
    }

    private function property(string $property, string $content): string
    {
        return '<meta property="'.e($property).'" content="'.e($content).'">';
    }
}
