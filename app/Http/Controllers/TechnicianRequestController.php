<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTechnicianRequestRequest;
use App\Models\TechnicianRequest;
use Illuminate\Http\RedirectResponse;

class TechnicianRequestController extends Controller
{
    /**
     * Enregistre une demande d'intervention (vitrine ou espace client).
     */
    public function store(StoreTechnicianRequestRequest $request): RedirectResponse
    {
        $technicianRequest = TechnicianRequest::create([
            ...$request->validated(),
            'reference' => TechnicianRequest::generateReference(),
            'user_id' => $request->user()?->id,
        ]);

        return back()->with(
            'success',
            "Demande {$technicianRequest->reference} transmise à notre équipe.",
        );
    }
}
