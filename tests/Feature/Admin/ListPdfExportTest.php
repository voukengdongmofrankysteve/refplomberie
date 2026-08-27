<?php

namespace Tests\Feature\Admin;

use App\Enums\PromoCodeType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Export PDF des cinq listes du back-office : produits, commandes, devis,
 * codes promo et comptes.
 */
class ListPdfExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_a_customer_cannot_export_any_list(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('admin.products.export'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.orders.export'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.quotes.export'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.promo-codes.export'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.customers.export'))->assertForbidden();
    }

    public function test_the_catalog_exports_as_a_pdf_matching_the_search_filter(): void
    {
        $category = Category::create(['slug' => 'plomberie', 'label' => 'Plomberie']);

        $this->makeProduct($category, 'Tuyau PVC 100', 12000);
        $this->makeProduct($category, 'Disjoncteur 30A', 8000);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.products.export', ['search' => 'Tuyau']));

        $this->assertPdf($response);
    }

    public function test_orders_export_as_a_pdf(): void
    {
        $this->makeOrder('CMD-0001');

        $response = $this->actingAs($this->admin)->get(route('admin.orders.export'));

        $this->assertPdf($response);
    }

    public function test_quotes_export_as_a_pdf(): void
    {
        Quote::create([
            'reference' => 'DEV-0001',
            'token' => Str::random(40),
            'customer_name' => 'Awa Nkeng',
            'customer_phone' => '690000000',
            'subtotal' => 20000,
            'shipping' => 0,
            'total' => 20000,
            'valid_until' => now()->addDays(15),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.quotes.export'));

        $this->assertPdf($response);
    }

    public function test_promo_codes_export_as_a_pdf(): void
    {
        PromoCode::create([
            'code' => 'BIENVENUE10',
            'label' => 'Nouveaux clients',
            'type' => PromoCodeType::Percent->value,
            'value' => 10,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.promo-codes.export'));

        $this->assertPdf($response);
    }

    public function test_customers_export_as_a_pdf_matching_the_search_filter(): void
    {
        User::factory()->create(['name' => 'Jean Mbarga', 'email' => 'jean@example.com']);
        User::factory()->create(['name' => 'Paul Eto', 'email' => 'paul@example.com']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.customers.export', ['search' => 'Jean']));

        $this->assertPdf($response);
    }

    private function assertPdf(TestResponse $response): void
    {
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    private function makeProduct(Category $category, string $name, int $price): void
    {
        Product::create([
            'category_id' => $category->id,
            'slug' => Str::slug($name),
            'name' => $name,
            'description' => 'Description de test.',
            'price' => $price,
            'image' => 'https://example.test/image.jpg',
            'stock' => 20,
            'is_active' => true,
        ]);
    }

    private function makeOrder(string $reference): Order
    {
        return Order::create([
            'reference' => $reference,
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '690000000',
            'subtotal' => 10000,
            'shipping' => 0,
            'total' => 10000,
        ]);
    }
}
