<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le journal d'audit ne trace que les actions d'un administrateur
 * authentifié — jamais celles d'un client sur ses propres données.
 */
class AuditLogTest extends TestCase
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

    public function test_creating_a_product_as_an_administrator_is_logged(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'category_id' => $this->category->id,
            'name' => 'Clé à molette pro',
            'slug' => 'cle-a-molette-pro',
            'description' => 'Description.',
            'price' => 15000,
            'image' => 'https://example.test/image.jpg',
            'stock' => 12,
            'is_active' => true,
        ]);

        $product = Product::sole();
        $log = AuditLog::sole();

        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame('created', $log->action);
        $this->assertSame(Product::class, $log->auditable_type);
        $this->assertSame($product->id, $log->auditable_id);
        $this->assertSame('Clé à molette pro', $log->snapshot['name']);
    }

    public function test_updating_a_product_logs_only_the_changed_fields(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'category_id' => $this->category->id,
                'name' => 'Nouveau nom',
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'image' => $product->image,
                'stock' => $product->stock,
                'is_active' => $product->is_active,
            ]);

        $log = AuditLog::where('action', 'updated')->sole();

        $this->assertArrayHasKey('name', $log->changes);
        $this->assertSame('Produit test', $log->changes['name']['old']);
        $this->assertSame('Nouveau nom', $log->changes['name']['new']);
        // Les champs non modifiés ne polluent pas le journal.
        $this->assertArrayNotHasKey('slug', $log->changes);
    }

    public function test_deleting_a_product_is_logged_with_a_snapshot(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product));

        $log = AuditLog::where('action', 'deleted')->sole();

        $this->assertSame('Produit test', $log->snapshot['name']);
    }

    public function test_a_customer_editing_their_own_profile_is_not_audited(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)->patch(route('profile.update'), [
            'name' => 'Nouveau nom',
            'email' => $customer->email,
        ]);

        $this->assertSame(0, AuditLog::count());
    }

    public function test_an_unauthenticated_action_is_not_audited(): void
    {
        $product = $this->makeProduct();

        // Une commande passée par un visiteur touche le stock du produit,
        // sans qu'aucun administrateur ne soit dans la boucle.
        $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '690000000',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $this->assertSame(0, AuditLog::count());
    }

    public function test_an_administrator_changing_a_customer_role_is_logged(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($this->admin)
            ->put(route('admin.customers.update', $customer), [
                'role' => UserRole::Admin->value,
            ]);

        $log = AuditLog::where('auditable_type', User::class)->sole();

        $this->assertSame('updated', $log->action);
        $this->assertSame('customer', $log->changes['role']['old']);
        $this->assertSame('admin', $log->changes['role']['new']);
    }

    public function test_only_an_administrator_can_view_the_audit_log(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('admin.audit-log.index'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('admin.audit-log.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/audit-log/index')
                ->has('logs')
                ->has('types')
                ->has('admins'));
    }

    public function test_the_log_can_be_filtered_by_action(): void
    {
        // Créée en tant qu'administrateur : la trace de création compte
        // elle aussi, pour donner deux actions à distinguer par le filtre.
        $this->actingAs($this->admin);
        $product = $this->makeProduct();

        $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product));

        $this->assertSame(2, AuditLog::count());

        $this->actingAs($this->admin)
            ->get(route('admin.audit-log.index', ['action' => 'deleted']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('logs.data', 1));
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
            'stock' => 50,
            'is_active' => true,
        ]);
    }
}
