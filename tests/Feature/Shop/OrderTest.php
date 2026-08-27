<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_place_an_order(): void
    {
        $product = $this->makeProduct();

        $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'customer_address' => 'Bastos, Yaoundé',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertRedirect();

        $order = Order::sole();

        $this->assertNull($order->user_id);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(20000, $order->subtotal);
        $this->assertSame(3500, $order->shipping);
        $this->assertSame(23500, $order->total);
        $this->assertSame(1, $order->items()->count());
    }

    public function test_the_server_applies_the_price_tier_and_ignores_client_prices(): void
    {
        $product = $this->makeProduct();
        $product->priceTiers()->createMany([
            ['min_qty' => 1, 'max_qty' => 9, 'price' => 10000],
            ['min_qty' => 10, 'max_qty' => null, 'price' => 8000],
        ]);

        // Le panier n'envoie que le produit et la quantité : aucun prix.
        $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 10]],
        ])->assertRedirect();

        $item = Order::sole()->items()->sole();

        $this->assertSame(8000, $item->unit_price);
        $this->assertSame(80000, $item->line_total);
    }

    public function test_free_shipping_applies_above_the_threshold(): void
    {
        $product = $this->makeProduct(price: 60000);

        $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();

        $order = Order::sole();

        $this->assertSame(0, $order->shipping);
        $this->assertSame(60000, $order->total);
    }

    public function test_an_order_placed_while_signed_in_is_attached_to_the_account(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)
            ->post(route('orders.store'), [
                'customer_name' => $user->name,
                'customer_phone' => '+237 690 00 00 00',
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ])
            ->assertRedirect();

        $this->assertSame($user->id, Order::sole()->user_id);
    }

    public function test_the_order_reference_is_flashed_for_the_whatsapp_message(): void
    {
        $product = $this->makeProduct();

        $response = $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $reference = Order::sole()->reference;

        // Le panier lit cette valeur pour l'insérer dans le message WhatsApp.
        $response->assertSessionHas('orderReference', $reference);

        $this->get(route('home'))->assertInertia(
            fn ($page) => $page->where('flash.orderReference', $reference),
        );
    }

    public function test_an_order_requires_a_customer_and_at_least_one_item(): void
    {
        $this->post(route('orders.store'), [])
            ->assertSessionHasErrors(['customer_name', 'customer_phone', 'items']);

        $this->assertSame(0, Order::count());
    }

    public function test_placing_an_order_reserves_the_stock(): void
    {
        $product = $this->makeProduct();

        $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertRedirect();

        $this->assertSame(47, $product->fresh()->stock);
    }

    public function test_an_order_is_refused_when_stock_is_insufficient(): void
    {
        $product = $this->makeProduct();
        $product->update(['stock' => 2]);

        $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ])->assertSessionHas('error');

        // Ni la commande, ni la décrémentation ne survivent à un refus : la
        // transaction s'est entièrement défaite.
        $this->assertSame(0, Order::count());
        $this->assertSame(2, $product->fresh()->stock);
    }

    private function makeProduct(int $price = 10000): Product
    {
        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);

        return Product::create([
            'category_id' => $category->id,
            'slug' => 'produit-test',
            'name' => 'Produit test',
            'description' => 'Description de test.',
            'price' => $price,
            'image' => 'https://example.test/image.jpg',
            'stock' => 50,
            'is_active' => true,
        ]);
    }
}
