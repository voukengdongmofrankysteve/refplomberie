<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\ProductRecommendations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * « Souvent achetés ensemble » : fondé sur l'historique réel des commandes,
 * pas sur la catégorie.
 */
class ProductRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['slug' => 'outils', 'label' => 'Outils']);
    }

    public function test_products_bought_in_the_same_order_are_recommended(): void
    {
        $tuyau = $this->makeProduct('tuyau-pvc');
        $collier = $this->makeProduct('collier-serrage');
        $autre = $this->makeProduct('autre-produit');

        $this->makeOrder([$tuyau, $collier]);

        $recommendations = app(ProductRecommendations::class)->frequentlyBoughtWith($tuyau);

        $this->assertTrue($recommendations->pluck('id')->contains($collier->id));
        $this->assertFalse($recommendations->pluck('id')->contains($autre->id));
        // Le produit consulté ne se recommande jamais lui-même.
        $this->assertFalse($recommendations->pluck('id')->contains($tuyau->id));
    }

    public function test_recommendations_are_ranked_by_how_often_they_are_bought_together(): void
    {
        $tuyau = $this->makeProduct('tuyau-pvc');
        $collier = $this->makeProduct('collier-serrage');
        $colle = $this->makeProduct('colle-pvc');

        // Le collier accompagne le tuyau deux fois, la colle une seule.
        $this->makeOrder([$tuyau, $collier]);
        $this->makeOrder([$tuyau, $collier]);
        $this->makeOrder([$tuyau, $colle]);

        $recommendations = app(ProductRecommendations::class)->frequentlyBoughtWith($tuyau);

        $this->assertSame($collier->id, $recommendations->first()->id);
    }

    public function test_a_cancelled_order_does_not_count_as_a_purchase_association(): void
    {
        $tuyau = $this->makeProduct('tuyau-pvc');
        $collier = $this->makeProduct('collier-serrage');

        $this->makeOrder([$tuyau, $collier], status: OrderStatus::Cancelled);

        $recommendations = app(ProductRecommendations::class)->frequentlyBoughtWith($tuyau);

        $this->assertTrue($recommendations->isEmpty());
    }

    public function test_an_inactive_product_is_never_recommended(): void
    {
        $tuyau = $this->makeProduct('tuyau-pvc');
        $collier = $this->makeProduct('collier-serrage', isActive: false);

        $this->makeOrder([$tuyau, $collier]);

        $recommendations = app(ProductRecommendations::class)->frequentlyBoughtWith($tuyau);

        $this->assertTrue($recommendations->isEmpty());
    }

    public function test_the_product_page_exposes_the_recommendations(): void
    {
        $tuyau = $this->makeProduct('tuyau-pvc');
        $collier = $this->makeProduct('collier-serrage');

        $this->makeOrder([$tuyau, $collier]);

        $this->get(route('shop.product', $tuyau))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('frequentlyBoughtWith', 1)
                    ->where('frequentlyBoughtWith.0.slug', $collier->slug),
            );
    }

    private function makeProduct(string $slug, bool $isActive = true): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'slug' => $slug,
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'description' => 'Description de test.',
            'price' => 5000,
            'image' => 'https://example.test/image.jpg',
            'stock' => 50,
            'is_active' => $isActive,
        ]);
    }

    /**
     * @param  array<int, Product>  $products
     */
    private function makeOrder(array $products, OrderStatus $status = OrderStatus::Pending): Order
    {
        $order = Order::create([
            'reference' => Order::generateReference(),
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '690000000',
            'status' => $status,
            'subtotal' => 0,
            'shipping' => 0,
            'total' => 0,
        ]);

        foreach ($products as $product) {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $product->price,
                'quantity' => 1,
                'line_total' => $product->price,
            ]);
        }

        return $order;
    }
}
