<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromoCodeType;
use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Services\ListPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PromoCodeController extends Controller
{
    public function index(): Response
    {
        $codes = PromoCode::latest()
            ->paginate(15)
            ->through(fn (PromoCode $code): array => [
                'id' => $code->id,
                'code' => $code->code,
                'label' => $code->label,
                'type' => $code->type->value,
                'typeLabel' => $code->type->label(),
                'value' => $code->value,
                'advantage' => $code->advantage(),
                'minSubtotal' => $code->min_subtotal,
                'maxUses' => $code->max_uses,
                'usedCount' => $code->used_count,
                'startsAt' => $code->starts_at?->format('Y-m-d'),
                'endsAt' => $code->ends_at?->format('Y-m-d'),
                'isActive' => $code->is_active,
                // Distinct de `isActive` : un code actif peut être épuisé,
                // pas encore ouvert, ou déjà expiré.
                'isRedeemable' => $code->isRedeemable(),
            ]);

        return Inertia::render('admin/promo-codes/index', [
            'codes' => $codes,
            'types' => PromoCodeType::options(),
        ]);
    }

    /**
     * Tous les codes promo, en PDF.
     */
    public function exportPdf(ListPdfService $pdf): HttpResponse
    {
        $codes = PromoCode::latest()->get();

        $document = $pdf->render(
            title: 'Codes promo',
            subtitle: $codes->count().' code'.($codes->count() > 1 ? 's' : ''),
            columns: [
                ['label' => 'Code'],
                ['label' => 'Libellé'],
                ['label' => 'Avantage'],
                ['label' => 'Utilisations', 'align' => 'right'],
                ['label' => 'Statut'],
                ['label' => 'Période'],
            ],
            rows: $codes->map(fn (PromoCode $code): array => [
                $code->code,
                $code->label ?? '',
                $code->advantage(),
                $code->used_count.($code->max_uses !== null ? ' / '.$code->max_uses : ''),
                $code->isRedeemable() ? 'Utilisable' : 'Inactif',
                collect([
                    $code->starts_at?->format('d/m/Y'),
                    $code->ends_at?->format('d/m/Y'),
                ])->filter()->implode(' → ') ?: 'Sans limite',
            ])->all(),
        );

        return $document->download(ListPdfService::filename('codes-promo'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $code = PromoCode::create($data);

        return back()->with('success', "Code « {$code->code} » créé.");
    }

    public function update(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $promoCode->update($this->validated($request, $promoCode));

        return back()->with('success', "Code « {$promoCode->code} » mis à jour.");
    }

    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        $code = $promoCode->code;
        $promoCode->delete();

        return back()->with('success', "Code « {$code} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PromoCode $existing = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('promo_codes', 'code')->ignore($existing?->id),
            ],
            'label' => ['nullable', 'string', 'max:120'],
            'type' => ['required', Rule::enum(PromoCodeType::class)],
            // Un pourcentage ne dépasse pas 100 ; un montant fixe est libre,
            // la remise étant de toute façon plafonnée au sous-total.
            'value' => [
                'required',
                'integer',
                'min:1',
                Rule::when(
                    $request->input('type') === PromoCodeType::Percent->value,
                    ['max:100'],
                ),
            ],
            'min_subtotal' => ['required', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['required', 'boolean'],
        ], attributes: [
            'code' => 'code',
            'type' => 'type de remise',
            'value' => 'valeur',
            'min_subtotal' => 'montant minimum',
            'max_uses' => 'nombre maximum d’utilisations',
            'starts_at' => 'date de début',
            'ends_at' => 'date de fin',
        ], messages: [
            'code.regex' => 'Le code ne peut contenir que des lettres, chiffres, tirets et soulignés.',
            'value.max' => 'Une remise en pourcentage ne peut pas dépasser 100 %.',
        ]);
    }
}
