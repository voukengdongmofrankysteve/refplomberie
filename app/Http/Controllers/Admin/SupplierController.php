<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/suppliers/index', [
            'suppliers' => Supplier::withCount('purchaseOrders')
                ->orderBy('name')
                ->get()
                ->map(fn (Supplier $supplier): array => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'contactName' => $supplier->contact_name,
                    'phone' => $supplier->phone,
                    'email' => $supplier->email,
                    'address' => $supplier->address,
                    'notes' => $supplier->notes,
                    'purchaseOrdersCount' => $supplier->purchase_orders_count,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));

        return back()->with('success', "Fournisseur « {$supplier->name} » ajouté.");
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return back()->with('success', "Fournisseur « {$supplier->name} » mis à jour.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', "« {$supplier->name} » a des bons de commande et ne peut pas être supprimé.");
        }

        $name = $supplier->name;
        $supplier->delete();

        return back()->with('success', "Fournisseur « {$name} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'name' => 'nom',
            'contact_name' => 'contact',
            'phone' => 'téléphone',
            'email' => 'email',
            'address' => 'adresse',
            'notes' => 'notes',
        ]);
    }
}
