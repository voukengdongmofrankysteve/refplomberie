<?php

namespace App\Services\Analytics;

use Illuminate\Support\Str;

/**
 * Lecture d'un en-tête User-Agent.
 *
 * Volontairement sommaire : on cherche à répondre à « plutôt téléphone ou
 * plutôt ordinateur, plutôt Chrome ou plutôt Safari », pas à identifier
 * précisément un appareil. Les bibliothèques qui font mieux embarquent des
 * milliers d'expressions régulières pour un gain nul à notre échelle.
 */
class UserAgent
{
    /** Fragments présents dans les agents des robots d'indexation. */
    private const BOTS = [
        'bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'preview',
        'headless', 'curl', 'wget', 'python-requests', 'okhttp', 'phantomjs',
        'lighthouse', 'pingdom', 'uptime', 'monitor', 'scrapy', 'axios',
        'go-http-client', 'java/', 'libwww', 'whatsapp', 'telegram',
    ];

    public function __construct(private readonly string $agent)
    {
        //
    }

    public static function make(?string $agent): self
    {
        return new self(Str::lower((string) $agent));
    }

    /**
     * Un agent vide compte comme un robot : aucun navigateur ne s'annonce
     * anonymement, et les scripts de scan, si.
     */
    public function isBot(): bool
    {
        if (trim($this->agent) === '') {
            return true;
        }

        return Str::contains($this->agent, self::BOTS);
    }

    /** « mobile », « tablette » ou « ordinateur ». */
    public function device(): string
    {
        if (Str::contains($this->agent, ['ipad', 'tablet', 'playbook', 'silk'])) {
            return 'tablette';
        }

        if (Str::contains($this->agent, ['mobi', 'android', 'iphone', 'ipod', 'windows phone'])) {
            return 'mobile';
        }

        return 'ordinateur';
    }

    public function platform(): string
    {
        return match (true) {
            Str::contains($this->agent, 'android') => 'Android',
            Str::contains($this->agent, ['iphone', 'ipad', 'ipod']) => 'iOS',
            Str::contains($this->agent, 'windows') => 'Windows',
            // « mac os » apparaît aussi dans les agents iOS : testé après eux.
            Str::contains($this->agent, ['mac os', 'macintosh']) => 'macOS',
            Str::contains($this->agent, ['cros', 'chrome os']) => 'ChromeOS',
            Str::contains($this->agent, 'linux') => 'Linux',
            default => 'Inconnu',
        };
    }

    public function browser(): string
    {
        return match (true) {
            // Edge et Opera se présentent aussi comme Chrome : testés avant.
            Str::contains($this->agent, ['edg/', 'edge']) => 'Edge',
            Str::contains($this->agent, ['opr/', 'opera']) => 'Opera',
            Str::contains($this->agent, 'samsungbrowser') => 'Samsung Internet',
            Str::contains($this->agent, 'firefox') => 'Firefox',
            Str::contains($this->agent, 'chrome') => 'Chrome',
            Str::contains($this->agent, 'safari') => 'Safari',
            default => 'Autre',
        };
    }
}
