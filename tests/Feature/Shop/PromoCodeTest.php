<?php

namespace Tests\Feature\Shop;

use App\Enums\PromoCodeType;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_percentage_code_reduces_the_order_total(): void
    {
        $product = $this->makeProduct(price: 10000);
        PromoCode::create([
            'code' => 'BIENVENUE10',
            'type' => PromoCodeType::Percent,
            'value' => 10,
        ]);

        $this->postOrder($product, quantity: 2, code: 'bienvenue10')
            ->assertRedirect();

        $order = Order::sole();

        $this->assertSame(20000, $order->subtotal);
        $this->assertSame(2000, $order->discount);
        $this->assertSame('BIENVENUE10', $order->promo_code);
        $this->assertSame(21500, $order->total);
    }

    public function test_a_fixed_amount_code_reduces_the_order_total(): void
    {
        $product = $this->makeProduct(price: 10000);
        PromoCode::create([
            'code' => 'MOINS5000',
            'type' => PromoCodeType::Amount,
            'value' => 5000,
        ]);

        $this->postOrder($product, quantity: 2, code: 'MOINS5000')->assertRedirect();

        $order = Order::sole();

        $this->assertSame(5000, $order->discount);
        $this->assertSame(18500, $order->total);
    }

    public function test_a_code_below_its_minimum_is_ignored(): void
    {
        $product = $this->makeProduct(price: 10000);
        PromoCode::create([
            'code' => 'GROSPANIER',
            'type' => PromoCodeType::Percent,
            'value' => 20,
            'min_subtotal' => 50000,
        ]);

        $this->postOrder($product, quantity: 1, code: 'GROSPANIER')->assertRedirect();

        $order = Order::sole();

        $this->assertSame(0, $order->discount);
        $this->assertNull($order->promo_code);
    }

    public function test_an_expired_or_exhausted_code_is_ignored(): void
    {
        $product = $this->makeProduct(price: 10000);
        PromoCode::create([
            'code' => 'PERIME',
            'type' => PromoCodeType::Percent,
            'value' => 50,
            'ends_at' => now()->subDay(),
        ]);
        PromoCode::create([
            'code' => 'EPUISE',
            'type' => PromoCodeType::Percent,
            'value' => 50,
            'max_uses' => 1,
            'used_count' => 1,
        ]);

        foreach (['PERIME', 'EPUISE'] as $code) {
            $this->postOrder($product, quantity: 1, code: $code)->assertRedirect();
        }

        $this->assertSame(0, Order::sum('discount'));
    }

    public function test_a_discount_never_exceeds_the_subtotal(): void
    {
        $product = $this->makeProduct(price: 10000);
        PromoCode::create([
            'code' => 'ENORME',
            'type' => PromoCodeType::Amount,
            'value' => 999999,
        ]);

        $this->postOrder($product, quantity: 1, code: 'ENORME')->assertRedirect();

        $order = Order::sole();

        // La remise est plafonnée : le total reste les seuls frais de port.
        $this->assertSame(10000, $order->discount);
        $this->assertSame(3500, $order->total);
    }

    public function test_using_a_code_increments_its_counter(): void
    {
        $product = $this->makeProduct(price: 10000);
        $promo = PromoCode::create([
            'code' => 'COMPTEUR',
            'type' => PromoCodeType::Percent,
            'value' => 10,
        ]);

        $this->postOrder($product, quantity: 1, code: 'COMPTEUR')->assertRedirect();

        $this->assertSame(1, $promo->fresh()->used_count);
    }

    public function test_free_shipping_is_judged_on_the_discounted_amount(): void
    {
        $product = $this->makeProduct(price: 52000);
        PromoCode::create([
            'code' => 'REMISE10',
            'type' => PromoCodeType::Percent,
            'value' => 10,
        ]);

        // 52 000 franchit le seuil, mais 46 800 une fois remisé : la livraison
        // redevient payante.
        $this->postOrder($product, quantity: 1, code: 'REMISE10')->assertRedirect();

        $order = Order::sole();

        $this->assertSame(5200, $order->discount);
        $this->assertSame(3500, $order->shipping);
        $this->assertSame(50300, $order->total);
    }

    public function test_the_check_endpoint_reports_a_valid_code(): void
    {
        PromoCode::create([
            'code' => 'BIENVENUE10',
            'label' => 'Offre de lancement',
            'type' => PromoCodeType::Percent,
            'value' => 10,
        ]);

        $this->getJson(route('promo-codes.check', [
            'code' => 'bienvenue10',
            'subtotal' => 20000,
        ]))->assertOk()->assertJson([
            'valid' => true,
            'code' => 'BIENVENUE10',
            'discount' => 2000,
        ]);
    }

    public function test_the_check_endpoint_reports_an_unknown_code(): void
    {
        $this->getJson(route('promo-codes.check', [
            'code' => 'INEXISTANT',
            'subtotal' => 20000,
        ]))->assertOk()->assertJson(['valid' => false]);
    }

    private function postOrder(Product $product, int $quantity, string $code)
    {
        return $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'promo_code' => $code,
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
        ]);
    }

    private function makeProduct(int $price): Product
    {
        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);

        return Product::create([
            'category_id' => $category->id,
            'slug' => 'produit-test-'.uniqid(),
            'name' => 'Produit test',
            'description' => 'Description de test.',
            'price' => $price,
            'image' => 'https://example.test/image.jpg',
            'stock' => 50,
            'is_active' => true,
        ]);
    }
}
