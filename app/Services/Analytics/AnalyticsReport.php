<?php

namespace App\Services\Analytics;

use App\Enums\AnalyticsEvent;
use App\Models\Analytics\Event;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lecture des mesures : tout ce que le tableau de bord affiche.
 *
 * Les chiffres sont calculés à la demande, sans table de synthèse. À l'échelle
 * d'une boutique — quelques milliers d'événements par mois — c'est plus simple
 * et toujours juste ; les index posés sur `type`, `occurred_at` et le sujet
 * gardent les requêtes rapides.
 */
class AnalyticsReport
{
    private readonly SqlDialect $sql;

    private readonly Carbon $from;

    private readonly Carbon $to;

    public function __construct(private readonly Period $period)
    {
        $this->sql = SqlDialect::make();
        [$this->from, $this->to] = $period->bounds();
    }

    /*
    |--------------------------------------------------------------------------
    | Chiffres clés
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, int|float>
     */
    public function summary(): array
    {
        return $this->summaryBetween($this->from, $this->to);
    }

    /**
     * Les mêmes chiffres sur la période précédente, pour les évolutions.
     *
     * @return array<string, int|float>
     */
    public function previousSummary(): array
    {
        [$from, $to] = $this->period->previous();

        return $this->summaryBetween($from->copy()->utc(), $to->copy()->utc());
    }

    /**
     * @return array<string, int|float>
     */
    private function summaryBetween(Carbon $from, Carbon $to): array
    {
        $events = $this->events($from, $to);

        $visitors = (clone $events)->distinct()->count('visitor_id');
        $pageViews = (clone $events)->where('type', AnalyticsEvent::PageView->value)->count();

        $sessions = Session::whereBetween('started_at', [$from, $to]);
        $sessionCount = (clone $sessions)->count();

        $orders = (clone $events)->where('type', AnalyticsEvent::OrderPlaced->value);
        $orderCount = (clone $orders)->count();

        // Durée moyenne d'une visite : l'écart entre sa première et sa
        // dernière action. Une visite d'une seule page dure zéro seconde — on
        // ne sait pas quand le visiteur est parti, et deviner serait mentir.
        $duration = (clone $sessions)
            ->selectRaw('AVG('.$this->sql->secondsBetween('started_at', 'last_activity_at').') as seconds')
            ->value('seconds');

        return [
            'visitors' => $visitors,
            'newVisitors' => Visitor::whereBetween('first_seen_at', [$from, $to])->count(),
            'sessions' => $sessionCount,
            'pageViews' => $pageViews,
            'events' => (clone $events)->count(),
            'pagesPerSession' => $sessionCount > 0 ? round($pageViews / $sessionCount, 1) : 0.0,
            'avgDuration' => (int) round((float) $duration),
            'orders' => $orderCount,
            'revenue' => (int) (clone $orders)->sum('value'),
            'quotes' => (clone $events)->where('type', AnalyticsEvent::QuoteRequested->value)->count(),
            'contacts' => (clone $events)->whereIn('type', [
                AnalyticsEvent::ContactMessage->value,
                AnalyticsEvent::WhatsAppClick->value,
                AnalyticsEvent::PhoneClick->value,
                AnalyticsEvent::TechnicianRequested->value,
            ])->count(),
            // Part des visites qui aboutissent à une commande.
            'conversionRate' => $sessionCount > 0
                ? round($orderCount / $sessionCount * 100, 2)
                : 0.0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Courbe dans le temps
    |--------------------------------------------------------------------------
    */

    /**
     * Audience par jour, par heure ou par mois selon la période demandée.
     *
     * Les intervalles sans la moindre visite sont renvoyés à zéro : une courbe
     * qui saute les jours creux donne l'illusion d'une fréquentation continue.
     *
     * @return array<int, array<string, int|string>>
     */
    public function series(): array
    {
        $bucket = $this->bucketExpression('occurred_at');

        $rows = $this->events()
            ->selectRaw("{$bucket} as bucket")
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as page_views', [AnalyticsEvent::PageView->value])
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as orders', [AnalyticsEvent::OrderPlaced->value])
            ->selectRaw('SUM(CASE WHEN type = ? THEN value ELSE 0 END) as revenue', [AnalyticsEvent::OrderPlaced->value])
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        return $this->buckets()
            ->map(function (array $bucket) use ($rows): array {
                /** @var object|null $row */
                $row = $rows[$bucket['key']] ?? null;

                return [
                    'bucket' => $bucket['key'],
                    'label' => $bucket['label'],
                    'visitors' => (int) ($row->visitors ?? 0),
                    'pageViews' => (int) ($row->page_views ?? 0),
                    'orders' => (int) ($row->orders ?? 0),
                    'revenue' => (int) ($row->revenue ?? 0),
                ];
            })
            ->all();
    }

    /**
     * Suite complète des intervalles de la période, vides compris.
     *
     * @return Collection<int, array{key: string, label: string}>
     */
    private function buckets(): Collection
    {
        $buckets = collect();
        $cursor = $this->period->from->copy();
        $end = $this->period->to;

        while ($cursor <= $end) {
            $buckets->push(match ($this->period->granularity) {
                'hour' => ['key' => $cursor->format('H'), 'label' => $cursor->format('H\h')],
                'month' => ['key' => $cursor->format('Y-m'), 'label' => $cursor->translatedFormat('M Y')],
                default => ['key' => $cursor->format('Y-m-d'), 'label' => $cursor->translatedFormat('j M')],
            });

            $cursor = match ($this->period->granularity) {
                'hour' => $cursor->addHour(),
                'month' => $cursor->addMonthNoOverflow(),
                default => $cursor->addDay(),
            };
        }

        return $buckets;
    }

    private function bucketExpression(string $column): string
    {
        return match ($this->period->granularity) {
            'hour' => $this->sql->hour($column),
            'month' => $this->sql->month($column),
            default => $this->sql->date($column),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Contenus
    |--------------------------------------------------------------------------
    */

    /**
     * Pages les plus consultées.
     *
     * @return array<int, array<string, int|string>>
     */
    public function topPages(int $limit = 12): array
    {
        $rows = $this->events()
            ->where('type', AnalyticsEvent::PageView->value)
            ->select('path')
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();

        $names = $this->productNames(
            $rows->pluck('path')
                ->map(fn (?string $path): ?string => $this->productSlug((string) $path))
                ->filter()
                ->all(),
        );

        return $rows
            ->map(fn (Event $row): array => [
                'path' => (string) $row->path,
                'label' => $this->pageLabel((string) $row->path, $names),
                'views' => (int) $row->getAttribute('views'),
                'visitors' => (int) $row->getAttribute('visitors'),
            ])
            ->all();
    }

    /**
     * Produits les plus regardés, et ce qu'ils ont réellement rapporté.
     *
     * Les ventes viennent des lignes de commande, pas des événements : c'est
     * la seule source qui connaisse les quantités et les remises.
     *
     * @return array<int, array<string, int|string|null>>
     */
    public function topProducts(int $limit = 12): array
    {
        $views = $this->events()
            ->where('type', AnalyticsEvent::ProductView->value)
            ->whereNotNull('subject_id')
            ->where('subject_type', (new Product)->getMorphClass())
            ->select('subject_id')
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('subject_id')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->keyBy('subject_id');

        if ($views->isEmpty()) {
            return [];
        }

        $sales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $views->keys()->all())
            ->whereBetween('orders.created_at', [$this->from, $this->to])
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('order_items.product_id')
            ->select('order_items.product_id')
            ->selectRaw('SUM(order_items.quantity) as quantity')
            ->selectRaw('SUM(order_items.quantity * order_items.unit_price) as revenue')
            ->get()
            ->keyBy('product_id');

        $products = Product::whereKey($views->keys()->all())->with('images')->get()->keyBy('id');

        return $views
            ->map(function (Event $row) use ($products, $sales): ?array {
                $product = $products[$row->subject_id] ?? null;

                if ($product === null) {
                    return null;
                }

                $sale = $sales[$row->subject_id] ?? null;
                $viewCount = (int) $row->getAttribute('views');
                $quantity = (int) ($sale->quantity ?? 0);

                return [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'image' => ProductImageService::url($product->image),
                    'views' => $viewCount,
                    'visitors' => (int) $row->getAttribute('visitors'),
                    'quantity' => $quantity,
                    'revenue' => (int) ($sale->revenue ?? 0),
                    // Combien de consultations aboutissent à une vente : la
                    // fiche qui attire sans convertir a un problème de prix,
                    // de photo ou de stock.
                    'conversion' => $viewCount > 0
                        ? round($quantity / $viewCount * 100, 1)
                        : 0.0,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Ce que les visiteurs cherchent, y compris ce qu'ils ne trouvent pas.
     *
     * @return array<int, array<string, int|string>>
     */
    public function topSearches(int $limit = 12): array
    {
        return $this->events()
            ->where('type', AnalyticsEvent::Search->value)
            ->whereNotNull('label')
            ->select('label')
            ->selectRaw('COUNT(*) as searches')
            // Une recherche sans résultat signale un produit à référencer.
            ->selectRaw('SUM(CASE WHEN value = 0 THEN 1 ELSE 0 END) as empty_results')
            ->groupBy('label')
            ->orderByDesc('searches')
            ->limit($limit)
            ->get()
            ->map(fn (Event $row): array => [
                'term' => (string) $row->label,
                'searches' => (int) $row->getAttribute('searches'),
                'empty' => (int) $row->getAttribute('empty_results'),
            ])
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Provenance des visiteurs
    |--------------------------------------------------------------------------
    */

    /**
     * Répartition des visites selon une colonne de session.
     *
     * @return array<int, array<string, int|string|float|null>>
     */
    public function breakdown(string $column, int $limit = 12, ?string $codeColumn = null): array
    {
        $query = Session::whereBetween('started_at', [$this->from, $this->to])
            ->select($column)
            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->whereNotNull($column)
            ->groupBy($column)
            ->orderByDesc('sessions')
            ->limit($limit);

        if ($codeColumn !== null) {
            // Le drapeau se déduit du code pays : on l'emporte avec le nom.
            $query->addSelect($codeColumn)->groupBy($codeColumn);
        }

        $rows = $query->get();
        $total = max(1, (int) $rows->sum('sessions'));

        return $rows
            ->map(fn (Session $row): array => [
                'name' => (string) $row->getAttribute($column),
                'code' => $codeColumn === null ? null : $row->getAttribute($codeColumn),
                'sessions' => (int) $row->getAttribute('sessions'),
                'visitors' => (int) $row->getAttribute('visitors'),
                'share' => round((int) $row->getAttribute('sessions') / $total * 100, 1),
            ])
            ->all();
    }

    /**
     * Sites qui nous envoient du monde, l'accès direct mis à part.
     *
     * @return array<int, array<string, int|string|null>>
     */
    public function referrers(int $limit = 10): array
    {
        $direct = Session::whereBetween('started_at', [$this->from, $this->to])
            ->whereNull('referrer_host')
            ->count();

        $rows = $this->breakdown('referrer_host', $limit);

        // L'accès direct — favoris, lien WhatsApp, adresse tapée — est la
        // première « source » de la plupart des boutiques : la cacher
        // donnerait une image fausse du recrutement.
        array_unshift($rows, [
            'name' => 'Accès direct',
            'code' => null,
            'sessions' => $direct,
            'visitors' => $direct,
            'share' => null,
        ]);

        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | Rythme et parcours
    |--------------------------------------------------------------------------
    */

    /**
     * Affluence heure par heure : à quelle heure ouvrir le standard.
     *
     * @return array<int, array{hour: string, views: int}>
     */
    public function byHour(): array
    {
        $rows = $this->events()
            ->where('type', AnalyticsEvent::PageView->value)
            ->selectRaw($this->sql->hour('occurred_at').' as slot')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('slot')
            ->pluck('views', 'slot');

        return collect(range(0, 23))
            ->map(fn (int $hour): array => [
                'hour' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT),
                'views' => (int) ($rows[str_pad((string) $hour, 2, '0', STR_PAD_LEFT)] ?? 0),
            ])
            ->all();
    }

    /**
     * Affluence par jour de la semaine.
     *
     * @return array<int, array{day: string, views: int}>
     */
    public function byWeekday(): array
    {
        $rows = $this->events()
            ->where('type', AnalyticsEvent::PageView->value)
            ->selectRaw($this->sql->weekday('occurred_at').' as slot')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('slot')
            ->pluck('views', 'slot');

        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

        return collect($days)
            ->map(fn (string $day, int $index): array => [
                'day' => $day,
                'views' => (int) ($rows[(string) $index] ?? 0),
            ])
            ->all();
    }

    /**
     * Toutes les actions mesurées, de la plus fréquente à la plus rare.
     *
     * @return array<int, array{type: string, label: string, count: int}>
     */
    public function actions(): array
    {
        $counts = $this->events()
            ->select('type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return collect(AnalyticsEvent::cases())
            ->map(fn (AnalyticsEvent $case): array => [
                'type' => $case->value,
                'label' => $case->label(),
                'count' => (int) ($counts[$case->value] ?? 0),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * Entonnoir : combien de visiteurs franchissent chaque étape.
     *
     * Compté en visiteurs distincts, pas en actions : un client qui recharge
     * son panier trois fois reste un client.
     *
     * @return array<int, array{step: string, visitors: int, share: float}>
     */
    public function funnel(): array
    {
        $total = $this->events()->distinct()->count('visitor_id');

        $steps = [
            ['step' => 'Visiteurs', 'visitors' => $total],
            ['step' => 'Fiche produit', 'visitors' => $this->distinctVisitors(AnalyticsEvent::ProductView)],
            ['step' => 'Ajout au panier', 'visitors' => $this->distinctVisitors(AnalyticsEvent::AddToCart)],
            ['step' => 'Commande entamée', 'visitors' => $this->distinctVisitors(AnalyticsEvent::CheckoutStarted)],
            ['step' => 'Commande passée', 'visitors' => $this->distinctVisitors(AnalyticsEvent::OrderPlaced)],
        ];

        return array_map(
            fn (array $step): array => [
                ...$step,
                'share' => $total > 0 ? round($step['visitors'] / $total * 100, 1) : 0.0,
            ],
            $steps,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Temps réel
    |--------------------------------------------------------------------------
    */

    /**
     * Qui est là maintenant, et ce qu'il vient de faire.
     *
     * @return array<string, mixed>
     */
    public function live(int $minutes = 30): array
    {
        $since = Carbon::now()->subMinutes($minutes);

        return [
            'minutes' => $minutes,
            'visitors' => Session::where('last_activity_at', '>=', $since)
                ->distinct()
                ->count('visitor_id'),
            'recent' => Event::with('session:id,country,city,device,source')
                ->where('occurred_at', '>=', $since)
                ->latest('occurred_at')
                ->limit(15)
                ->get()
                ->map(fn (Event $event): array => [
                    'id' => $event->id,
                    'type' => $event->type->value,
                    'label' => $event->label ?? $event->type->label(),
                    'path' => $event->path,
                    'city' => $event->session?->city,
                    'country' => $event->session?->country,
                    'device' => $event->session?->device,
                    'source' => $event->session?->source,
                    'at' => $event->occurred_at->toIso8601String(),
                ])
                ->all(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Utilitaires
    |--------------------------------------------------------------------------
    */

    /**
     * @return Builder<Event>
     */
    private function events(?Carbon $from = null, ?Carbon $to = null): Builder
    {
        return Event::query()->whereBetween('occurred_at', [
            $from ?? $this->from,
            $to ?? $this->to,
        ]);
    }

    private function distinctVisitors(AnalyticsEvent $type): int
    {
        return $this->events()->where('type', $type->value)->distinct()->count('visitor_id');
    }

    /**
     * Nom lisible d'un chemin.
     *
     * @param  array<string, string>  $productNames
     */
    private function pageLabel(string $path, array $productNames): string
    {
        $slug = $this->productSlug($path);

        if ($slug !== null) {
            return $productNames[$slug] ?? 'Fiche « '.$slug.' »';
        }

        return match (true) {
            $path === '/' => 'Accueil',
            Str::startsWith($path, '/recherche') => 'Recherche',
            Str::startsWith($path, '/dashboard') => 'Espace client',
            Str::startsWith($path, '/mes-favoris') => 'Mes favoris',
            Str::startsWith($path, '/mes-commandes') => 'Mes commandes',
            Str::startsWith($path, '/mes-interventions') => 'Mes interventions',
            Str::startsWith($path, '/devis') => 'Devis',
            Str::startsWith($path, '/login') => 'Connexion',
            Str::startsWith($path, '/register') => 'Inscription',
            Str::startsWith($path, '/settings') => 'Réglages du compte',
            default => $path,
        };
    }

    private function productSlug(string $path): ?string
    {
        return Str::startsWith($path, '/produit/')
            ? Str::after($path, '/produit/')
            : null;
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<string, string>
     */
    private function productNames(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return Product::whereIn('slug', $slugs)
            ->pluck('name', 'slug')
            ->all();
    }
}
