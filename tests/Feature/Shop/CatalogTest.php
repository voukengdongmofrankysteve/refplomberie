<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_active_products_from_the_database(): void
    {
        $category = Category::create([
            'slug' => 'robinetterie',
            'label' => 'Robinetterie',
        ]);

        $visible = $this->makeProduct($category, 'Mitigeur visible');
        $hidden = $this->makeProduct($category, 'Produit masqué', isActive: false);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('shop/home')
                    ->has('products', 1)
                    ->where('products.0.name', $visible->name)
                    ->where('products.0.slug', $visible->slug),
            );

        $this->assertDatabaseHas('products', ['id' => $hidden->id]);
    }

    public function test_product_page_is_resolved_by_slug(): void
    {
        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);
        $product = $this->makeProduct($category, 'Pince à sertir');

        $this->get(route('shop.product', $product))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('shop/product')
                    ->where('product.slug', $product->slug),
            );
    }

    public function test_inactive_and_unknown_products_return_404(): void
    {
        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);
        $hidden = $this->makeProduct($category, 'Masqué', isActive: false);

        $this->get(route('shop.product', $hidden))->assertNotFound();
        $this->get('/produit/inexistant')->assertNotFound();
    }

    private function makeProduct(
        Category $category,
        string $name,
        bool $isActive = true,
    ): Product {
        return Product::create([
            'category_id' => $category->id,
            'slug' => str($name)->slug()->value(),
            'name' => $name,
            'description' => 'Description de test.',
            'price' => 10000,
            'image' => 'https://example.test/image.jpg',
            'stock' => 5,
            'is_active' => $isActive,
        ]);
    }
}
