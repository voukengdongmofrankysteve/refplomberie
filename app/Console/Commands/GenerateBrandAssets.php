<?php

namespace App\Console\Commands;

use GdImage;
use Illuminate\Console\Command;

/**
 * Génère les icônes de marque servies depuis `public/`.
 *
 * Le pictogramme reprend le logo de la vitrine : carré vert arrondi, disque
 * bleu nuit percé de deux fentes verticales.
 */
class GenerateBrandAssets extends Command
{
    protected $signature = 'brand:assets';

    protected $description = 'Génère favicons, icônes PWA et image de partage aux couleurs Réf. Plomberie';

    private const GREEN = [37, 211, 102];

    private const INK = [26, 26, 46];

    public function handle(): int
    {
        $public = public_path();

        // ── Favicon vectoriel ─────────────────────────────────────────────
        file_put_contents($public.'/favicon.svg', $this->svgIcon());
        $this->line('  favicon.svg');

        // ── Déclinaisons PNG ──────────────────────────────────────────────
        foreach ([16, 32, 48, 180, 192, 512] as $size) {
            $icon = $this->renderIcon($size);
            $name = match ($size) {
                180 => 'apple-touch-icon.png',
                192 => 'icon-192.png',
                512 => 'icon-512.png',
                default => "favicon-{$size}.png",
            };

            imagepng($icon, $public.'/'.$name, 9);
            imagedestroy($icon);
            $this->line('  '.$name);
        }

        // ── favicon.ico (conteneur ICO embarquant des PNG 16/32/48) ───────
        file_put_contents($public.'/favicon.ico', $this->ico([16, 32, 48]));
        $this->line('  favicon.ico');

        // ── Image de partage social 1200×630 ──────────────────────────────
        $share = $this->renderShareImage();
        imagepng($share, $public.'/og-image.png', 9);
        imagedestroy($share);
        $this->line('  og-image.png');

        // ── Manifeste PWA ─────────────────────────────────────────────────
        file_put_contents($public.'/site.webmanifest', json_encode([
            'name' => 'Réf. Plomberie — Matériaux & Équipements',
            'short_name' => 'Réf.Plomberie',
            'description' => 'Robinetterie, tuyauterie, sanitaire et outillage professionnel au Cameroun.',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#25D366',
            'lang' => 'fr',
            'icons' => [
                ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => '/favicon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml'],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->line('  site.webmanifest');

        $this->info('Icônes de marque régénérées.');

        return self::SUCCESS;
    }

    /** Le même tracé que le composant React `AppLogoIcon`. */
    private function svgIcon(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <rect width="24" height="24" rx="5" fill="#25D366"/>
    <path d="M12 4C7.58 4 4 7.58 4 12s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm-1.2 11.2H9.2V8.8h1.6v6.4zm3.2 0h-1.6V8.8h1.6v6.4z" fill="#1A1A2E"/>
</svg>

SVG;
    }

    /**
     * Rendu bitmap du pictogramme : fond vert arrondi, disque encre, deux
     * fentes verticales reprenant la découpe du logo.
     */
    private function renderIcon(int $size): GdImage
    {
        // Rendu 4× puis réduction : bords nets sans crénelage.
        $scale = 4;
        $canvas = imagecreatetruecolor($size * $scale, $size * $scale);
        imagealphablending($canvas, true);

        $side = $size * $scale;
        $green = imagecolorallocate($canvas, ...self::GREEN);
        $ink = imagecolorallocate($canvas, ...self::INK);

        $this->roundedRectangle($canvas, $side, (int) ($side * 0.21), $green);

        $centre = (int) ($side / 2);
        imagefilledellipse($canvas, $centre, $centre, (int) ($side * 0.68), (int) ($side * 0.68), $ink);

        // Fentes verticales, teintées comme le fond.
        $slotWidth = (int) round($side * 0.075);
        $slotHeight = (int) round($side * 0.30);
        $offset = (int) round($side * 0.105);

        foreach ([-$offset, $offset] as $dx) {
            imagefilledrectangle(
                $canvas,
                $centre + $dx - (int) ($slotWidth / 2),
                $centre - (int) ($slotHeight / 2),
                $centre + $dx + (int) ($slotWidth / 2),
                $centre + (int) ($slotHeight / 2),
                $green,
            );
        }

        $icon = imagecreatetruecolor($size, $size);
        imagealphablending($icon, false);
        imagesavealpha($icon, true);
        imagecopyresampled($icon, $canvas, 0, 0, 0, 0, $size, $size, $side, $side);
        imagedestroy($canvas);

        return $icon;
    }

    /** Carré aux coins arrondis, transparent au-delà. */
    private function roundedRectangle(GdImage $image, int $side, int $radius, int $colour): void
    {
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagealphablending($image, false);
        imagefilledrectangle($image, 0, 0, $side, $side, $transparent);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        imagefilledrectangle($image, $radius, 0, $side - $radius, $side, $colour);
        imagefilledrectangle($image, 0, $radius, $side, $side - $radius, $colour);

        foreach ([[$radius, $radius], [$side - $radius, $radius], [$radius, $side - $radius], [$side - $radius, $side - $radius]] as [$cx, $cy]) {
            imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $colour);
        }
    }

    /**
     * Assemble un fichier ICO embarquant plusieurs PNG.
     *
     * @param  array<int, int>  $sizes
     */
    private function ico(array $sizes): string
    {
        $images = [];

        foreach ($sizes as $size) {
            $icon = $this->renderIcon($size);
            ob_start();
            imagepng($icon, null, 9);
            $images[$size] = (string) ob_get_clean();
            imagedestroy($icon);
        }

        // En-tête ICONDIR : réservé, type 1 (icône), nombre d'images.
        $header = pack('vvv', 0, 1, count($images));
        $offset = 6 + 16 * count($images);
        $directory = '';
        $payload = '';

        foreach ($images as $size => $png) {
            $directory .= pack(
                'CCCCvvVV',
                $size >= 256 ? 0 : $size, // largeur
                $size >= 256 ? 0 : $size, // hauteur
                0,                        // palette
                0,                        // réservé
                1,                        // plans
                32,                       // bits par pixel
                strlen($png),
                $offset,
            );

            $payload .= $png;
            $offset += strlen($png);
        }

        return $header.$directory.$payload;
    }

    /** Bannière 1200×630 utilisée par défaut pour les partages sociaux. */
    private function renderShareImage(): GdImage
    {
        $width = 1200;
        $height = 630;
        $image = imagecreatetruecolor($width, $height);

        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));

        // Bande verte inférieure, rappel de la charte.
        imagefilledrectangle($image, 0, $height - 18, $width, $height, imagecolorallocate($image, ...self::GREEN));

        $icon = $this->renderIcon(180);
        imagecopy($image, $icon, 90, 150, 0, 0, 180, 180);
        imagedestroy($icon);

        $font = resource_path('fonts/Outfit-Variable.ttf');

        if (is_file($font)) {
            $ink = imagecolorallocate($image, ...self::INK);
            $green = imagecolorallocate($image, ...self::GREEN);
            $soft = imagecolorallocate($image, 74, 74, 106);

            $prefix = 'Réf.';
            $box = imagettfbbox(78, 0, $font, $prefix);
            $prefixWidth = $box[2] - $box[0];

            $this->text($image, 78, 310, 265, $ink, $font, $prefix, weight: 2);
            $this->text($image, 78, 310 + $prefixWidth, 265, $green, $font, 'Plomberie', weight: 2);
            $this->text($image, 34, 312, 325, $soft, $font, 'Matériaux & Équipements');
            $this->text($image, 28, 312, 415, $soft, $font, 'Robinetterie · Tuyauterie · Sanitaire · Outillage');
            $this->text($image, 28, 312, 465, $soft, $font, 'Livraison rapide partout au Cameroun');
        }

        return $image;
    }

    /**
     * Écrit du texte, avec un faux-gras optionnel.
     *
     * La police Outfit livrée est variable : FreeType n'en rend que l'instance
     * par défaut (Regular). Superposer quelques passes légèrement décalées
     * épaissit le trait, ce qui rend au titre son allure de logo.
     */
    private function text(
        GdImage $image,
        float $size,
        int $x,
        int $y,
        int $colour,
        string $font,
        string $text,
        int $weight = 0,
    ): void {
        for ($dx = 0; $dx <= $weight; $dx++) {
            for ($dy = 0; $dy <= $weight; $dy++) {
                imagettftext($image, $size, 0, $x + $dx, $y + $dy, $colour, $font, $text);
            }
        }
    }
}
