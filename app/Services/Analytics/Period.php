<?php

namespace App\Services\Analytics;

use Illuminate\Support\Carbon;

/**
 * Fenêtre de temps d'un rapport, et la fenêtre équivalente qui la précède.
 *
 * La période précédente sert aux comparaisons « + 12 % » : elle a exactement
 * la même durée, sans quoi la comparaison ne voudrait rien dire.
 */
class Period
{
    /** @var array<string, string> */
    public const PRESETS = [
        'today' => "Aujourd'hui",
        '7d' => '7 derniers jours',
        '30d' => '30 derniers jours',
        '90d' => '90 derniers jours',
        '12m' => '12 derniers mois',
        'year' => 'Cette année',
        'all' => 'Depuis le début',
    ];

    private function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly Carbon $from,
        public readonly Carbon $to,
        /** `hour`, `day` ou `month` */
        public readonly string $granularity,
    ) {}

    /**
     * Construit la période demandée, en repliant sur 30 jours l'inconnue.
     *
     * @param  Carbon|null  $firstEvent  Date du tout premier événement mesuré.
     */
    public static function make(?string $key, ?Carbon $firstEvent = null): self
    {
        $timezone = (string) config('analytics.timezone', 'UTC');
        $now = Carbon::now($timezone);
        $key = array_key_exists((string) $key, self::PRESETS) ? (string) $key : '30d';

        [$from, $granularity] = match ($key) {
            'today' => [$now->copy()->startOfDay(), 'hour'],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), 'day'],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), 'day'],
            '90d' => [$now->copy()->subDays(89)->startOfDay(), 'day'],
            '12m' => [$now->copy()->subMonths(11)->startOfMonth(), 'month'],
            'year' => [$now->copy()->startOfYear(), 'month'],
            'all' => [
                ($firstEvent?->copy()->setTimezone($timezone) ?? $now->copy()->subDays(29))->startOfDay(),
                'month',
            ],
        };

        return new self(
            key: $key,
            label: self::PRESETS[$key],
            from: $from,
            to: $now->copy()->endOfDay(),
            granularity: $granularity,
        );
    }

    /**
     * Fenêtre de même durée juste avant celle-ci.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function previous(): array
    {
        $seconds = max(1, $this->from->diffInSeconds($this->to));

        return [
            $this->from->copy()->subSeconds($seconds),
            $this->from->copy()->subSecond(),
        ];
    }

    /**
     * Bornes en temps universel, celles que la base comprend.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function bounds(): array
    {
        return [$this->from->copy()->utc(), $this->to->copy()->utc()];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (string $key, string $label): array => ['value' => $key, 'label' => $label],
            array_keys(self::PRESETS),
            array_values(self::PRESETS),
        );
    }
}
