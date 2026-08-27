<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TechnicianRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TechnicianRequestResource;
use App\Models\Technician;
use App\Models\TechnicianRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianRequestController extends Controller
{
    /**
     * File des demandes d'intervention, filtrable par statut.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();

        $requests = TechnicianRequest::query()
            ->with(['technician', 'user'])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('reference', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%"),
            ))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/technician-requests/index', [
            'requests' => TechnicianRequestResource::collection($requests)
                ->response()
                ->getData(true),
            'statuses' => TechnicianRequestStatus::options(),
            'technicians' => $this->technicianOptions(),
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function show(TechnicianRequest $technicianRequest): Response
    {
        $technicianRequest->load(['technician', 'user']);

        return Inertia::render('admin/technician-requests/show', [
            'request' => (new TechnicianRequestResource($technicianRequest))->resolve(),
            'statuses' => TechnicianRequestStatus::options(),
            'technicians' => $this->technicianOptions(),
        ]);
    }

    /**
     * Assignation d'un technicien, changement de statut et note interne.
     */
    public function update(
        Request $request,
        TechnicianRequest $technicianRequest,
    ): RedirectResponse {
        $data = $request->validate([
            'status' => ['required', Rule::enum(TechnicianRequestStatus::class)],
            'technician_id' => ['nullable', 'integer', 'exists:technicians,id'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ], attributes: [
            'status' => 'statut',
            'technician_id' => 'technicien',
            'admin_note' => 'note',
        ]);

        $technicianRequest->update($data);

        return back()->with(
            'success',
            "Demande {$technicianRequest->reference} mise à jour.",
        );
    }

    public function destroy(TechnicianRequest $technicianRequest): RedirectResponse
    {
        $reference = $technicianRequest->reference;
        $technicianRequest->delete();

        return to_route('admin.technician-requests.index')
            ->with('success', "Demande {$reference} supprimée.");
    }

    /**
     * @return array<int, array{value: int, label: string, available: bool}>
     */
    private function technicianOptions(): array
    {
        return Technician::orderBy('name')
            ->get()
            ->map(fn (Technician $technician): array => [
                'value' => $technician->id,
                'label' => $technician->name.' — '.$technician->specialty,
                'available' => $technician->is_available,
            ])
            ->all();
    }
}
