<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianController extends Controller
{
    public function index(): Response
    {
        $technicians = Technician::withCount('requests')
            ->orderBy('name')
            ->get()
            ->map(fn (Technician $technician): array => [
                'id' => $technician->id,
                'name' => $technician->name,
                'specialty' => $technician->specialty,
                'experience' => $technician->experience,
                'rating' => (float) $technician->rating,
                'jobsCount' => $technician->jobs_count,
                'photo' => $technician->photo,
                'isAvailable' => $technician->is_available,
                'requestsCount' => $technician->requests_count,
            ])
            ->all();

        return Inertia::render('admin/technicians/index', [
            'technicians' => $technicians,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Technician::create($this->validated($request));

        return back()->with('success', 'Technicien ajouté.');
    }

    public function update(Request $request, Technician $technician): RedirectResponse
    {
        $technician->update($this->validated($request));

        return back()->with('success', 'Technicien mis à jour.');
    }

    public function destroy(Technician $technician): RedirectResponse
    {
        $name = $technician->name;
        $technician->delete();

        return back()->with('success', "Technicien « {$name} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'specialty' => ['required', 'string', 'max:120'],
            'experience' => ['required', 'string', 'max:40'],
            'rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'jobs_count' => ['required', 'integer', 'min:0'],
            'photo' => ['required', 'url', 'max:500'],
            'is_available' => ['required', 'boolean'],
        ], attributes: [
            'name' => 'nom',
            'specialty' => 'spécialité',
            'experience' => 'expérience',
            'rating' => 'note',
            'jobs_count' => 'interventions',
            'photo' => 'photo',
        ]);
    }
}
