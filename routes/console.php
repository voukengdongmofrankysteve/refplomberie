<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purge des vieilles mesures, la nuit : la suppression balaie plusieurs
// tables et n'a aucune raison de gêner un client en pleine commande.
Schedule::command('analytics:prune')->dailyAt('03:30');
