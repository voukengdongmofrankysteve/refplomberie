<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FlashSaleTest extends TestCase
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

    public function test_an_administrator_creates_a_flash_sale(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.flash-sales.store'), [
                'title' => 'Vente du week-end',
                'starts_at' => now()->toDateTimeString(),
                'ends_at' => now()->addDay()->toDateTimeString(),
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertSame('Vente du week-end', FlashSale::sole()->title);
    }

    public function test_the_end_date_must_be_after_the_start_date(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.flash-sales.store'), [
                'title' => 'Vente incohérente',
                'starts_at' => now()->addDay()->toDateTimeString(),
                'ends_at' => now()->toDateTimeString(),
                'is_active' => true,
            ])
            ->assertSessionHasErrors('ends_at');

        $this->assertSame(0, FlashSale::count());
    }

    public function test_a_product_can_be_added_with_a_discounted_price(): void
    {
        $sale = $this->makeSale();
        $product = $this->makeProduct('Tuyau PVC', 10000);

        $this->actingAs($this->admin)
            ->post(route('admin.flash-sales.products.store', $sale), [
                'product_id' => $product->id,
                'sale_price' => 7000,
            ])
            ->assertRedirect();

        $this->assertSame(7000, $sale->products()->sole()->pivot->sale_price);
    }

    public function test_a_sale_price_above_the_product_price_is_refused(): void
    {
        $sale = $this->makeSale();
        $product = $this->makeProduct('Tuyau PVC', 10000);

        $this->actingAs($this->admin)
            ->post(route('admin.flash-sales.products.store', $sale), [
                'product_id' => $product->id,
                'sale_price' => 10000,
            ])
            ->assertSessionHasErrors('sale_price');

        $this->assertSame(0, $sale->products()->count());
    }

    public function test_the_same_product_cannot_be_added_twice(): void
    {
        $sale = $this->makeSale();
        $product = $this->makeProduct('Tuyau PVC', 10000);
        $sale->products()->attach($product->id, ['sale_price' => 7000]);

        $this->actingAs($this->admin)
            ->post(route('admin.flash-sales.products.store', $sale), [
                'product_id' => $product->id,
                'sale_price' => 6000,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertSame(1, $sale->products()->count());
    }

    public function test_removing_a_product_from_the_sale(): void
    {
        $sale = $this->makeSale();
        $product = $this->makeProduct('Tuyau PVC', 10000);
        $sale->products()->attach($product->id, ['sale_price' => 7000]);

        $this->actingAs($this->admin)
            ->delete(route('admin.flash-sales.products.destroy', [$sale, $product]))
            ->assertRedirect();

        $this->assertSame(0, $sale->products()->count());
    }

    public function test_a_customer_cannot_manage_flash_sales(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('admin.flash-sales.index'))
            ->assertForbidden();
    }

    public function test_a_past_sale_never_reaches_the_home_page(): void
    {
        $product = $this->makeProduct('Tuyau PVC', 10000);

        $past = $this->makeSale(starts: now()->subDays(3), ends: now()->subDay());
        $past->products()->attach($product->id, ['sale_price' => 7000]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('flashSale', null));
    }

    public function test_a_sale_not_yet_started_reaches_the_home_page_as_upcoming(): void
    {
        $product = $this->makeProduct('Tuyau PVC', 10000);

        $future = $this->makeSale(starts: now()->addDay(), ends: now()->addDays(2));
        $future->products()->attach($product->id, ['sale_price' => 7000]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('flashSale.status', 'upcoming')
                ->where('flashSale.title', $future->title)
                ->where('flashSale.startsAt', $future->starts_at->toIso8601String()));
    }

    public function test_a_running_sale_takes_priority_over_an_upcoming_one(): void
    {
        $product = $this->makeProduct('Tuyau PVC', 10000);

        $future = $this->makeSale(starts: now()->addDay(), ends: now()->addDays(2));
        $future->products()->attach($product->id, ['sale_price' => 7000]);

        $current = $this->makeSale();
        $current->products()->attach($product->id, ['sale_price' => 6000]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('flashSale.status', 'running')
                ->where('flashSale.title', $current->title)
                ->where('flashSale.products.0.price', 6000)
                ->where('flashSale.products.0.originalPrice', 10000));
    }

    public function test_a_sale_deactivated_by_the_administrator_does_not_show_even_within_its_window(): void
    {
        $product = $this->makeProduct('Tuyau PVC', 10000);
        $sale = $this->makeSale();
        $sale->update(['is_active' => false]);
        $sale->products()->attach($product->id, ['sale_price' => 7000]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('flashSale', null));
    }

    public function test_placing_an_order_charges_the_flash_sale_price(): void
    {
        $product = $this->makeProduct('Tuyau PVC', 10000);
        $sale = $this->makeSale();
        $sale->products()->attach($product->id, ['sale_price' => 7000]);

        $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '690000000',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();

        $order = Order::sole();

        // Le prix de vente flash l'emporte sur le prix normal du produit,
        // même si le client n'a jamais vu ni transmis ce prix lui-même.
        $this->assertSame(7000, $order->items()->sole()->unit_price);
        $this->assertSame(7000, $order->subtotal);
    }

    public function test_the_flash_sale_price_never_applies_once_the_sale_has_ended(): void
    {
        $product = $this->makeProduct('Tuyau PVC', 10000);
        $sale = $this->makeSale(starts: now()->subDays(3), ends: now()->subDay());
        $sale->products()->attach($product->id, ['sale_price' => 7000]);

        $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '690000000',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $this->assertSame(10000, Order::sole()->items()->sole()->unit_price);
    }

    private function makeSale(?CarbonInterface $starts = null, ?CarbonInterface $ends = null): FlashSale
    {
        return FlashSale::create([
            'title' => 'Vente flash de test',
            'starts_at' => $starts ?? now()->subHour(),
            'ends_at' => $ends ?? now()->addHour(),
            'is_active' => true,
        ]);
    }

    private function makeProduct(string $name, int $price): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'slug' => Str::slug($name),
            'name' => $name,
            'description' => 'Description de test.',
            'price' => $price,
            'image' => 'https://example.test/image.jpg',
            'stock' => 50,
            'is_active' => true,
        ]);
    }
}
