<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'slug' => 'chauffe-eau',
            'label' => 'Chauffe-eau',
        ]);
    }

    public function test_a_short_term_returns_nothing(): void
    {
        $this->makeProduct('Chauffe-eau 200L', 'chauffe-eau-200l');

        $this->getJson(route('search', ['q' => 'c']))
            ->assertOk()
            ->assertExactJson(['products' => [], 'categories' => []]);
    }

    public function test_products_are_found_by_name_and_by_description(): void
    {
        $this->makeProduct('Chauffe-eau 200L', 'chauffe-eau-200l');
        $this->makeProduct(
            'Ballon thermodynamique',
            'ballon-thermodynamique',
            description: 'Un chauffe-eau à pompe à chaleur intégrée.',
        );

        $response = $this->getJson(route('search', ['q' => 'chauffe']));

        $response->assertOk();
        $this->assertCount(2, $response->json('products'));
    }

    public function test_a_name_match_ranks_before_a_description_match(): void
    {
        $this->makeProduct(
            'Ballon thermodynamique',
            'ballon-thermodynamique',
            description: 'Un chauffe-eau à pompe à chaleur intégrée.',
        );
        $this->makeProduct('Chauffe-eau 200L', 'chauffe-eau-200l');

        $response = $this->getJson(route('search', ['q' => 'chauffe']));

        $this->assertSame(
            'Chauffe-eau 200L',
            $response->json('products.0.name'),
        );
    }

    public function test_hidden_products_never_surface(): void
    {
        $this->makeProduct('Chauffe-eau 200L', 'chauffe-eau-200l', active: false);

        $this->getJson(route('search', ['q' => 'chauffe']))
            ->assertOk()
            ->assertJsonCount(0, 'products');
    }

    public function test_categories_are_matched_too(): void
    {
        $this->makeProduct('Robinet mitigeur', 'robinet-mitigeur');

        $response = $this->getJson(route('search', ['q' => 'chauffe']));

        $this->assertSame('chauffe-eau', $response->json('categories.0.id'));
    }

    public function test_the_result_list_is_capped(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $this->makeProduct("Robinet modèle {$i}", "robinet-modele-{$i}");
        }

        $this->getJson(route('search', ['q' => 'robinet']))
            ->assertOk()
            ->assertJsonCount(10, 'products');
    }

    private function makeProduct(
        string $name,
        string $slug,
        string $description = 'Description de test.',
        bool $active = true,
    ): Product {
        return Product::create([
            'category_id' => $this->category->id,
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'price' => 10000,
            'image' => 'https://example.test/image.jpg',
            'stock' => 5,
            'is_active' => $active,
        ]);
    }
}
