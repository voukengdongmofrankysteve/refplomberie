<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/faqs/index', [
            'faqs' => Faq::orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(fn (Faq $faq): array => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'category' => $faq->category,
                    'position' => $faq->position,
                    'isActive' => $faq->is_active,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // Ajoutée en fin de liste par défaut : personne ne s'attend à voir
        // une nouvelle question sauter devant celles déjà classées.
        $data['position'] ??= (int) Faq::max('position') + 1;

        Faq::create($data);

        return back()->with('success', 'Question ajoutée.');
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        return back()->with('success', 'Question mise à jour.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return back()->with('success', 'Question supprimée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ], attributes: [
            'question' => 'question',
            'answer' => 'réponse',
            'category' => 'thème',
            'position' => 'position',
        ]);
    }
}
