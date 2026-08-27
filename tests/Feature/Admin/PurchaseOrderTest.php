<?php

namespace Tests\Feature\Admin;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fournisseurs et bons de commande : réapprovisionner le stock, avec une
 * traçabilité complète et sans double-incrémentation possible.
 */
class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $stockManager;

    private Category $category;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockManager = User::factory()->create(['role' => UserRole::StockManager]);
        $this->category = Category::create(['slug' => 'outils', 'label' => 'Outils']);
        $this->product = Product::create([
            'category_id' => $this->category->id,
            'slug' => 'produit-test',
            'name' => 'Produit test',
            'description' => 'Description de test.',
            'price' => 10000,
            'image' => 'https://example.test/image.jpg',
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    public function test_a_stock_manager_can_create_a_supplier_and_a_purchase_order(): void
    {
        $this->actingAs($this->stockManager)->post(route('admin.suppliers.store'), [
            'name' => 'Quincaillerie du Centre',
            'phone' => '690000000',
        ])->assertRedirect();

        $supplier = Supplier::sole();
        $this->assertSame('Quincaillerie du Centre', $supplier->name);

        $this->actingAs($this->stockManager)->post(route('admin.purchase-orders.store'), [
            'supplier_id' => $supplier->id,
        ])->assertRedirect();

        $order = PurchaseOrder::sole();
        $this->assertSame($supplier->id, $order->supplier_id);
        $this->assertSame(PurchaseOrderStatus::Draft, $order->status);
        $this->assertNotEmpty($order->reference);
    }

    public function test_adding_items_updates_the_order_total(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->stockManager)->post(
            route('admin.purchase-orders.items.store', $order),
            ['product_id' => $this->product->id, 'quantity' => 3, 'unit_cost' => 8000],
        )->assertRedirect();

        $this->assertSame(24000, $order->fresh()->total);
    }

    public function test_marking_an_order_as_received_increments_stock_once(): void
    {
        $order = $this->makeOrder();
        $order->items()->create([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_cost' => 8000,
            'quantity' => 10,
            'line_total' => 80000,
        ]);

        $this->actingAs($this->stockManager)->put(
            route('admin.purchase-orders.update', $order),
            ['status' => PurchaseOrderStatus::Received->value],
        )->assertRedirect();

        $this->assertSame(15, $this->product->fresh()->stock);
        $this->assertSame(PurchaseOrderStatus::Received, $order->fresh()->status);

        // Un bon reçu est clos : impossible de le repasser par un autre
        // statut pour re-déclencher l'incrément de stock.
        $this->actingAs($this->stockManager)->put(
            route('admin.purchase-orders.update', $order),
            ['status' => PurchaseOrderStatus::Ordered->value],
        );

        $this->assertSame(15, $this->product->fresh()->stock);
        $this->assertSame(PurchaseOrderStatus::Received, $order->fresh()->status);
    }

    public function test_items_cannot_be_added_once_the_order_is_received(): void
    {
        $order = $this->makeOrder(PurchaseOrderStatus::Received);

        $this->actingAs($this->stockManager)->post(
            route('admin.purchase-orders.items.store', $order),
            ['product_id' => $this->product->id, 'quantity' => 1, 'unit_cost' => 1000],
        );

        $this->assertSame(0, $order->items()->count());
    }

    public function test_a_draft_purchase_order_can_be_deleted_but_not_a_received_one(): void
    {
        $draft = $this->makeOrder();
        $received = $this->makeOrder(PurchaseOrderStatus::Received);

        $this->actingAs($this->stockManager)
            ->delete(route('admin.purchase-orders.destroy', $draft))
            ->assertRedirect();
        $this->assertModelMissing($draft);

        $this->actingAs($this->stockManager)
            ->delete(route('admin.purchase-orders.destroy', $received));
        $this->assertModelExists($received);
    }

    public function test_a_vendor_cannot_access_suppliers_or_purchase_orders(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)->get(route('admin.suppliers.index'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.purchase-orders.index'))->assertForbidden();
    }

    public function test_receiving_a_purchase_order_is_recorded_in_the_audit_log(): void
    {
        $order = $this->makeOrder();
        $order->items()->create([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_cost' => 8000,
            'quantity' => 2,
            'line_total' => 16000,
        ]);

        $this->actingAs($this->stockManager)->put(
            route('admin.purchase-orders.update', $order),
            ['status' => PurchaseOrderStatus::Received->value],
        );

        $log = AuditLog::where('auditable_type', PurchaseOrder::class)
            ->where('action', 'updated')
            ->sole();

        $this->assertSame($this->stockManager->id, $log->user_id);
        $this->assertSame(PurchaseOrderStatus::Received->value, $log->changes['status']['new']);
    }

    private function makeOrder(PurchaseOrderStatus $status = PurchaseOrderStatus::Draft): PurchaseOrder
    {
        $supplier = Supplier::create(['name' => 'Fournisseur test']);

        return PurchaseOrder::create([
            'reference' => PurchaseOrder::generateReference(),
            'supplier_id' => $supplier->id,
            'status' => $status,
        ]);
    }
}
