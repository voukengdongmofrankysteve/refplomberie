<?php

namespace App\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Answered = 'answered';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Read => 'Lu',
            self::Answered => 'Traité',
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
