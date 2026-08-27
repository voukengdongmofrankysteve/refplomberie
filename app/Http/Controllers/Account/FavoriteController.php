<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    /**
     * Liste complète des produits favoris du client.
     */
    public function index(Request $request): Response
    {
        $favorites = $request->user()->favorites()
            ->with(['category', 'images', 'priceTiers'])
            ->orderByPivot('created_at', 'desc')
            ->get();

        return Inertia::render('account/favorites', [
            'favorites' => ProductResource::collection($favorites)->resolve(),
        ]);
    }
}
