<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_product_page_exposes_an_absolute_share_url(): void
    {
        $product = $this->makeProduct();

        $this->get(route('shop.product', $product))
            ->assertInertia(fn ($page) => $page
                // WhatsApp et Facebook résolvent l'URL depuis leurs serveurs :
                // un chemin relatif ne leur dirait rien.
                ->where('shareUrl', route('shop.product', $product)));
    }

    public function test_the_share_preview_image_is_absolute_in_the_head(): void
    {
        $product = $this->makeProduct();

        $html = $this->get(route('shop.product', $product))->getContent();

        // C'est cette balise qui produit la vignette dans la conversation.
        $this->assertMatchesRegularExpression(
            '/<meta property="og:image" content="https?:\/\/[^"]+"/',
            $html,
        );
        $this->assertStringContainsString('property="og:type" content="product"', $html);
    }

    private function makeProduct(): Product
    {
        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);

        return Product::create([
            'category_id' => $category->id,
            'slug' => 'cle-a-molette',
            'name' => 'Clé à molette',
            'description' => 'Clé à molette professionnelle.',
            'price' => 15000,
            'image' => 'products/cle.webp',
            'stock' => 10,
            'is_active' => true,
        ]);
    }
}
