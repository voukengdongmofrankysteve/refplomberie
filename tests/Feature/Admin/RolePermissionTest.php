<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le back-office a trois rôles au-delà du client : vendeur, gestionnaire de
 * stock, et administrateur complet. Chacun n'ouvre que sa zone.
 */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vendor_can_open_their_allowed_pages(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)->get(route('admin.orders.index'))->assertOk();
        $this->actingAs($vendor)->get(route('admin.quotes.index'))->assertOk();
        $this->actingAs($vendor)->get(route('admin.technician-requests.index'))->assertOk();
        $this->actingAs($vendor)->get(route('admin.messages.index'))->assertOk();
        $this->actingAs($vendor)->get(route('admin.technicians.index'))->assertOk();
        $this->actingAs($vendor)->get(route('admin.promo-codes.index'))->assertOk();
        $this->actingAs($vendor)->get(route('admin.campaigns.index'))->assertOk();
    }

    public function test_a_vendor_is_denied_stock_and_admin_only_pages(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.catalog.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.flash-sales.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.faqs.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.stories.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.customers.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.audit-log.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.analytics.index'))->assertForbidden();
    }

    public function test_a_stock_manager_can_open_their_allowed_pages(): void
    {
        $stockManager = User::factory()->create(['role' => UserRole::StockManager]);

        $this->actingAs($stockManager)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($stockManager)->get(route('admin.catalog.index'))->assertOk();
        $this->actingAs($stockManager)->get(route('admin.flash-sales.index'))->assertOk();
        $this->actingAs($stockManager)->get(route('admin.faqs.index'))->assertOk();
        $this->actingAs($stockManager)->get(route('admin.stories.index'))->assertOk();
    }

    public function test_a_stock_manager_is_denied_vendor_and_admin_only_pages(): void
    {
        $stockManager = User::factory()->create(['role' => UserRole::StockManager]);

        $this->actingAs($stockManager)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($stockManager)->get(route('admin.quotes.index'))->assertForbidden();
        $this->actingAs($stockManager)->get(route('admin.customers.index'))->assertForbidden();
        $this->actingAs($stockManager)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($stockManager)->get(route('admin.audit-log.index'))->assertForbidden();
    }

    public function test_only_an_administrator_can_change_a_customer_role(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        $stockManager = User::factory()->create(['role' => UserRole::StockManager]);
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($vendor)
            ->put(route('admin.customers.update', $customer), ['role' => UserRole::Admin->value])
            ->assertForbidden();

        $this->actingAs($stockManager)
            ->put(route('admin.customers.update', $customer), ['role' => UserRole::Admin->value])
            ->assertForbidden();

        $this->assertSame(UserRole::Customer, $customer->fresh()->role);
    }

    public function test_a_vendors_action_is_recorded_in_the_audit_log(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);
        $product = Product::create([
            'category_id' => $category->id,
            'slug' => 'produit-test',
            'name' => 'Produit test',
            'description' => 'Description de test.',
            'price' => 10000,
            'image' => 'https://example.test/image.jpg',
            'stock' => 50,
            'is_active' => true,
        ]);
        $order = Order::create([
            'reference' => Order::generateReference(),
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '690000000',
            'status' => OrderStatus::Pending,
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

        $this->actingAs($vendor)->put(route('admin.orders.update', $order), [
            'status' => OrderStatus::Confirmed->value,
        ]);

        $log = AuditLog::where('auditable_type', Order::class)->sole();

        $this->assertSame($vendor->id, $log->user_id);
    }
}
