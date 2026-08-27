<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseOrderItemController extends Controller
{
    public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $purchaseOrder->isEditable()) {
            return back()->with('error', 'Ce bon de commande est clos et ne peut plus être modifié.');
        }

        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'integer', 'min:0'],
        ], attributes: ['product_id' => 'produit', 'quantity' => 'quantité', 'unit_cost' => 'coût unitaire']);

        $product = Product::findOrFail($data['product_id']);

        $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_cost' => $data['unit_cost'],
            'quantity' => $data['quantity'],
            'line_total' => $data['unit_cost'] * $data['quantity'],
        ]);

        $purchaseOrder->recomputeTotal();

        return back()->with('success', "« {$product->name} » ajouté au bon de commande.");
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderItem $item): RedirectResponse
    {
        abort_unless($item->purchase_order_id === $purchaseOrder->id, 404);

        if (! $purchaseOrder->isEditable()) {
            return back()->with('error', 'Ce bon de commande est clos et ne peut plus être modifié.');
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'integer', 'min:0'],
        ], attributes: ['quantity' => 'quantité', 'unit_cost' => 'coût unitaire']);

        $item->update([
            ...$data,
            'line_total' => $data['unit_cost'] * $data['quantity'],
        ]);

        $purchaseOrder->recomputeTotal();

        return back()->with('success', "« {$item->product_name} » mis à jour.");
    }

    public function destroy(PurchaseOrder $purchaseOrder, PurchaseOrderItem $item): RedirectResponse
    {
        abort_unless($item->purchase_order_id === $purchaseOrder->id, 404);

        if (! $purchaseOrder->isEditable()) {
            return back()->with('error', 'Ce bon de commande est clos et ne peut plus être modifié.');
        }

        $name = $item->product_name;
        $item->delete();

        $purchaseOrder->recomputeTotal();

        return back()->with('success', "« {$name} » retiré du bon de commande.");
    }
}
