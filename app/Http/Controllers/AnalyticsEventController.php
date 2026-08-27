<?php

namespace App\Http\Controllers;

use App\Enums\AnalyticsEvent;
use App\Facades\Analytics;
use App\Models\Product;
use App\Models\Story;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Déclaration d'une action qui n'existe que dans le navigateur.
 *
 * Ajouter au panier, cliquer sur le bouton WhatsApp, regarder un statut : rien
 * de tout cela ne touche le serveur, et sans ce point d'entrée ces actions
 * seraient invisibles.
 *
 * Seuls les types de la liste blanche sont acceptés. Une « commande passée »
 * annoncée par le navigateur serait un chiffre d'affaires inventé depuis la
 * console : ces événements-là restent écrits par le serveur, et par lui seul.
 */
class AnalyticsEventController extends Controller
{
    /** Sujets que le navigateur peut désigner, par leur nom court. */
    private const SUBJECTS = [
        'product' => Product::class,
        'story' => Story::class,
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(AnalyticsEvent::clientReportable())],
            'subject' => ['nullable', Rule::in(array_keys(self::SUBJECTS))],
            'id' => ['nullable', 'integer', 'min:1'],
            'label' => ['nullable', 'string', 'max:250'],
            'value' => ['nullable', 'integer', 'min:0'],
            'path' => ['nullable', 'string', 'max:250'],
        ]);

        Analytics::record(
            AnalyticsEvent::from($data['type']),
            subject: $this->subject($data),
            label: $data['label'] ?? null,
            value: $data['value'] ?? null,
            path: $data['path'] ?? null,
        );

        // Réponse vide et immédiate : le navigateur n'attend rien, il envoie
        // souvent la requête au moment où il quitte la page.
        return response()->json(status: 204);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function subject(array $data): ?Model
    {
        $class = self::SUBJECTS[$data['subject'] ?? ''] ?? null;

        if ($class === null || ($data['id'] ?? null) === null) {
            return null;
        }

        return $class::find($data['id']);
    }
}
