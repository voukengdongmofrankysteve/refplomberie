<?php

namespace App\Services\Analytics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Les quelques expressions SQL que les moteurs n'écrivent pas pareil.
 *
 * Les horodatages sont rangés en temps universel ; les rapports, eux, doivent
 * parler de la journée telle que la boutique la vit. Le décalage est appliqué
 * dans la requête plutôt qu'après coup : autrement, un événement de 23 h 30 à
 * Douala serait compté sur la journée de la veille.
 */
class SqlDialect
{
    private readonly int $offsetMinutes;

    public function __construct(private readonly string $driver, string $timezone)
    {
        // Décalage fixe : le Cameroun n'a pas d'heure d'été, et les zones qui
        // en ont ne changent que deux fois l'an — l'écart resterait sous
        // l'heure sur les seules journées de bascule.
        $this->offsetMinutes = (int) (Carbon::now($timezone)->getOffset() / 60);
    }

    public static function make(?string $timezone = null): self
    {
        return new self(
            DB::connection()->getDriverName(),
            $timezone ?? (string) config('analytics.timezone', 'UTC'),
        );
    }

    /** Jour local, au format `2026-08-24`. */
    public function date(string $column): string
    {
        return match ($this->driver) {
            'sqlite' => "strftime('%Y-%m-%d', {$this->shifted($column)})",
            'mysql', 'mariadb' => "DATE_FORMAT({$this->shifted($column)}, '%Y-%m-%d')",
            'pgsql' => "to_char({$this->shifted($column)}, 'YYYY-MM-DD')",
            default => throw $this->unsupported(),
        };
    }

    /** Mois local, au format `2026-08`. */
    public function month(string $column): string
    {
        return match ($this->driver) {
            'sqlite' => "strftime('%Y-%m', {$this->shifted($column)})",
            'mysql', 'mariadb' => "DATE_FORMAT({$this->shifted($column)}, '%Y-%m')",
            'pgsql' => "to_char({$this->shifted($column)}, 'YYYY-MM')",
            default => throw $this->unsupported(),
        };
    }

    /** Heure locale, de `00` à `23`. */
    public function hour(string $column): string
    {
        return match ($this->driver) {
            'sqlite' => "strftime('%H', {$this->shifted($column)})",
            'mysql', 'mariadb' => "DATE_FORMAT({$this->shifted($column)}, '%H')",
            'pgsql' => "to_char({$this->shifted($column)}, 'HH24')",
            default => throw $this->unsupported(),
        };
    }

    /** Jour de la semaine, `0` pour dimanche. */
    public function weekday(string $column): string
    {
        return match ($this->driver) {
            'sqlite' => "strftime('%w', {$this->shifted($column)})",
            'mysql', 'mariadb' => "CAST(DAYOFWEEK({$this->shifted($column)}) - 1 AS CHAR)",
            // EXTRACT(DOW) plutôt que to_char('ID') : le premier compte le
            // dimanche comme zéro, comme les deux autres moteurs.
            'pgsql' => "EXTRACT(DOW FROM {$this->shifted($column)})::text",
            default => throw $this->unsupported(),
        };
    }

    /** Durée en secondes entre deux colonnes d'horodatage. */
    public function secondsBetween(string $from, string $to): string
    {
        return match ($this->driver) {
            'sqlite' => "(julianday({$to}) - julianday({$from})) * 86400",
            'mysql', 'mariadb' => "TIMESTAMPDIFF(SECOND, {$from}, {$to})",
            'pgsql' => "EXTRACT(EPOCH FROM ({$to} - {$from}))",
            default => throw $this->unsupported(),
        };
    }

    private function shifted(string $column): string
    {
        if ($this->offsetMinutes === 0) {
            return $column;
        }

        $minutes = $this->offsetMinutes;

        return match ($this->driver) {
            'sqlite' => "datetime({$column}, '{$this->signed($minutes)} minutes')",
            'mysql', 'mariadb' => "DATE_ADD({$column}, INTERVAL {$minutes} MINUTE)",
            'pgsql' => "({$column} + interval '{$minutes} minutes')",
            default => throw $this->unsupported(),
        };
    }

    private function signed(int $minutes): string
    {
        return ($minutes >= 0 ? '+' : '').$minutes;
    }

    private function unsupported(): RuntimeException
    {
        return new RuntimeException(
            "Statistiques : moteur de base « {$this->driver} » non pris en charge.",
        );
    }
}
