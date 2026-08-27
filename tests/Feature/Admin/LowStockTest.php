<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->category = Category::create(['slug' => 'outils', 'label' => 'Outils']);
    }

    public function test_the_dashboard_counts_and_lists_products_to_reorder(): void
    {
        $this->makeProduct('rupture', stock: 0, threshold: 5);
        $this->makeProduct('bas', stock: 3, threshold: 5);
        $this->makeProduct('au-seuil', stock: 5, threshold: 5);
        $this->makeProduct('confortable', stock: 40, threshold: 5);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.lowStock', 3)
                ->where('stats.outOfStock', 1)
                ->has('lowStockProducts', 3)
                // Le plus urgent en tête.
                ->where('lowStockProducts.0.slug', 'rupture')
                ->where('lowStockProducts.0.level', 'out')
                ->where('lowStockProducts.1.level', 'low'));
    }

    public function test_a_zero_threshold_disables_the_watch(): void
    {
        $this->makeProduct('ignore', stock: 0, threshold: 0);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('stats.lowStock', 0)
                ->has('lowStockProducts', 0));
    }

    public function test_the_catalogue_can_be_filtered_on_low_stock(): void
    {
        $this->makeProduct('bas', stock: 2, threshold: 5);
        $this->makeProduct('confortable', stock: 40, threshold: 5);

        $this->actingAs($this->admin)
            ->get(route('admin.products.index', ['stock' => 'low']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.slug', 'bas')
                ->where('products.data.0.stockLevel', 'low'));
    }

    public function test_the_threshold_is_saved_from_the_product_form(): void
    {
        $product = $this->makeProduct('cle', stock: 12, threshold: 5);

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'category_id' => $this->category->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'image' => $product->image,
                'stock' => 12,
                'low_stock_threshold' => 20,
                'is_active' => true,
            ])
            ->assertRedirect();

        $product->refresh();

        $this->assertSame(20, $product->low_stock_threshold);
        $this->assertSame('low', $product->stockLevel());
    }

    private function makeProduct(string $slug, int $stock, int $threshold): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'slug' => $slug,
            'name' => 'Produit '.$slug,
            'description' => 'Description de test.',
            'price' => 10000,
            'image' => 'https://example.test/image.jpg',
            'stock' => $stock,
            'low_stock_threshold' => $threshold,
            'is_active' => true,
        ]);
    }
}
