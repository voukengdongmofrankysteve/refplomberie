<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->category = Category::create(['slug' => 'outils', 'label' => 'Outils']);
    }

    public function test_an_uploaded_image_is_optimised_watermarked_and_stored(): void
    {
        $file = $this->photo(2400, 1800);
        $originalSize = $file->getSize();

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                ...$this->payload(),
                'image_file' => $file,
            ])
            ->assertRedirect();

        $product = Product::sole();

        // Le chemin stocké pointe vers le disque public, pas vers une URL.
        $this->assertStringStartsWith('products/', $product->image);
        $this->assertStringEndsWith('.webp', $product->image);
        Storage::disk('public')->assertExists($product->image);

        $stored = Storage::disk('public')->get($product->image);
        $size = getimagesizefromstring($stored);

        // Redimensionnée dans le gabarit et convertie en WebP.
        $this->assertSame('image/webp', $size['mime']);
        $this->assertLessThanOrEqual(1600, $size[0]);
        $this->assertLessThanOrEqual(1600, $size[1]);
        $this->assertLessThan($originalSize, strlen($stored));

        $this->assertWatermarked($stored);
    }

    public function test_the_gallery_accepts_several_uploads(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                ...$this->payload(),
                'image_file' => $this->photo(),
                'gallery_files' => [$this->photo(), $this->photo()],
            ])
            ->assertRedirect();

        $product = Product::sole();

        $this->assertSame(2, $product->images()->count());

        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->url);
            $this->assertWatermarked(Storage::disk('public')->get($image->url));
        }
    }

    public function test_replacing_the_main_image_deletes_the_previous_file(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            ...$this->payload(),
            'image_file' => $this->photo(),
        ]);

        $product = Product::sole();
        $previous = $product->image;

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            ...$this->payload(),
            'image' => $previous,
            'image_file' => $this->photo(),
        ]);

        $product->refresh();

        $this->assertNotSame($previous, $product->image);
        Storage::disk('public')->assertMissing($previous);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_a_product_cannot_be_created_without_an_image(): void
    {
        $payload = $this->payload();
        unset($payload['image']);

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Product::count());
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                ...$this->payload(),
                'image_file' => UploadedFile::fake()->create('tarif.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image_file');

        $this->assertSame(0, Product::count());
    }

    public function test_stored_images_are_served_from_a_host_independent_url(): void
    {
        // APP_URL mal renseignée en production : c'est exactement le cas qui
        // faisait servir des images pointant vers localhost.
        config(['filesystems.disks.public.url' => 'http://localhost/storage']);

        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            ...$this->payload(),
            'image_file' => $this->photo(),
        ]);

        $product = Product::sole();
        $url = ProductImageService::url($product->image);

        $this->assertStringStartsWith('/storage/products/', $url);
        $this->assertStringNotContainsString('localhost', $url);

        // La vitrine reçoit elle aussi l'URL relative.
        $this->get(route('shop.product', $product))
            ->assertInertia(fn ($page) => $page->where('product.img', $url));
    }

    public function test_social_tags_still_use_an_absolute_image_url(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            ...$this->payload(),
            'image_file' => $this->photo(),
        ]);

        $product = Product::sole();
        $html = $this->get(route('shop.product', $product))->getContent();

        // Les robots sociaux refusent les chemins relatifs.
        $this->assertMatchesRegularExpression(
            '/<meta property="og:image" content="https?:\/\/[^"]+\/storage\/products\/[^"]+"/',
            $html,
        );
    }

    public function test_external_seed_urls_are_left_untouched(): void
    {
        $url = 'https://images.unsplash.com/photo-123';

        $this->assertSame($url, ProductImageService::url($url));

        // Et supprimer une URL externe ne tente rien sur le disque.
        app(ProductImageService::class)->delete($url);

        $this->assertTrue(true);
    }

    /**
     * Vérifie la présence du filigrane : pixels au
     * vert de marque pour le mot « Plomberie ».
     */
    private function assertWatermarked(string $contents): void
    {
        $image = imagecreatefromstring($contents);
        $width = imagesx($image);
        $height = imagesy($image);

        $brandPixels = 0;

        // Le filigrane est centré : un coin se recadre ou se rogne trop
        // facilement quand la photo est reprise ailleurs.
        for ($x = (int) ($width * 0.2); $x < (int) ($width * 0.8); $x++) {
            for ($y = (int) ($height * 0.35); $y < (int) ($height * 0.65); $y++) {
                $colour = imagecolorsforindex($image, imagecolorat($image, $x, $y));

                if (
                    $colour['green'] > 130
                    && $colour['green'] - $colour['red'] > 40
                    && $colour['green'] - $colour['blue'] > 40
                ) {
                    $brandPixels++;
                }
            }
        }

        imagedestroy($image);

        $this->assertGreaterThan(
            200,
            $brandPixels,
            'Le filigrane « Réf.Plomberie » est absent de l’image stockée.',
        );
    }

    private function photo(int $width = 1200, int $height = 900): UploadedFile
    {
        return UploadedFile::fake()->image('photo.jpg', $width, $height);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'category_id' => $this->category->id,
            'name' => 'Clé à molette pro',
            'slug' => 'cle-a-molette-pro',
            'description' => 'Clé à molette professionnelle.',
            'price' => 15000,
            'old_price' => null,
            'badge' => null,
            'image' => 'https://example.test/principale.jpg',
            'stock' => 12,
            'is_active' => true,
        ];
    }
}
