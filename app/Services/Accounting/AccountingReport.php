<?php

namespace App\Services\Accounting;

use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Services\Analytics\Period;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Chiffre d'affaires, achats et marge, pour le tableau de bord comptable et
 * son export.
 *
 * Calculé à la demande comme le rapport d'audience : aux volumes d'une
 * boutique, une table de synthèse n'apporterait rien qu'une requête directe
 * ne fasse déjà assez vite.
 */
class AccountingReport
{
    private readonly Carbon $from;

    private readonly Carbon $to;

    public function __construct(private readonly Period $period)
    {
        [$this->from, $this->to] = $period->bounds();
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        $orders = $this->orders();
        $purchaseOrders = $this->receivedPurchaseOrders();

        $revenue = (int) $orders->sum('total');
        $costs = (int) $purchaseOrders->sum('total');

        return [
            'revenue' => $revenue,
            'costs' => $costs,
            'margin' => $revenue - $costs,
            'ordersCount' => $orders->count(),
            'purchaseOrdersCount' => $purchaseOrders->count(),
        ];
    }

    /**
     * Chiffre d'affaires et achats groupés par période, pour le graphique.
     *
     * @return array<int, array{label: string, revenue: int, costs: int}>
     */
    public function series(): array
    {
        $orders = $this->orders()->groupBy(fn (Order $order): string => $this->bucket($order->created_at));
        $purchaseOrders = $this->receivedPurchaseOrders()
            ->groupBy(fn (PurchaseOrder $order): string => $this->bucket($order->received_at));

        $buckets = $orders->keys()->merge($purchaseOrders->keys())->unique()->sort();

        return $buckets->map(fn (string $bucket): array => [
            'label' => $bucket,
            'revenue' => (int) $orders->get($bucket, collect())->sum('total'),
            'costs' => (int) $purchaseOrders->get($bucket, collect())->sum('total'),
        ])->values()->all();
    }

    /**
     * Ventes et achats de la période, dans l'ordre chronologique — au
     * format d'un journal comptable classique (une ligne, un montant au
     * débit ou au crédit, jamais les deux).
     *
     * @return array<int, array{date: string, journal: string, reference: string, party: string, label: string, debit: int, credit: int}>
     */
    public function ledger(): array
    {
        $sales = $this->orders()->map(fn (Order $order): array => [
            'date' => $order->created_at->toDateString(),
            'journal' => 'VENTES',
            'reference' => $order->reference,
            'party' => $order->customer_name,
            'label' => "Commande {$order->reference}",
            'debit' => 0,
            'credit' => $order->total,
        ]);

        $purchases = $this->receivedPurchaseOrders()->map(fn (PurchaseOrder $order): array => [
            'date' => $order->received_at->toDateString(),
            'journal' => 'ACHATS',
            'reference' => $order->reference,
            'party' => $order->supplier->name,
            'label' => "Bon de commande {$order->reference}",
            'debit' => $order->total,
            'credit' => 0,
        ]);

        return $sales->concat($purchases)
            ->sortBy('date')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Order>
     */
    private function orders(): Collection
    {
        return Order::query()
            ->whereBetween('created_at', [$this->from, $this->to])
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->get();
    }

    /**
     * @return Collection<int, PurchaseOrder>
     */
    private function receivedPurchaseOrders(): Collection
    {
        return PurchaseOrder::query()
            ->with('supplier')
            ->where('status', PurchaseOrderStatus::Received->value)
            ->whereBetween('received_at', [$this->from, $this->to])
            ->get();
    }

    private function bucket(?CarbonInterface $date): string
    {
        $date ??= Carbon::now();

        return match ($this->period->granularity) {
            'hour' => $date->format('H\h'),
            'month' => $date->format('m/Y'),
            default => $date->format('d/m/Y'),
        };
    }
}
