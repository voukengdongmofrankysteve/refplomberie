<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuoteResource;
use App\Models\Order;
use App\Models\Quote;
use App\Services\ListPdfService;
use App\Services\QuotePdfService;
use App\Services\StockReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class QuoteController extends Controller
{
    public function __construct(
        private readonly QuotePdfService $pdf,
        private readonly StockReservation $stock,
    ) {}

    /**
     * File des devis, filtrable par statut et par référence/client.
     */
    public function index(Request $request): InertiaResponse
    {
        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();

        $quotes = Quote::query()
            ->with(['items', 'user'])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('reference', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_company', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%"),
            ))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/quotes/index', [
            'quotes' => QuoteResource::collection($quotes)->response()->getData(true),
            'statuses' => QuoteStatus::options(),
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    /**
     * La file de devis filtrée, en PDF — mêmes filtres que l'écran.
     */
    public function exportPdf(Request $request, ListPdfService $pdf): Response
    {
        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();

        $quotes = Quote::query()
            ->with(['items', 'user'])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('reference', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_company', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%"),
            ))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        $document = $pdf->render(
            title: 'Devis',
            subtitle: $quotes->count().' devis',
            columns: [
                ['label' => 'Référence'],
                ['label' => 'Client'],
                ['label' => 'Société'],
                ['label' => 'Téléphone'],
                ['label' => 'Statut'],
                ['label' => 'Total', 'align' => 'right'],
                ['label' => 'Validité'],
                ['label' => 'Date'],
            ],
            rows: $quotes->map(fn (Quote $quote): array => [
                $quote->reference,
                $quote->customer_name,
                $quote->customer_company ?? '',
                $quote->customer_phone,
                $quote->status->label(),
                number_format($quote->total, 0, ',', ' ').' FCFA',
                $quote->valid_until?->format('d/m/Y') ?? '',
                $quote->created_at?->format('d/m/Y H:i') ?? '',
            ])->all(),
            orientation: 'landscape',
        );

        return $document->download(ListPdfService::filename('devis'));
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(QuoteStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['status' => 'statut', 'note' => 'note']);

        $quote->update($data);

        return back()->with('success', "Devis {$quote->reference} mis à jour.");
    }

    /** Le PDF vu depuis le back-office, sans jeton : l'admin est authentifié. */
    public function pdf(Quote $quote): Response
    {
        return $this->pdf->forQuote($quote)
            ->download(QuotePdfService::filename($quote->reference));
    }

    /**
     * Transforme un devis accepté en commande.
     *
     * Les lignes sont recopiées telles quelles : c'est bien le montant annoncé
     * au client qui est engagé, même si le tarif a bougé depuis.
     */
    public function convert(Quote $quote): RedirectResponse
    {
        $quote->loadMissing('items');

        $lines = $quote->items
            ->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])
            ->all();

        try {
            $order = DB::transaction(function () use ($quote, $lines): Order {
                // Un devis n'engageait aucun stock : la conversion est le
                // premier moment où la commande devient réelle.
                $this->stock->reserve($lines);

                $order = Order::create([
                    'reference' => Order::generateReference(),
                    'user_id' => $quote->user_id,
                    'customer_name' => $quote->customer_name,
                    'customer_phone' => $quote->customer_phone,
                    'customer_address' => $quote->customer_address,
                    'status' => OrderStatus::Confirmed,
                    'subtotal' => $quote->subtotal,
                    'shipping' => $quote->shipping,
                    'discount' => 0,
                    'total' => $quote->total,
                    'note' => "Issu du devis {$quote->reference}.",
                ]);

                $order->items()->createMany($lines);

                $quote->update(['status' => QuoteStatus::Accepted]);

                return $order;
            });
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.orders.show', $order)
            ->with('success', "Commande {$order->reference} créée depuis le devis {$quote->reference}.");
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $reference = $quote->reference;
        $quote->delete();

        return back()->with('success', "Devis {$reference} supprimé.");
    }
}
