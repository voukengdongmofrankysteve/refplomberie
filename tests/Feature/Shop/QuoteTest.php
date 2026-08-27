<?php

namespace Tests\Feature\Shop;

use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_request_a_quote_from_the_cart(): void
    {
        $product = $this->makeProduct();

        $response = $this->post(route('quotes.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'customer_company' => 'BTP Central Sarl',
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ]);

        $quote = Quote::sole();

        $response->assertRedirect()
            ->assertSessionHas('quoteReference', $quote->reference)
            ->assertSessionHas('quoteUrl');

        $this->assertSame(QuoteStatus::Draft, $quote->status);
        $this->assertSame('BTP Central Sarl', $quote->customer_company);
        $this->assertSame(30000, $quote->subtotal);
        $this->assertSame(1, $quote->items()->count());
        $this->assertTrue($quote->valid_until->isFuture());
    }

    public function test_a_quote_applies_the_price_tiers_like_an_order(): void
    {
        $product = $this->makeProduct();
        $product->priceTiers()->createMany([
            ['min_qty' => 1, 'max_qty' => 9, 'price' => 10000],
            ['min_qty' => 10, 'max_qty' => null, 'price' => 8000],
        ]);

        $this->post(route('quotes.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 10]],
        ])->assertRedirect();

        // Un devis doit annoncer exactement ce que la commande facturera.
        $this->assertSame(8000, Quote::sole()->items()->sole()->unit_price);
    }

    public function test_the_pdf_is_served_with_the_right_token(): void
    {
        $product = $this->makeProduct();

        $this->post(route('quotes.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $quote = Quote::sole();

        $response = $this->get(route('quotes.download', [
            'quote' => $quote->id,
            'token' => $quote->token,
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_pdf_is_refused_with_a_wrong_token(): void
    {
        $product = $this->makeProduct();

        $this->post(route('quotes.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $quote = Quote::sole();

        $this->get(route('quotes.download', [
            'quote' => $quote->id,
            'token' => str_repeat('x', 40),
        ]))->assertNotFound();
    }

    public function test_an_administrator_converts_a_quote_into_an_order(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $product = $this->makeProduct();

        $this->post(route('quotes.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $quote = Quote::sole();

        $this->actingAs($admin)
            ->post(route('admin.quotes.convert', $quote))
            ->assertRedirect();

        $order = Order::sole();

        // Le montant engagé est celui qui a été annoncé au client.
        $this->assertSame($quote->total, $order->total);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(QuoteStatus::Accepted, $quote->fresh()->status);

        // Un devis n'avait rien réservé : c'est la conversion qui décrémente.
        $this->assertSame(48, $product->fresh()->stock);
    }

    public function test_converting_a_quote_is_refused_if_the_stock_ran_out_since(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $product = $this->makeProduct();

        $this->post(route('quotes.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        // Le stock a fondu depuis l'établissement du devis.
        $product->update(['stock' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.quotes.convert', Quote::sole()))
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        $this->assertSame(QuoteStatus::Draft, Quote::sole()->status);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_a_quote_requires_a_customer_and_at_least_one_item(): void
    {
        $this->post(route('quotes.store'), [])
            ->assertSessionHasErrors(['customer_name', 'customer_phone', 'items']);

        $this->assertSame(0, Quote::count());
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
