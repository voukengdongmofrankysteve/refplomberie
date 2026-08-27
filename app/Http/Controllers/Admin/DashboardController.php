<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactMessageStatus;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\TechnicianRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\TechnicianRequestResource;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\TechnicianRequest;
use App\Models\User;
use App\Services\ProductImageService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Vue d'ensemble : chiffre d'affaires, activité et files d'attente.
     */
    public function __invoke(): Response
    {
        $revenue = (int) Order::whereNot('status', OrderStatus::Cancelled->value)->sum('total');

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'revenue' => $revenue,
                'orders' => Order::count(),
                'pendingOrders' => Order::where('status', OrderStatus::Pending->value)->count(),
                'products' => Product::count(),
                'outOfStock' => Product::where('stock', 0)->count(),
                // Produits au seuil de réapprovisionnement ou en dessous,
                // ruptures comprises.
                'lowStock' => Product::lowStock()->count(),
                'pendingQuotes' => Quote::where('status', QuoteStatus::Draft->value)->count(),
                'customers' => User::count(),
                'pendingRequests' => TechnicianRequest::where(
                    'status',
                    TechnicianRequestStatus::Pending->value,
                )->count(),
                'newMessages' => ContactMessage::where(
                    'status',
                    ContactMessageStatus::New->value,
                )->count(),
            ],
            // Liste d'action : ce qu'il faut recommander, au plus urgent.
            'lowStockProducts' => Product::query()
                ->with('category')
                ->lowStock()
                ->orderBy('stock')
                ->orderBy('name')
                ->take(8)
                ->get()
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'category' => $product->category->label,
                    'stock' => $product->stock,
                    'threshold' => $product->low_stock_threshold,
                    'level' => $product->stockLevel(),
                    'image' => ProductImageService::url($product->image),
                ])
                ->all(),
            'recentOrders' => OrderResource::collection(
                Order::with('user')->withCount('items')->latest()->take(6)->get(),
            )->resolve(),
            'recentRequests' => TechnicianRequestResource::collection(
                TechnicianRequest::with(['technician', 'user'])->latest()->take(6)->get(),
            )->resolve(),
            // Alimente le petit graphe « ventes par statut ».
            'ordersByStatus' => collect(OrderStatus::cases())
                ->map(fn (OrderStatus $status): array => [
                    'status' => $status->value,
                    'label' => $status->label(),
                    'count' => Order::where('status', $status->value)->count(),
                ])
                ->all(),
        ]);
    }
}
