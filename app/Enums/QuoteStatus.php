<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Refused = 'refused';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'À traiter',
            self::Sent => 'Envoyé',
            self::Accepted => 'Accepté',
            self::Refused => 'Refusé',
            self::Expired => 'Expiré',
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
}
