<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\TechnicianRequestResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianRequestController extends Controller
{
    /**
     * Demandes d'intervention du client + formulaire de nouvelle demande.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $requests = $user->technicianRequests()->with('technician')->paginate(10);

        return Inertia::render('account/technician-requests', [
            'requests' => TechnicianRequestResource::collection($requests)
                ->response()
                ->getData(true),
            'services' => config('shop.services'),
            // Pré-remplit le formulaire avec les coordonnées du compte.
            'defaults' => [
                'customer_name' => $user->name,
                'customer_phone' => $user->phone ?? '',
                'address' => $user->address ?? '',
            ],
        ]);
    }
}
