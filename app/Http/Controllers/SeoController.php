<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * robots.txt — généré pour refléter l'URL réelle du site et l'état
     * « indexable » défini dans les réglages de la boutique.
     */
    public function robots(): Response
    {
        $store = StoreSetting::current();

        $lines = ['User-agent: *'];

        if ($store->is_indexable) {
            $lines[] = 'Allow: /';
            $lines[] = '';
            // Espaces privés et points d'entrée d'authentification.
            foreach (['/admin', '/dashboard', '/mes-favoris', '/mes-commandes', '/mes-interventions', '/settings', '/login', '/register', '/password'] as $path) {
                $lines[] = 'Disallow: '.$path;
            }
            $lines[] = '';
            $lines[] = 'Sitemap: '.route('sitemap');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * sitemap.xml — accueil + toutes les fiches produit actives.
     */
    public function sitemap(): Response
    {
        $products = Product::query()
            ->active()
            ->orderBy('id')
            ->get(['slug', 'updated_at']);

        $urls = [[
            'loc' => route('home'),
            'lastmod' => ($products->max('updated_at') ?? now())->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ]];

        foreach ($products as $product) {
            $urls[] = [
                'loc' => route('shop.product', $product->slug),
                'lastmod' => $product->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '    <url>'."\n"
                .'        <loc>'.e($url['loc']).'</loc>'."\n"
                .'        <lastmod>'.$url['lastmod'].'</lastmod>'."\n"
                .'        <changefreq>'.$url['changefreq'].'</changefreq>'."\n"
                .'        <priority>'.$url['priority'].'</priority>'."\n"
                .'    </url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
