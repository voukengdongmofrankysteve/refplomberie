<?php

namespace App\Services\Analytics;

use App\Enums\AnalyticsEvent;
use App\Models\Analytics\Event;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Throwable;

/**
 * Écrit ce qui se passe sur le site et dans l'application.
 *
 * Un seul exemplaire par requête : la visite est résolue une fois, puis
 * réutilisée par tous les événements de la même page.
 *
 * Règle de fond : la mesure ne doit jamais casser la boutique. Toute écriture
 * est enveloppée — si la base d'audience refuse quelque chose, le client passe
 * quand même sa commande et l'incident part dans les journaux.
 */
class AnalyticsRecorder
{
    private ?Visitor $visitor = null;

    private ?Session $session = null;

    private bool $resolved = false;

    public function __construct(private readonly Geolocator $geolocator) {}

    public function enabled(): bool
    {
        return (bool) config('analytics.enabled', true);
    }

    /**
     * Enregistre une action.
     *
     * Le sujet est facultatif : un produit pour une consultation de fiche,
     * une commande pour un achat. Il permet ensuite de classer les produits
     * les plus vus sans stocker leur nom mille fois.
     *
     * @param  array<string, mixed>  $meta
     */
    public function record(
        AnalyticsEvent $type,
        ?Model $subject = null,
        ?string $label = null,
        ?int $value = null,
        array $meta = [],
        ?string $path = null,
        ?string $title = null,
    ): ?Event {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $session = $this->session();

            if ($session === null) {
                return null;
            }

            $now = Carbon::now();

            $event = Event::create([
                'session_id' => $session->id,
                'visitor_id' => $session->visitor_id,
                'user_id' => $session->user_id,
                'type' => $type,
                'path' => Str::limit($path ?? $this->currentPath(), 250, ''),
                'title' => $title === null ? null : Str::limit($title, 250, ''),
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'label' => $label === null ? null : Str::limit($label, 250, ''),
                'value' => $value,
                'meta' => $meta === [] ? null : $meta,
                'occurred_at' => $now,
            ]);

            $session->forceFill([
                'events_count' => $session->events_count + 1,
                'page_views' => $session->page_views + ($type === AnalyticsEvent::PageView ? 1 : 0),
                'last_activity_at' => $now,
            ])->save();

            $session->visitor?->forceFill([
                'events_count' => $session->visitor->events_count + 1,
                'last_seen_at' => $now,
            ])->save();

            return $event;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Visite en cours, créée au besoin.
     *
     * Renvoie `null` hors requête HTTP — une commande console ou une tâche
     * planifiée n'a pas de visiteur à mesurer.
     */
    public function session(): ?Session
    {
        if ($this->resolved) {
            return $this->session;
        }

        $this->resolved = true;

        if (! $this->enabled()) {
            return null;
        }

        $request = request();

        // Hors requête HTTP — une commande console, une tâche planifiée — il
        // n'y a personne à mesurer. `runningInConsole()` ne suffirait pas : il
        // est vrai aussi pendant les tests, qui eux simulent de vraies visites.
        if (! $request instanceof Request || $request->server('REQUEST_METHOD') === null) {
            return null;
        }

        try {
            $this->session = $this->resolveSession($request);
        } catch (Throwable $e) {
            report($e);
            $this->session = null;
        }

        return $this->session;
    }

    /**
     * Ferme la visite courante : le prochain événement en ouvrira une neuve.
     *
     * Utilisé par les tests, et par la déconnexion.
     */
    public function forget(): void
    {
        $this->visitor = null;
        $this->session = null;
        $this->resolved = false;
    }

    private function resolveSession(Request $request): ?Session
    {
        $agent = UserAgent::make($request->userAgent());

        // Les robots ne sont pas mesurés du tout : comptés, ils gonfleraient
        // l'audience d'un trafic que personne n'a l'intention d'acheter.
        if ($agent->isBot() && ! $this->isMobileApp($request)) {
            return null;
        }

        $visitor = $this->resolveVisitor($request);
        $now = Carbon::now();
        $source = $this->isMobileApp($request) ? 'app' : 'web';

        $window = $now->copy()->subMinutes((int) config('analytics.session_minutes', 30));

        $session = Session::where('visitor_id', $visitor->id)
            ->where('source', $source)
            ->where('last_activity_at', '>=', $window)
            ->latest('last_activity_at')
            ->first();

        if ($session !== null) {
            // Visite en cours : on note seulement que le compte a pu être
            // identifié depuis, une connexion ayant lieu en cours de route.
            if ($session->user_id === null && $request->user() !== null) {
                $session->forceFill(['user_id' => $request->user()->id])->save();
            }

            $session->setRelation('visitor', $visitor);

            return $session;
        }

        $ip = (string) $request->ip();
        $ipHash = $this->hash($ip);
        $referrer = (string) $request->headers->get('referer', '');

        $session = Session::create([
            'visitor_id' => $visitor->id,
            'user_id' => $request->user()?->id,
            'ip_hash' => $ipHash,
            'source' => $source,
            'device' => $source === 'app' ? 'mobile' : $agent->device(),
            'platform' => $this->platform($request, $agent),
            'browser' => $source === 'app' ? 'Application' : $agent->browser(),
            'referrer' => $this->externalReferrer($referrer),
            'referrer_host' => $this->referrerHost($referrer),
            'landing_path' => Str::limit($this->currentPath(), 250, ''),
            'started_at' => $now,
            'last_activity_at' => $now,
        ]);

        $visitor->forceFill([
            'sessions_count' => $visitor->sessions_count + 1,
            'last_seen_at' => $now,
            'user_id' => $request->user()?->id ?? $visitor->user_id,
        ])->save();

        $session->setRelation('visitor', $visitor);

        // La localisation part après la réponse : interroger un service
        // extérieur avant d'afficher la page ferait attendre le visiteur pour
        // une information dont lui n'a aucun usage.
        defer(function () use ($session, $ip, $ipHash): void {
            $place = $this->geolocator->locate($ip, $ipHash);

            if ($place !== []) {
                $session->forceFill($place)->save();
            }
        });

        return $session;
    }

    private function resolveVisitor(Request $request): Visitor
    {
        if ($this->visitor !== null) {
            return $this->visitor;
        }

        $uuid = $this->visitorUuid($request);
        $now = Carbon::now();

        $visitor = Visitor::firstOrCreate(
            ['uuid' => $uuid],
            [
                'user_id' => $request->user()?->id,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ],
        );

        return $this->visitor = $visitor;
    }

    /**
     * Identifiant du navigateur, ou de l'installation mobile.
     *
     * L'application n'a pas de cookies : elle transmet son propre identifiant
     * dans un en-tête, tiré au sort à la première ouverture.
     */
    private function visitorUuid(Request $request): string
    {
        $header = (string) $request->header('X-Visitor-Id', '');

        if (Str::isUuid($header)) {
            return $header;
        }

        $name = (string) config('analytics.cookie', 'rp_visiteur');
        $cookie = (string) $request->cookie($name, '');

        if (Str::isUuid($cookie)) {
            return $cookie;
        }

        $uuid = (string) Str::uuid();

        // Une année et demie : assez pour reconnaître un client fidèle, pas
        // assez pour le suivre indéfiniment.
        Cookie::queue(
            $name,
            $uuid,
            (int) config('analytics.cookie_days', 400) * 24 * 60,
        );

        return $uuid;
    }

    private function isMobileApp(Request $request): bool
    {
        return Str::isUuid((string) $request->header('X-Visitor-Id', ''))
            && $request->is('api/*');
    }

    private function platform(Request $request, UserAgent $agent): string
    {
        if (! $this->isMobileApp($request)) {
            return $agent->platform();
        }

        // L'application annonce sa plateforme dans un en-tête : son
        // User-Agent, lui, est celui de la bibliothèque HTTP de Dart et ne dit
        // rien du téléphone.
        return match (Str::lower((string) $request->header('X-Client-Platform'))) {
            'android' => 'Android',
            'ios' => 'iOS',
            default => 'Application',
        };
    }

    private function currentPath(): string
    {
        $path = '/'.ltrim(request()->path(), '/');

        return $path === '//' ? '/' : $path;
    }

    /**
     * Provenance, uniquement si elle vient d'ailleurs.
     *
     * Un lien interne n'est pas une provenance : sans ce filtre, le site
     * apparaîtrait comme sa propre première source de trafic.
     */
    private function externalReferrer(string $referrer): ?string
    {
        $host = $this->referrerHost($referrer);

        return $host === null ? null : Str::limit($referrer, 500, '');
    }

    private function referrerHost(string $referrer): ?string
    {
        $host = parse_url($referrer, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = Str::lower(Str::replaceStart('www.', '', $host));

        return $host === Str::lower(Str::replaceStart('www.', '', (string) parse_url((string) config('app.url'), PHP_URL_HOST)))
            ? null
            : Str::limit($host, 250, '');
    }

    /**
     * Empreinte d'une adresse IP.
     *
     * Salée par la clé de l'application : deux installations différentes
     * produisent des empreintes différentes, et personne ne peut retrouver
     * l'adresse d'origine en essayant les quatre milliards de possibilités.
     */
    private function hash(string $ip): string
    {
        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
