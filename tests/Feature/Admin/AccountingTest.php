<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tableau de bord comptable : chiffre d'affaires, achats et marge, réservés
 * à l'administrateur.
 */
class AccountingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_the_summary_counts_revenue_and_costs_but_excludes_cancelled_orders_and_unreceived_purchase_orders(): void
    {
        $this->makeOrder(50000, OrderStatus::Confirmed);
        $this->makeOrder(20000, OrderStatus::Cancelled);
        $this->makePurchaseOrder(15000, PurchaseOrderStatus::Received);
        $this->makePurchaseOrder(9000, PurchaseOrderStatus::Ordered);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.accounting.index'))
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('admin/accounting/index')
            ->where('summary.revenue', 50000)
            ->where('summary.costs', 15000)
            ->where('summary.margin', 35000)
            ->where('summary.ordersCount', 1)
            ->where('summary.purchaseOrdersCount', 1));
    }

    public function test_the_export_is_a_csv_ledger_with_debit_and_credit_lines(): void
    {
        $order = $this->makeOrder(50000, OrderStatus::Confirmed);
        $purchaseOrder = $this->makePurchaseOrder(15000, PurchaseOrderStatus::Received);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.accounting.export'))
            ->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString($order->reference, $csv);
        $this->assertStringContainsString($purchaseOrder->reference, $csv);
        $this->assertStringContainsString('VENTES', $csv);
        $this->assertStringContainsString('ACHATS', $csv);
    }

    public function test_a_vendor_and_a_stock_manager_cannot_access_accounting(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);
        $stockManager = User::factory()->create(['role' => UserRole::StockManager]);

        $this->actingAs($vendor)->get(route('admin.accounting.index'))->assertForbidden();
        $this->actingAs($stockManager)->get(route('admin.accounting.index'))->assertForbidden();
    }

    private function makeOrder(int $total, OrderStatus $status): Order
    {
        return Order::create([
            'reference' => Order::generateReference(),
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '690000000',
            'status' => $status,
            'subtotal' => $total,
            'shipping' => 0,
            'total' => $total,
        ]);
    }

    private function makePurchaseOrder(int $total, PurchaseOrderStatus $status): PurchaseOrder
    {
        $supplier = Supplier::create(['name' => 'Fournisseur test']);

        return PurchaseOrder::create([
            'reference' => PurchaseOrder::generateReference(),
            'supplier_id' => $supplier->id,
            'status' => $status,
            'total' => $total,
            'received_at' => $status === PurchaseOrderStatus::Received ? now() : null,
        ]);
    }
}
