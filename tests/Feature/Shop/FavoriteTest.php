<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_toggle_a_favorite(): void
    {
        $product = $this->makeProduct();

        $this->post(route('favorites.toggle', $product))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_a_customer_can_add_then_remove_a_favorite(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)
            ->post(route('favorites.toggle', $product))
            ->assertRedirect();

        $this->assertTrue($user->favorites()->whereKey($product->id)->exists());

        $this->actingAs($user)
            ->post(route('favorites.toggle', $product))
            ->assertRedirect();

        $this->assertFalse($user->favorites()->whereKey($product->id)->exists());
    }

    public function test_favorites_are_shared_with_every_inertia_page(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $user->favorites()->attach($product);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertInertia(fn ($page) => $page->has('favorites', 1));
    }

    private function makeProduct(): Product
    {
        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);

        return Product::create([
            'category_id' => $category->id,
            'slug' => 'produit-test',
            'name' => 'Produit test',
            'description' => 'Description de test.',
            'price' => 10000,
            'image' => 'https://example.test/image.jpg',
            'stock' => 5,
            'is_active' => true,
        ]);
    }
}
