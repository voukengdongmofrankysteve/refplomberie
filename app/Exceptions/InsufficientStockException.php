<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Le stock disponible ne couvre plus ce qui a été demandé.
 *
 * Levée à l'intérieur de la transaction qui enregistre la commande : la
 * transaction s'annule d'elle-même, aucune commande n'est créée et le stock
 * décrémenté pour les lignes déjà traitées revient à sa valeur d'avant.
 */
class InsufficientStockException extends RuntimeException
{
    /**
     * @param  array<int, array{name: string, requested: int, available: int}>  $shortages
     */
    public function __construct(public readonly array $shortages)
    {
        parent::__construct(self::summarize($shortages));
    }

    /**
     * @param  array<int, array{name: string, requested: int, available: int}>  $shortages
     */
    private static function summarize(array $shortages): string
    {
        $details = array_map(
            fn (array $shortage): string => $shortage['available'] > 0
                ? "{$shortage['name']} (il n'en reste que {$shortage['available']})"
                : "{$shortage['name']} (rupture de stock)",
            $shortages,
        );

        return 'Stock insuffisant pour : '.implode(', ', $details).'.';
    }
}
