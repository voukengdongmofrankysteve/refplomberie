<?php

namespace App\Enums;

enum TechnicianRequestStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Assigned => 'Technicien assigné',
            self::Scheduled => 'Planifiée',
            self::Completed => 'Terminée',
            self::Cancelled => 'Annulée',
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
