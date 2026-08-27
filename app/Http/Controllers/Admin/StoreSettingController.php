<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreSettingController extends Controller
{
    public function __construct(private readonly ProductImageService $images) {}

    public function edit(): Response
    {
        $store = StoreSetting::current();

        return Inertia::render('admin/settings/index', [
            'settings' => [
                'name' => $store->name,
                'address' => $store->address,
                'phone' => $store->phone,
                'whatsapp' => $store->whatsapp,
                'email' => $store->email,
                'hours' => $store->hours,
                'latitude' => $store->latitude,
                'longitude' => $store->longitude,
                'map_zoom' => $store->map_zoom,
                'meta_title' => $store->meta_title,
                'meta_description' => $store->meta_description,
                'meta_keywords' => $store->meta_keywords,
                'google_site_verification' => $store->google_site_verification,
                'is_indexable' => $store->is_indexable,
                'facebook_url' => $store->facebook_url,
                'instagram_url' => $store->instagram_url,
                'linkedin_url' => $store->linkedin_url,
                'og_image' => $store->og_image,
                'ogImageUrl' => ProductImageService::url($store->og_image),
            ],
            // Aperçu live de la carte telle qu'elle apparaît sur la vitrine.
            'mapEmbedUrl' => $store->mapEmbedUrl(),
            'mapLinkUrl' => $store->mapLinkUrl(),
            'seoUrls' => [
                'robots' => route('robots'),
                'sitemap' => route('sitemap'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'email' => ['required', 'email', 'max:255'],
            'hours' => ['required', 'string', 'max:120'],

            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_zoom' => ['required', 'integer', 'min:1', 'max:21'],

            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'google_site_verification' => ['nullable', 'string', 'max:120'],
            'is_indexable' => ['required', 'boolean'],

            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],

            'og_image_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ], attributes: [
            'name' => 'nom de la boutique',
            'address' => 'adresse',
            'phone' => 'téléphone',
            'whatsapp' => 'numéro WhatsApp',
            'hours' => 'horaires',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'map_zoom' => 'niveau de zoom',
            'meta_title' => 'titre SEO',
            'meta_description' => 'description SEO',
        ], messages: [
            'whatsapp.regex' => 'Le numéro WhatsApp ne doit contenir que des chiffres, indicatif compris (ex. 237677259585).',
            'meta_title.max' => 'Le titre SEO dépasse 70 caractères : Google le tronquerait.',
        ]);

        $store = StoreSetting::query()->first() ?? new StoreSetting;

        if ($request->hasFile('og_image_file')) {
            $previous = $store->og_image;
            $data['og_image'] = $this->images->store($request->file('og_image_file'));
            $this->images->delete($previous);
        }

        unset($data['og_image_file']);

        $store->fill($data)->save();

        StoreSetting::forgetCurrent();

        return back()->with('success', 'Réglages de la boutique enregistrés.');
    }
}
