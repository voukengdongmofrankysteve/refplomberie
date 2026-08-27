<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Ordered = 'ordered';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Ordered => 'Commandé',
            self::Received => 'Reçu',
            self::Cancelled => 'Annulé',
        };
    }

    /** Reçu ou annulé : plus aucune modification n'a de sens. */
    public function isTerminal(): bool
    {
        return $this === self::Received || $this === self::Cancelled;
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
}
