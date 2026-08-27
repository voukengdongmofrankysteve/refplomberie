<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Historique des commandes du client, lignes incluses.
     */
    public function index(Request $request): Response
    {
        $orders = $request->user()->orders()->with('items')->paginate(10);

        return Inertia::render('account/orders', [
            'orders' => OrderResource::collection($orders)->response()->getData(true),
        ]);
    }
}
