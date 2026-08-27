<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FlashSaleProductController extends Controller
{
    public function store(Request $request, FlashSale $flashSale): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id'),
                Rule::unique('flash_sale_products', 'product_id')
                    ->where('flash_sale_id', $flashSale->id),
            ],
            'sale_price' => ['required', 'integer', 'min:1'],
        ], attributes: ['product_id' => 'produit', 'sale_price' => 'prix de vente flash']);

        $product = Product::findOrFail($data['product_id']);
        $this->assertDiscounted($product, $data['sale_price']);

        $flashSale->products()->attach($product->id, [
            'sale_price' => $data['sale_price'],
            'position' => (int) $flashSale->products()->max('position') + 1,
        ]);

        return back()->with('success', "« {$product->name} » ajouté à la vente.");
    }

    public function update(Request $request, FlashSale $flashSale, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'sale_price' => ['required', 'integer', 'min:1'],
        ], attributes: ['sale_price' => 'prix de vente flash']);

        $this->assertDiscounted($product, $data['sale_price']);

        $flashSale->products()->updateExistingPivot($product->id, [
            'sale_price' => $data['sale_price'],
        ]);

        return back()->with('success', "Prix de « {$product->name} » mis à jour.");
    }

    public function destroy(FlashSale $flashSale, Product $product): RedirectResponse
    {
        $flashSale->products()->detach($product->id);

        return back()->with('success', "« {$product->name} » retiré de la vente.");
    }

    /**
     * Une « vente flash » à un prix supérieur ou égal au prix courant ne
     * serait pas une vente : elle induirait le client en erreur.
     */
    private function assertDiscounted(Product $product, int $salePrice): void
    {
        if ($salePrice >= $product->price) {
            throw ValidationException::withMessages([
                'sale_price' => 'Le prix de vente flash doit être inférieur au prix actuel du produit ('
                    .number_format($product->price, 0, ',', ' ').' FCFA).',
            ]);
        }
    }
}
