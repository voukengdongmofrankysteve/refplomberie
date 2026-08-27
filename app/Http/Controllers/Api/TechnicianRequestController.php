<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTechnicianRequestRequest;
use App\Http\Resources\Api\TechnicianRequestResource;
use App\Models\TechnicianRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TechnicianRequestController extends Controller
{
    /** Interventions demandées par le client connecté. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return TechnicianRequestResource::collection(
            $request->user()
                ->technicianRequests()
                ->with('technician')
                ->paginate(15),
        );
    }

    /** Demande d'intervention, ouverte aux visiteurs comme aux clients. */
    public function store(StoreTechnicianRequestRequest $request): JsonResponse
    {
        $technicianRequest = TechnicianRequest::create([
            ...$request->validated(),
            'reference' => TechnicianRequest::generateReference(),
            'user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'data' => (new TechnicianRequestResource($technicianRequest))->resolve($request),
            'message' => "Demande {$technicianRequest->reference} transmise à notre équipe.",
        ], 201);
    }
}
