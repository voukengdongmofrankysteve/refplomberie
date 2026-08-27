<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\ProductWarrantyBadge;
use App\Enums\TechnicianRequestStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Technician;
use App\Models\TechnicianRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->category = Category::create([
            'slug' => 'outils',
            'label' => 'Outils',
        ]);
    }

    public function test_an_administrator_sets_warranty_badges_on_a_product(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'category_id' => $this->category->id,
            'name' => 'Clé à molette pro',
            'slug' => 'cle-a-molette-pro',
            'description' => 'Clé à molette professionnelle.',
            'price' => 15000,
            'image' => 'https://example.test/principale.jpg',
            'stock' => 12,
            'is_active' => true,
            'warranty_badges' => [
                ProductWarrantyBadge::Authentic->value,
                ProductWarrantyBadge::ManufacturerWarranty->value,
            ],
        ])->assertRedirect();

        $this->assertSame(
            [ProductWarrantyBadge::Authentic->value, ProductWarrantyBadge::ManufacturerWarranty->value],
            Product::sole()->warranty_badges,
        );
    }

    public function test_an_invalid_warranty_badge_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'category_id' => $this->category->id,
            'name' => 'Clé à molette pro',
            'slug' => 'cle-a-molette-pro',
            'description' => 'Clé à molette professionnelle.',
            'price' => 15000,
            'image' => 'https://example.test/principale.jpg',
            'stock' => 12,
            'is_active' => true,
            'warranty_badges' => ['bogus-badge'],
        ])->assertSessionHasErrors('warranty_badges.0');

        $this->assertSame(0, Product::count());
    }

    public function test_a_product_shows_its_warranty_badges_on_its_page(): void
    {
        $product = $this->makeProduct();
        $product->update(['warranty_badges' => [ProductWarrantyBadge::Authentic->value]]);

        $this->get(route('shop.product', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'product.warrantyBadges.0.value',
                ProductWarrantyBadge::Authentic->value,
            ));
    }

    public function test_an_administrator_creates_a_product_with_gallery_and_tiers(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                'category_id' => $this->category->id,
                'name' => 'Clé à molette pro',
                'slug' => 'cle-a-molette-pro',
                'description' => 'Clé à molette professionnelle.',
                'price' => 15000,
                'old_price' => 18000,
                'badge' => 'Nouveau',
                'image' => 'https://example.test/principale.jpg',
                'stock' => 12,
                'is_active' => true,
                'images' => [
                    'https://example.test/1.jpg',
                    'https://example.test/2.jpg',
                ],
                'price_tiers' => [
                    ['min_qty' => 1, 'max_qty' => 9, 'price' => 15000],
                    ['min_qty' => 10, 'max_qty' => null, 'price' => 12000],
                ],
            ])
            ->assertRedirect();

        $product = Product::sole();

        $this->assertSame('cle-a-molette-pro', $product->slug);
        $this->assertSame(2, $product->images()->count());
        $this->assertSame(2, $product->priceTiers()->count());
    }

    public function test_a_product_created_without_a_video_shows_none(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'category_id' => $this->category->id,
            'name' => 'Clé à molette pro',
            'slug' => 'cle-a-molette-pro',
            'description' => 'Clé à molette professionnelle.',
            'price' => 15000,
            'image' => 'https://example.test/principale.jpg',
            'stock' => 12,
            'is_active' => true,
        ]);

        $this->assertNull(Product::sole()->video_url);
    }

    public function test_a_product_video_url_must_be_a_valid_url(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                'category_id' => $this->category->id,
                'name' => 'Clé à molette pro',
                'slug' => 'cle-a-molette-pro',
                'description' => 'Clé à molette professionnelle.',
                'video_url' => 'pas-une-url',
                'price' => 15000,
                'image' => 'https://example.test/principale.jpg',
                'stock' => 12,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('video_url');

        $this->assertSame(0, Product::count());
    }

    public function test_a_product_shows_its_tutorial_video_on_its_page(): void
    {
        $product = $this->makeProduct();
        $product->update(['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $this->get(route('shop.product', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'product.videoUrl',
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ));
    }

    public function test_updating_a_product_replaces_its_gallery_and_tiers(): void
    {
        $product = $this->makeProduct();
        $product->images()->create(['url' => 'https://example.test/old.jpg']);
        $product->priceTiers()->create([
            'min_qty' => 1,
            'max_qty' => null,
            'price' => 9000,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'category_id' => $this->category->id,
                'name' => 'Produit renommé',
                'slug' => $product->slug,
                'description' => 'Nouvelle description.',
                'price' => 11000,
                'old_price' => null,
                'badge' => null,
                'image' => 'https://example.test/principale.jpg',
                'stock' => 3,
                'is_active' => false,
                'images' => ['https://example.test/new.jpg'],
                'price_tiers' => [],
            ])
            ->assertRedirect();

        $product->refresh();

        $this->assertSame('Produit renommé', $product->name);
        $this->assertFalse($product->is_active);
        $this->assertSame(
            ['https://example.test/new.jpg'],
            $product->images()->pluck('url')->all(),
        );
        $this->assertSame(0, $product->priceTiers()->count());
    }

    public function test_renaming_the_slug_redirects_to_the_new_url(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->admin)
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), [
                'category_id' => $this->category->id,
                'name' => 'Produit renommé',
                'slug' => 'produit-renomme',
                'description' => 'Nouvelle description.',
                'price' => 11000,
                'old_price' => null,
                'badge' => null,
                'image' => 'https://example.test/principale.jpg',
                'stock' => 3,
                'is_active' => true,
            ]);

        $product->refresh();

        // Un retour vers l'ancien slug donnerait un 404 : la redirection doit
        // suivre le nouvel identifiant URL.
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertStringContainsString('produit-renomme', $response->headers->get('Location'));

        $this->actingAs($this->admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk();
    }

    public function test_the_edit_form_posts_with_method_spoofing(): void
    {
        $product = $this->makeProduct();

        // Le formulaire téléverse des fichiers : Inertia envoie donc un POST
        // multipart accompagné de `_method=put`.
        $this->actingAs($this->admin)
            ->post(route('admin.products.update', $product), [
                '_method' => 'put',
                'category_id' => $this->category->id,
                'name' => 'Produit mis à jour',
                'slug' => $product->slug,
                'description' => 'Description mise à jour.',
                'price' => 17000,
                'old_price' => null,
                'badge' => null,
                'image' => 'https://example.test/principale.jpg',
                'stock' => 9,
                'is_active' => true,
            ])
            ->assertRedirect();

        $product->refresh();

        $this->assertSame('Produit mis à jour', $product->name);
        $this->assertSame(17000, $product->price);
    }

    public function test_an_administrator_deletes_a_product(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame(0, Product::count());
    }

    public function test_an_administrator_advances_an_order_status(): void
    {
        $order = Order::create([
            'reference' => 'CMD-TEST-001',
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'subtotal' => 10000,
            'shipping' => 3500,
            'total' => 13500,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Shipped->value,
                'note' => 'Colis remis au transporteur.',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertSame('Colis remis au transporteur.', $order->note);
    }

    public function test_an_administrator_assigns_a_technician_to_a_request(): void
    {
        $technician = Technician::create([
            'name' => 'Paul Nkemdirim',
            'specialty' => 'Plomberie générale',
            'experience' => '8 ans',
            'rating' => 4.9,
            'jobs_count' => 10,
            'photo' => 'https://example.test/paul.jpg',
            'is_available' => true,
        ]);

        $request = TechnicianRequest::create([
            'reference' => 'INT-TEST-001',
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'address' => 'Bastos, Yaoundé',
            'service' => 'Dépannage urgence',
            'description' => 'Fuite au compteur.',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.technician-requests.update', $request), [
                'status' => TechnicianRequestStatus::Assigned->value,
                'technician_id' => $technician->id,
                'admin_note' => 'Paul passe demain matin.',
            ])
            ->assertRedirect();

        $request->refresh();

        $this->assertSame(TechnicianRequestStatus::Assigned, $request->status);
        $this->assertSame($technician->id, $request->technician_id);
    }

    public function test_an_administrator_cannot_drop_their_own_admin_role(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.customers.update', $this->admin), [
                'role' => UserRole::Customer->value,
            ])
            ->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    private function makeProduct(): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'slug' => 'produit-test',
            'name' => 'Produit test',
            'description' => 'Description de test.',
            'price' => 10000,
            'image' => 'https://example.test/image.jpg',
            'stock' => 5,
            'is_active' => true,
        ]);
    }
}
