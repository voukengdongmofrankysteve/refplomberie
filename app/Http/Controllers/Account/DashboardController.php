<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Enums\TechnicianRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\TechnicianRequestResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Tableau de bord client : chiffres clés et dernières activités.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $favorites = $user->favorites()
            ->with(['category', 'images', 'priceTiers'])
            ->get();

        $orders = $user->orders()->withCount('items')->take(5)->get();
        $requests = $user->technicianRequests()->with('technician')->take(5)->get();

        return Inertia::render('account/dashboard', [
            'stats' => [
                'favorites' => $favorites->count(),
                'orders' => $user->orders()->count(),
                'openRequests' => $user->technicianRequests()
                    ->whereIn('status', [
                        TechnicianRequestStatus::Pending->value,
                        TechnicianRequestStatus::Assigned->value,
                        TechnicianRequestStatus::Scheduled->value,
                    ])
                    ->count(),
                'totalSpent' => (int) $user->orders()
                    ->whereNot('status', OrderStatus::Cancelled->value)
                    ->sum('total'),
            ],
            'favorites' => ProductResource::collection($favorites->take(4))->resolve(),
            'orders' => OrderResource::collection($orders)->resolve(),
            'requests' => TechnicianRequestResource::collection($requests)->resolve(),
        ]);
    }
}
