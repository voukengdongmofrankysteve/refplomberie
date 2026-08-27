<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CustomerNotifier;
use App\Services\ListPdfService;
use App\Services\QuotePdfService;
use App\Services\StockReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly QuotePdfService $pdf,
        private readonly CustomerNotifier $notifier,
        private readonly StockReservation $stock,
    ) {}

    /**
     * File des commandes, filtrable par statut et par référence/client.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();

        $orders = Order::query()
            ->with('user')
            ->withCount('items')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('reference', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%"),
            ))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/orders/index', [
            'orders' => OrderResource::collection($orders)->response()->getData(true),
            'statuses' => OrderStatus::options(),
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    /**
     * La file de commandes filtrée, en PDF — mêmes filtres que l'écran.
     *
     * Nommée à part de `pdf()` : celle-ci imprime une commande, celle-là en
     * liste plusieurs.
     */
    public function exportPdf(Request $request, ListPdfService $pdf): HttpResponse
    {
        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();

        $orders = Order::query()
            ->with('user')
            ->withCount('items')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('reference', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%"),
            ))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        $document = $pdf->render(
            title: 'Commandes',
            subtitle: $orders->count().' commande'.($orders->count() > 1 ? 's' : ''),
            columns: [
                ['label' => 'Référence'],
                ['label' => 'Client'],
                ['label' => 'Téléphone'],
                ['label' => 'Statut'],
                ['label' => 'Articles', 'align' => 'right'],
                ['label' => 'Total', 'align' => 'right'],
                ['label' => 'Date'],
            ],
            rows: $orders->map(fn (Order $order): array => [
                $order->reference,
                $order->customer_name,
                $order->customer_phone,
                $order->status->label(),
                (string) $order->items_count,
                number_format($order->total, 0, ',', ' ').' FCFA',
                $order->created_at?->format('d/m/Y H:i') ?? '',
            ])->all(),
            orientation: 'landscape',
        );

        return $document->download(ListPdfService::filename('commandes'));
    }

    public function show(Order $order): Response
    {
        $order->load(['items', 'user']);

        return Inertia::render('admin/orders/show', [
            'order' => (new OrderResource($order))->resolve(),
            'statuses' => OrderStatus::options(),
        ]);
    }

    /**
     * Mise à jour du statut et de la note interne.
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['status' => 'statut', 'note' => 'note']);

        $statusChanged = $order->status->value !== $data['status'];
        $previousStatus = $order->status;
        $newStatus = OrderStatus::from($data['status']);

        try {
            DB::transaction(function () use ($order, $data, $statusChanged, $previousStatus, $newStatus): void {
                $order->update($data);

                if (! $statusChanged) {
                    return;
                }

                if ($newStatus === OrderStatus::Cancelled) {
                    // Annulée : les articles retournent au stock.
                    $this->stock->release($order);
                } elseif ($previousStatus === OrderStatus::Cancelled) {
                    // Ranimée : on reprend le stock qu'on venait de rendre.
                    // Si un autre client l'a pris entre-temps, tout s'annule
                    // — statut compris — plutôt que de vendre du vide.
                    $this->stock->reapply($order);
                }
            });
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Le client n'est prévenu que si l'étape a réellement changé :
        // corriger une note interne ne doit pas lui envoyer un email.
        $notified = $statusChanged
            && $this->notifier->orderStatusChanged($order->fresh()->load('user'));

        $message = "Commande {$order->reference} mise à jour.";

        if ($notified) {
            $message .= ' Le client a été prévenu par email.';
        }

        return back()->with('success', $message);
    }

    /** Facture pro forma imprimable, reprenant les lignes de la commande. */
    public function pdf(Order $order): HttpResponse
    {
        $order->load(['items', 'user']);

        return $this->pdf->forOrder($order)
            ->download(QuotePdfService::filename($order->reference));
    }

    public function destroy(Order $order): RedirectResponse
    {
        $reference = $order->reference;

        DB::transaction(function () use ($order): void {
            // Une commande annulée a déjà rendu son stock ; les autres le
            // gardaient encore réservé, et le perdraient pour de bon sinon.
            if ($order->status !== OrderStatus::Cancelled) {
                $this->stock->release($order);
            }

            $order->delete();
        });

        return to_route('admin.orders.index')
            ->with('success', "Commande {$reference} supprimée.");
    }
}
