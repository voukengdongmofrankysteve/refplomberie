<?php

namespace App\Facades;

use App\Enums\AnalyticsEvent;
use App\Models\Analytics\Event;
use App\Models\Analytics\Session;
use App\Services\Analytics\AnalyticsRecorder;
use Illuminate\Support\Facades\Facade;

/**
 * Point d'entrée de la mesure d'audience.
 *
 * Une façade plutôt qu'une injection : la mesure se pose dans une dizaine de
 * contrôleurs qui n'ont, autrement, aucune raison de connaître ce service.
 *
 * @method static bool enabled()
 * @method static Event|null record(AnalyticsEvent $type, \Illuminate\Database\Eloquent\Model|null $subject = null, string|null $label = null, int|null $value = null, array<string, mixed> $meta = [], string|null $path = null, string|null $title = null)
 * @method static Session|null session()
 * @method static void forget()
 *
 * @see AnalyticsRecorder
 */
class Analytics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AnalyticsRecorder::class;
    }
}
