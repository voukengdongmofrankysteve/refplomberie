<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        StoreSetting::forgetCurrent();

        StoreSetting::create([
            'name' => 'Réf. Plomberie — Yaoundé',
            'address' => 'Avenue Kennedy, Yaoundé, Cameroun',
            'phone' => '+237 677 259 585',
            'whatsapp' => '237677259585',
            'email' => 'contact@refplomberie.cm',
            'hours' => 'Lun–Sam : 7h – 18h',
            'latitude' => 3.8666,
            'longitude' => 11.5167,
            'map_zoom' => 16,
            'meta_title' => 'Réf. Plomberie — Matériaux & Équipements',
            'meta_description' => 'Robinetterie, tuyauterie et sanitaire au Cameroun.',
            'facebook_url' => 'https://facebook.com/refplomberie',
            'is_indexable' => true,
        ]);

        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);

        $this->product = Product::create([
            'category_id' => $category->id,
            'slug' => 'cle-a-molette-pro',
            'name' => 'Clé à molette pro',
            'description' => 'Clé à molette professionnelle en acier chromé.',
            'price' => 15000,
            'image' => 'https://example.test/cle.jpg',
            'stock' => 7,
            'is_active' => true,
        ]);
    }

    public function test_the_home_page_carries_general_and_social_meta_tags(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('<meta name="description"', escape: false)
            ->assertSee('Robinetterie, tuyauterie et sanitaire au Cameroun.', escape: false)
            ->assertSee('<link rel="canonical"', escape: false)
            ->assertSee('content="index, follow', escape: false)
            ->assertSee('property="og:site_name"', escape: false)
            ->assertSee('property="og:title"', escape: false)
            ->assertSee('property="og:image"', escape: false)
            ->assertSee('property="og:locale" content="fr_FR"', escape: false)
            ->assertSee('name="twitter:card" content="summary_large_image"', escape: false)
            ->assertSee('<html lang="fr">', escape: false);
    }

    public function test_the_home_page_title_comes_from_the_back_office(): void
    {
        $this->assertSame(
            'Réf. Plomberie — Matériaux & Équipements',
            $this->titleOf(route('home')),
        );
    }

    public function test_a_product_page_is_titled_after_the_product(): void
    {
        $this->assertSame(
            'Clé à molette pro — '.config('app.name'),
            $this->titleOf(route('shop.product', $this->product)),
        );
    }

    /** Titre rendu côté serveur, tel que le voit un robot. */
    private function titleOf(string $url): string
    {
        preg_match(
            '/<title[^>]*>(.*?)<\/title>/s',
            (string) $this->get($url)->getContent(),
            $matches,
        );

        return html_entity_decode($matches[1] ?? '');
    }

    public function test_the_home_page_exposes_local_business_structured_data(): void
    {
        $html = $this->get(route('home'))->getContent();

        $this->assertStringContainsString('"@type":"HardwareStore"', $html);
        $this->assertStringContainsString('"@type":"GeoCoordinates"', $html);
        $this->assertStringContainsString('"latitude":3.8666', $html);
        $this->assertStringContainsString('https://facebook.com/refplomberie', $html);
    }

    public function test_a_product_page_carries_its_own_social_meta_tags(): void
    {
        $html = $this->get(route('shop.product', $this->product))->getContent();

        $this->assertStringContainsString('property="og:type" content="product"', $html);
        $this->assertStringContainsString('Clé à molette professionnelle en acier chromé.', $html);
        $this->assertStringContainsString('property="product:price:amount" content="15000"', $html);
        $this->assertStringContainsString('property="product:price:currency" content="XAF"', $html);
        $this->assertStringContainsString('property="product:availability" content="in stock"', $html);
        $this->assertStringContainsString('name="twitter:image"', $html);
        $this->assertStringContainsString(route('shop.product', $this->product), $html);
    }

    public function test_a_product_page_exposes_product_structured_data(): void
    {
        $html = $this->get(route('shop.product', $this->product))->getContent();

        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringContainsString('"@type":"Offer"', $html);
        $this->assertStringContainsString('"priceCurrency":"XAF"', $html);
        $this->assertStringContainsString('"availability":"https://schema.org/InStock"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
    }

    public function test_an_out_of_stock_product_is_reported_as_such(): void
    {
        $this->product->update(['stock' => 0]);

        $html = $this->get(route('shop.product', $this->product))->getContent();

        $this->assertStringContainsString('content="out of stock"', $html);
        $this->assertStringContainsString('"availability":"https://schema.org/OutOfStock"', $html);
    }

    public function test_every_page_carries_exactly_one_description_and_one_title(): void
    {
        foreach ([route('home'), route('shop.product', $this->product)] as $url) {
            $html = $this->get($url)->getContent();

            $this->assertSame(
                1,
                preg_match_all('/<meta name="description"/', $html),
                "Balise description dupliquée sur {$url}.",
            );
            $this->assertSame(
                1,
                preg_match_all('/<title[^>]*>/', $html),
                "Balise title dupliquée sur {$url}.",
            );
        }
    }

    public function test_private_areas_are_excluded_from_search_engines(): void
    {
        // L'écran de connexion se visite déconnecté ; un visiteur authentifié
        // y serait redirigé vers son tableau de bord.
        $this->assertStringContainsString(
            'content="noindex, nofollow"',
            $this->get(route('login'))->getContent(),
            'La page de connexion devrait être exclue des index.',
        );

        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([route('dashboard'), route('admin.dashboard')] as $url) {
            $html = $this->actingAs($admin)->get($url)->getContent();

            $this->assertStringContainsString(
                'content="noindex, nofollow"',
                $html,
                "{$url} devrait être exclue des index.",
            );
        }
    }

    public function test_robots_txt_points_at_the_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.route('sitemap'))
            ->assertSee('Disallow: /admin');
    }

    public function test_robots_txt_blocks_everything_when_indexing_is_disabled(): void
    {
        StoreSetting::query()->update(['is_indexable' => false]);
        StoreSetting::forgetCurrent();

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /')
            ->assertDontSee('Sitemap:');

        $this->assertStringContainsString(
            'content="noindex, nofollow"',
            $this->get(route('home'))->getContent(),
        );
    }

    public function test_the_sitemap_lists_the_home_page_and_active_products(): void
    {
        Product::create([
            'category_id' => $this->product->category_id,
            'slug' => 'produit-masque',
            'name' => 'Produit masqué',
            'description' => 'Hors catalogue.',
            'price' => 1000,
            'image' => 'https://example.test/x.jpg',
            'stock' => 1,
            'is_active' => false,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();

        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString(route('home'), $xml);
        $this->assertStringContainsString('cle-a-molette-pro', $xml);
        // Un produit masqué n'a rien à faire dans le sitemap.
        $this->assertStringNotContainsString('produit-masque', $xml);
    }
}
