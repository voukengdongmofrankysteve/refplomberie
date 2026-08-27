<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(): Response
    {
        $orders = PurchaseOrder::with('supplier')
            ->withCount('items')
            ->latest()
            ->get()
            ->map(fn (PurchaseOrder $order): array => [
                'id' => $order->id,
                'reference' => $order->reference,
                'supplier' => $order->supplier->name,
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'expectedAt' => $order->expected_at?->toDateString(),
                'total' => $order->total,
                'itemsCount' => $order->items_count,
            ]);

        return Inertia::render('admin/purchase-orders/index', [
            'orders' => $orders,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['supplier', 'items.product']);

        return Inertia::render('admin/purchase-orders/show', [
            'order' => [
                'id' => $purchaseOrder->id,
                'reference' => $purchaseOrder->reference,
                'supplier' => $purchaseOrder->supplier->name,
                'status' => $purchaseOrder->status->value,
                'statusLabel' => $purchaseOrder->status->label(),
                'expectedAt' => $purchaseOrder->expected_at?->toDateString(),
                'note' => $purchaseOrder->note,
                'total' => $purchaseOrder->total,
                'isEditable' => $purchaseOrder->isEditable(),
            ],
            'items' => $purchaseOrder->items->map(fn ($item): array => [
                'id' => $item->id,
                'productName' => $item->product_name,
                'unitCost' => $item->unit_cost,
                'quantity' => $item->quantity,
                'lineTotal' => $item->line_total,
            ]),
            'statuses' => PurchaseOrderStatus::options(),
            'catalog' => Product::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'price']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'expected_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['supplier_id' => 'fournisseur', 'expected_at' => 'date attendue']);

        $order = PurchaseOrder::create([
            ...$data,
            'reference' => PurchaseOrder::generateReference(),
        ]);

        return to_route('admin.purchase-orders.show', $order)
            ->with('success', "Bon de commande {$order->reference} créé. Ajoutez-y des produits.");
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status->isTerminal()) {
            return back()->with('error', 'Ce bon de commande est clos et ne peut plus être modifié.');
        }

        $data = $request->validate([
            'status' => ['required', Rule::enum(PurchaseOrderStatus::class)],
            'expected_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['status' => 'statut', 'expected_at' => 'date attendue']);

        $newStatus = PurchaseOrderStatus::from($data['status']);

        if ($newStatus === PurchaseOrderStatus::Received) {
            $data['received_at'] = now();
        }

        DB::transaction(function () use ($purchaseOrder, $newStatus, $data): void {
            if ($newStatus === PurchaseOrderStatus::Received) {
                $this->incrementStock($purchaseOrder);
            }

            $purchaseOrder->update($data);
        });

        return back()->with('success', "Bon de commande {$purchaseOrder->reference} mis à jour.");
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::Draft) {
            return back()->with('error', 'Seul un bon de commande en brouillon peut être supprimé.');
        }

        $reference = $purchaseOrder->reference;
        $purchaseOrder->delete();

        return to_route('admin.purchase-orders.index')
            ->with('success', "Bon de commande {$reference} supprimé.");
    }

    /**
     * Incrémente le stock de chaque ligne — verrouillé par produit contre
     * une double réception concurrente. Le passage à « reçu » est terminal
     * (voir `isTerminal()`), donc cette méthode ne s'exécute jamais deux
     * fois pour le même bon.
     */
    private function incrementStock(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing('items');

        foreach ($purchaseOrder->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            Product::whereKey($item->product_id)->lockForUpdate()->increment('stock', $item->quantity);
        }
    }
}
