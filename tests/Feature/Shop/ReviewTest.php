<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Un avis n'a de valeur que s'il vient d'un client qui a réellement acheté
 * le produit — pas de n'importe quel visiteur connecté.
 */
class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_publish_a_review(): void
    {
        $product = $this->makeProduct();

        $this->post(route('reviews.store', $product), [
            'rating' => 5,
            'body' => 'Excellent produit.',
        ])->assertRedirect(route('login'));

        $this->assertSame(0, Review::count());
    }

    public function test_a_customer_who_never_bought_the_product_cannot_publish_a_review(): void
    {
        $product = $this->makeProduct();

        $this->actingAs(User::factory()->create())
            ->post(route('reviews.store', $product), [
                'rating' => 5,
                'body' => 'Excellent produit.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Review::count());
    }

    /**
     * @return iterable<string, array{OrderStatus}>
     */
    public static function unconfirmedStatuses(): iterable
    {
        yield 'pending' => [OrderStatus::Pending];
        yield 'cancelled' => [OrderStatus::Cancelled];
    }

    #[DataProvider('unconfirmedStatuses')]
    public function test_a_pending_or_cancelled_order_does_not_unlock_a_review(OrderStatus $status): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->makeOrder($user, $product, $status);

        $this->actingAs($user)
            ->post(route('reviews.store', $product), [
                'rating' => 5,
                'body' => 'Excellent produit.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Review::count());
    }

    public function test_a_confirmed_purchase_unlocks_a_verified_review_which_updates_the_rating(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->makeOrder($user, $product, OrderStatus::Confirmed);

        $this->actingAs($user)
            ->post(route('reviews.store', $product), [
                'rating' => 4,
                'body' => 'Bon produit, livraison rapide.',
            ])
            ->assertRedirect();

        $review = Review::sole();
        $this->assertTrue($review->verified_purchase);

        $product->refresh();
        $this->assertSame(4.0, $product->rating);
        $this->assertSame(1, $product->reviews_count);
    }

    public function test_a_customer_can_only_review_a_product_once(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();
        $this->makeOrder($user, $product, OrderStatus::Confirmed);

        $this->actingAs($user)->post(route('reviews.store', $product), [
            'rating' => 5,
            'body' => 'Premier avis.',
        ]);

        $this->actingAs($user)
            ->post(route('reviews.store', $product), [
                'rating' => 1,
                'body' => 'Deuxième avis.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, Review::count());
    }

    public function test_the_product_page_exposes_published_reviews_marked_as_verified(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'body' => 'Parfait.',
            'verified_purchase' => true,
        ]);

        $this->get(route('shop.product', $product))
            ->assertInertia(
                fn ($page) => $page
                    ->has('reviews', 1)
                    ->where('reviews.0.text', 'Parfait.')
                    ->where('reviews.0.verifiedPurchase', true)
                    ->where('reviewGate', 'guest'),
            );
    }

    public function test_the_review_gate_reflects_purchase_and_review_state(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('shop.product', $product))
            ->assertInertia(fn ($page) => $page->where('reviewGate', 'not_purchased'));

        $this->makeOrder($user, $product, OrderStatus::Confirmed);

        $this->actingAs($user)
            ->get(route('shop.product', $product))
            ->assertInertia(fn ($page) => $page->where('reviewGate', 'can_review'));

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'body' => 'Parfait.',
            'verified_purchase' => true,
        ]);

        $this->actingAs($user)
            ->get(route('shop.product', $product))
            ->assertInertia(fn ($page) => $page->where('reviewGate', 'already_reviewed'));
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

    private function makeOrder(User $user, Product $product, OrderStatus $status): Order
    {
        $order = Order::create([
            'reference' => Order::generateReference(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '690000000',
            'status' => $status,
            'subtotal' => $product->price,
            'shipping' => 0,
            'total' => $product->price,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
            'line_total' => $product->price,
        ]);

        return $order;
    }
}
