<?php

namespace App\Enums;

/**
 * Badges de confiance affichés sur une fiche produit — garantie et
 * authenticité, deux préoccupations distinctes de l'étiquette marketing
 * libre (`Product::$badge`, « Promo », « Nouveau »…).
 */
enum ProductWarrantyBadge: string
{
    case Authentic = 'authentic';
    case ManufacturerWarranty = 'manufacturer_warranty';
    case StoreWarranty = 'store_warranty';
    case AfterSales = 'after_sales';

    public function label(): string
    {
        return match ($this) {
            self::Authentic => 'Produit authentique certifié',
            self::ManufacturerWarranty => 'Garantie fabricant',
            self::StoreWarranty => 'Garantie boutique',
            self::AfterSales => 'SAV disponible',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * Résout les valeurs stockées sur un produit en libellés affichables,
     * en ignorant silencieusement une valeur qui ne correspondrait plus à
     * aucun cas (badge retiré depuis).
     *
     * @param  list<string>|null  $values
     * @return array<int, array{value: string, label: string}>
     */
    public static function labelsFor(?array $values): array
    {
        return collect($values ?? [])
            ->map(fn (string $value): ?self => self::tryFrom($value))
            ->filter()
            ->map(fn (self $badge): array => ['value' => $badge->value, 'label' => $badge->label()])
            ->values()
            ->all();
    }
}
