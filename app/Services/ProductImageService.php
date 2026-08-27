<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Traite les images produit téléversées depuis le back-office.
 *
 * Chaque image est redimensionnée, ré-encodée en WebP (nettement plus léger
 * que le JPEG d'origine) et estampillée du filigrane « Réf.Plomberie /
 * Matériaux & Équipements » avant d'être écrite sur le disque public.
 */
class ProductImageService
{
    /** Largeur maximale conservée pour une image produit. */
    private const MAX_WIDTH = 1600;

    /** Hauteur maximale conservée pour une image produit. */
    private const MAX_HEIGHT = 1600;

    /** Qualité WebP : bon compromis netteté / poids. */
    private const QUALITY = 82;

    private const DISK = 'public';

    private const DIRECTORY = 'products';

    /**
     * Optimise puis stocke une image, et retourne son chemin relatif au disque.
     */
    public function store(UploadedFile $file): string
    {
        $image = $this->decode($file);

        try {
            $image = $this->resize($image);
            $this->watermark($image);

            $path = self::DIRECTORY.'/'.Str::uuid()->toString().'.webp';

            Storage::disk(self::DISK)->put($path, $this->encode($image));

            return $path;
        } finally {
            imagedestroy($image);
        }
    }

    /**
     * Supprime un fichier précédemment stocké.
     *
     * Les URL externes (catalogue de démonstration) sont ignorées : elles ne
     * nous appartiennent pas.
     */
    public function delete(?string $path): void
    {
        if ($path === null || $path === '' || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    /**
     * URL publique d'une image, qu'elle soit stockée localement ou distante.
     *
     * Pour un fichier local, on renvoie une URL **relative à la racine**
     * (`/storage/…`). Le disque `public` construit son URL à partir de
     * `APP_URL` : une valeur erronée ou un cache de configuration périmé en
     * production diffuserait sinon des images pointant vers `localhost`.
     * Une URL relative reste juste quel que soit le domaine servi.
     */
    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $url = Storage::disk(self::DISK)->url($path);

        return parse_url($url, PHP_URL_PATH) ?: $url;
    }

    /**
     * Variante absolue, exigée par les robots sociaux (Open Graph, Twitter),
     * qui refusent les chemins relatifs.
     */
    public static function absoluteUrl(?string $path): ?string
    {
        $url = self::url($path);

        if ($url === null || Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return url($url);
    }

    private function decode(UploadedFile $file): GdImage
    {
        $contents = file_get_contents($file->getRealPath());
        $image = $contents === false ? false : @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            throw new RuntimeException("Image illisible : {$file->getClientOriginalName()}.");
        }

        return $image;
    }

    /**
     * Réduit l'image pour tenir dans le gabarit, sans jamais l'agrandir.
     */
    private function resize(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height, 1);

        if ($ratio >= 1) {
            return $image;
        }

        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);

        // Un fond blanc évite les aplats noirs si la source est transparente.
        imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
        imagecopyresampled(
            $resized,
            $image,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            $width, $height,
        );

        imagedestroy($image);

        return $resized;
    }

    /**
     * Incruste le filigrane de la marque en bas à droite.
     */
    /**
     * Incruste le filigrane de la boutique au centre de l'image.
     *
     * Au centre plutôt que dans un coin : un filigrane d'angle se recadre ou
     * se rogne trop facilement quand la photo est reprise ailleurs.
     */
    private function watermark(GdImage $image): void
    {
        $font = resource_path('fonts/Outfit-Variable.ttf');

        if (! is_file($font)) {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Tailles proportionnelles : le filigrane reste lisible quel que soit
        // le gabarit, sans jamais manger l'image.
        $titleSize = max(12.0, $width / 22);
        $baselineSize = max(8.0, $width / 48);
        $padding = (int) round($width / 40);

        $title = (string) config('shop.watermark.title');
        $baseline = (string) config('shop.watermark.baseline');

        $titleBox = imagettfbbox($titleSize, 0, $font, $title);
        $baselineBox = imagettfbbox($baselineSize, 0, $font, $baseline);

        if ($titleBox === false || $baselineBox === false) {
            return;
        }

        $titleWidth = $titleBox[2] - $titleBox[0];
        $titleHeight = $titleBox[1] - $titleBox[7];
        $baselineWidth = $baselineBox[2] - $baselineBox[0];
        $baselineHeight = $baselineBox[1] - $baselineBox[7];

        $blockWidth = (int) max($titleWidth, $baselineWidth);
        $gap = (int) round($titleSize * 0.35);
        $blockHeight = (int) ($titleHeight + $gap + $baselineHeight);

        // Bloc centré sur les deux axes.
        $left = (int) (($width - $blockWidth) / 2);
        $top = (int) (($height - $blockHeight) / 2);

        imagealphablending($image, true);

        // Voile discret, ajusté au texte : au centre de l'image, un aplat
        // trop opaque masquerait le produit lui-même.
        $veil = imagecolorallocatealpha($image, 0, 0, 0, 105);
        imagefilledrectangle(
            $image,
            $left - $padding,
            $top - $padding,
            $left + $blockWidth + $padding,
            $top + $blockHeight + $padding,
            $veil,
        );

        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 70);
        $white = imagecolorallocatealpha($image, 255, 255, 255, 20);
        $green = imagecolorallocatealpha($image, 37, 211, 102, 20);

        $titleBaseline = (int) ($top + $titleHeight);

        // « Réf. » en blanc, « Plomberie » en vert de marque, comme le logo.
        // L'ensemble est recentré à partir de la largeur réelle des deux mots.
        $prefix = 'Réf.';
        $prefixBox = imagettfbbox($titleSize, 0, $font, $prefix);
        $prefixWidth = $prefixBox === false ? 0 : $prefixBox[2] - $prefixBox[0];
        $titleLeft = (int) (($width - $titleWidth) / 2);

        $this->text($image, $titleSize, $titleLeft + 2, $titleBaseline + 2, $shadow, $font, $prefix, 1);
        $this->text($image, $titleSize, $titleLeft, $titleBaseline, $white, $font, $prefix, 1);

        $plomberieX = (int) ($titleLeft + $prefixWidth);

        $this->text($image, $titleSize, $plomberieX + 2, $titleBaseline + 2, $shadow, $font, 'Plomberie', 1);
        $this->text($image, $titleSize, $plomberieX, $titleBaseline, $green, $font, 'Plomberie', 1);

        $baselineY = (int) ($titleBaseline + $gap + $baselineHeight);
        $baselineLeft = (int) (($width - $baselineWidth) / 2);

        $this->text($image, $baselineSize, $baselineLeft + 1, $baselineY + 1, $shadow, $font, $baseline);
        $this->text($image, $baselineSize, $baselineLeft, $baselineY, $white, $font, $baseline);
    }

    /**
     * Écrit du texte, avec un faux-gras optionnel.
     *
     * La police Outfit livrée est variable : FreeType n'en rend que l'instance
     * Regular. Superposer des passes décalées épaissit le trait du filigrane.
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

    private function encode(GdImage $image): string
    {
        ob_start();
        imagewebp($image, null, self::QUALITY);

        $contents = ob_get_clean();

        if ($contents === false || $contents === '') {
            throw new RuntimeException("Échec de l'encodage WebP.");
        }

        return $contents;
    }
}
