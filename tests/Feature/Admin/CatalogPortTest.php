<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CatalogPortTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->category = Category::create(['slug' => 'outils', 'label' => 'Outils']);
    }

    public function test_the_export_carries_a_bom_a_header_and_the_products(): void
    {
        $this->makeProduct('cle-a-molette', 'Clé à molette', price: 15000);

        $response = $this->actingAs($this->admin)->get(route('admin.catalog.export'));

        $response->assertOk();
        $body = $response->getContent();

        // Le BOM et le point-virgule sont ce qu'Excel attend en français.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('slug;nom;categorie;prix', $body);
        // fputcsv encadre les cellules contenant des espaces.
        $this->assertStringContainsString('cle-a-molette;"Clé à molette";outils;15000', $body);
    }

    public function test_an_import_updates_prices_and_stock(): void
    {
        $product = $this->makeProduct('cle-a-molette', 'Clé à molette', price: 15000);

        $this->import(<<<'CSV'
            slug;nom;categorie;prix;ancien_prix;stock;seuil_alerte;badge;actif;description
            cle-a-molette;;;19000;;25;8;;;
            CSV)->assertRedirect();

        $product->refresh();

        $this->assertSame(19000, $product->price);
        $this->assertSame(25, $product->stock);
        $this->assertSame(8, $product->low_stock_threshold);
        // Les cellules vides ne remplacent rien.
        $this->assertSame('Clé à molette', $product->name);
    }

    public function test_thousand_separators_and_a_currency_suffix_are_tolerated(): void
    {
        $product = $this->makeProduct('cle-a-molette', 'Clé à molette', price: 15000);

        $this->import(<<<'CSV'
            slug;prix
            cle-a-molette;"40 000 FCFA"
            CSV)->assertRedirect();

        $this->assertSame(40000, $product->fresh()->price);
    }

    public function test_an_unknown_slug_is_reported_and_creates_nothing(): void
    {
        $this->import(<<<'CSV'
            slug;prix
            produit-fantome;5000
            CSV)->assertRedirect()->assertSessionHas('importErrors');

        $this->assertSame(0, Product::count());
    }

    public function test_an_unknown_category_rejects_the_line_without_touching_the_rest(): void
    {
        $product = $this->makeProduct('cle-a-molette', 'Clé à molette', price: 15000);

        $this->import(<<<'CSV'
            slug;categorie;prix
            cle-a-molette;categorie-inconnue;99000
            CSV)->assertRedirect();

        // Le prix de la même ligne n'est pas appliqué non plus.
        $this->assertSame(15000, $product->fresh()->price);
    }

    public function test_the_active_column_accepts_oui_and_non(): void
    {
        $product = $this->makeProduct('cle-a-molette', 'Clé à molette', price: 15000);

        $this->import(<<<'CSV'
            slug;actif
            cle-a-molette;non
            CSV)->assertRedirect();

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_the_import_rejects_a_non_csv_file(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.catalog.import'), [
                'file' => UploadedFile::fake()->create('catalogue.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');
    }

    private function import(string $csv)
    {
        $path = tempnam(sys_get_temp_dir(), 'catalogue').'.csv';
        file_put_contents($path, $csv);

        return $this->actingAs($this->admin)->post(route('admin.catalog.import'), [
            'file' => new UploadedFile($path, 'catalogue.csv', 'text/csv', test: true),
        ]);
    }

    private function makeProduct(string $slug, string $name, int $price): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'slug' => $slug,
            'name' => $name,
            'description' => 'Description de test.',
            'price' => $price,
            'image' => 'https://example.test/image.jpg',
            'stock' => 10,
            'is_active' => true,
        ]);
    }
}
