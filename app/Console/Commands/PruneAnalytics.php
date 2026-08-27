<?php

namespace App\Console\Commands;

use App\Models\Analytics\Event;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Efface les mesures devenues trop anciennes.
 *
 * Le détail d'une visite d'il y a trois ans n'apprend plus rien à personne et
 * pèse sur chaque requête. La rétention se règle dans config/analytics.php.
 */
class PruneAnalytics extends Command
{
    protected $signature = 'analytics:prune {--days= : Nombre de jours à conserver}';

    protected $description = 'Supprime les mesures d’audience au-delà de la durée de conservation';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('analytics.retention_days', 730));

        if ($days < 1) {
            $this->error('La durée de conservation doit valoir au moins un jour.');

            return self::FAILURE;
        }

        $limit = Carbon::now()->subDays($days);

        $events = Event::where('occurred_at', '<', $limit)->delete();
        $sessions = Session::where('last_activity_at', '<', $limit)->delete();

        // Un visiteur dont toutes les visites ont disparu n'a plus d'histoire :
        // le garder ne ferait que fausser le décompte des « nouveaux ».
        $visitors = Visitor::where('last_seen_at', '<', $limit)->delete();

        $this->info(sprintf(
            'Purge au-delà du %s : %d événements, %d visites, %d visiteurs.',
            $limit->toDateString(),
            $events,
            $sessions,
            $visitors,
        ));

        return self::SUCCESS;
    }
}
