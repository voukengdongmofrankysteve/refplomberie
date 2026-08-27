<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le stock d'une commande suit son statut : réservé à la commande, rendu à
 * l'annulation, repris si elle est ranimée, rendu à la suppression.
 */
class OrderStockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_cancelling_an_order_returns_its_stock(): void
    {
        $product = $this->makeProduct(stock: 50);
        $order = $this->makeOrder($product, quantity: 5, stock: 45);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Cancelled->value,
                'note' => '',
            ])
            ->assertRedirect();

        $this->assertSame(50, $product->fresh()->stock);
    }

    public function test_reactivating_a_cancelled_order_reserves_the_stock_again(): void
    {
        $product = $this->makeProduct(stock: 45);
        $order = $this->makeOrder($product, quantity: 5, stock: 45, status: OrderStatus::Cancelled);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Confirmed->value,
                'note' => '',
            ])
            ->assertRedirect();

        $this->assertSame(40, $product->fresh()->stock);
    }

    public function test_reactivating_a_cancelled_order_fails_if_the_stock_is_gone(): void
    {
        $product = $this->makeProduct(stock: 2);
        $order = $this->makeOrder($product, quantity: 5, stock: 2, status: OrderStatus::Cancelled);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Confirmed->value,
                'note' => '',
            ])
            ->assertSessionHas('error');

        // Le statut n'a pas bougé non plus : tout ou rien.
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_deleting_an_active_order_returns_its_stock(): void
    {
        $product = $this->makeProduct(stock: 50);
        $order = $this->makeOrder($product, quantity: 5, stock: 45);

        $this->actingAs($this->admin)
            ->delete(route('admin.orders.destroy', $order))
            ->assertRedirect();

        $this->assertSame(50, $product->fresh()->stock);
    }

    public function test_deleting_an_already_cancelled_order_does_not_return_stock_twice(): void
    {
        $product = $this->makeProduct(stock: 50);
        $order = $this->makeOrder($product, quantity: 5, stock: 50, status: OrderStatus::Cancelled);

        $this->actingAs($this->admin)
            ->delete(route('admin.orders.destroy', $order))
            ->assertRedirect();

        $this->assertSame(50, $product->fresh()->stock);
    }

    private function makeProduct(int $stock): Product
    {
        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);

        return Product::create([
            'category_id' => $category->id,
            'slug' => 'produit-test',
            'name' => 'Produit test',
            'description' => 'Description de test.',
            'price' => 10000,
            'image' => 'https://example.test/image.jpg',
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    /**
     * Commande déjà passée, avec son stock déjà mis à l'état voulu par
     * l'appelant : ce test-ci ne rejoue pas le parcours de commande, il pose
     * directement la situation à observer.
     */
    private function makeOrder(
        Product $product,
        int $quantity,
        int $stock,
        OrderStatus $status = OrderStatus::Pending,
    ): Order {
        $product->update(['stock' => $stock]);

        $order = Order::create([
            'reference' => Order::generateReference(),
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '690000000',
            'status' => $status,
            'subtotal' => $product->price * $quantity,
            'shipping' => 0,
            'total' => $product->price * $quantity,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => $quantity,
            'line_total' => $product->price * $quantity,
        ]);

        return $order;
    }
}
