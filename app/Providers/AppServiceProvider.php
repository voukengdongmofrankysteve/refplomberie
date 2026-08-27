<?php

namespace App\Providers;

use App\Mail\HostingerApiTransport;
use App\Services\Analytics\AnalyticsRecorder;
use App\Support\Seo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Portée requête, pas singleton : les contrôleurs l'alimentent et le
        // layout Blade la rend, mais l'état ne doit jamais fuir d'une requête
        // à la suivante sous un runtime persistant (Octane).
        $this->app->scoped(Seo::class);

        // Même raison : la visite en cours est résolue une fois par requête,
        // et ne doit surtout pas être réutilisée par la requête suivante.
        $this->app->scoped(AnalyticsRecorder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerHostingerMailer();
    }

    /**
     * Branche le transport « hostinger » sur Laravel Mailer.
     *
     * Tout le reste de l'application continue d'utiliser `Mail::send()` et les
     * notifications sans rien savoir de l'API : seul MAIL_MAILER change.
     */
    protected function registerHostingerMailer(): void
    {
        Mail::extend('hostinger', function (array $config): HostingerApiTransport {
            $token = $config['token'] ?? config('services.hostinger.token');
            $mailbox = $config['mailbox'] ?? config('services.hostinger.mailbox');

            if (blank($token) || blank($mailbox)) {
                throw new RuntimeException(
                    'Renseignez HOSTINGER_MAIL_TOKEN et HOSTINGER_MAIL_MAILBOX '
                    .'pour utiliser le mailer « hostinger ».',
                );
            }

            return new HostingerApiTransport(
                token: $token,
                mailbox: $mailbox,
                baseUrl: $config['base_url'] ?? config('services.hostinger.base_url'),
            );
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
