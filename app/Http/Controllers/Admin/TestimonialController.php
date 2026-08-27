<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/testimonials/index', [
            'testimonials' => Testimonial::orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(fn (Testimonial $testimonial): array => [
                    'id' => $testimonial->id,
                    'name' => $testimonial->name,
                    'role' => $testimonial->role,
                    'text' => $testimonial->text,
                    'rating' => $testimonial->rating,
                    'position' => $testimonial->position,
                    'isActive' => $testimonial->is_active,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // Ajouté en fin de liste par défaut, comme la FAQ : personne ne
        // s'attend à voir un nouveau témoignage sauter devant les autres.
        $data['position'] ??= (int) Testimonial::max('position') + 1;

        Testimonial::create($data);

        return back()->with('success', 'Témoignage ajouté.');
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($this->validated($request));

        return back()->with('success', 'Témoignage mis à jour.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return back()->with('success', 'Témoignage supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'text' => ['required', 'string', 'max:1000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ], attributes: [
            'name' => 'nom',
            'role' => 'fonction',
            'text' => 'témoignage',
            'rating' => 'note',
            'position' => 'position',
        ]);
    }
}
