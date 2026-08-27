<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Story;
use App\Models\TechnicianRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['slug' => 'robinetterie', 'label' => 'Robinetterie']);
    }

    /*
    |--------------------------------------------------------------------------
    | Catalogue
    |--------------------------------------------------------------------------
    */

    public function test_the_bootstrap_payload_carries_what_the_app_needs_at_launch(): void
    {
        $this->getJson(route('api.bootstrap'))
            ->assertOk()
            ->assertJsonStructure([
                'store' => ['name', 'phone', 'whatsapp', 'shippingCost', 'freeShippingFrom'],
                'categories' => [['slug', 'label']],
                'services',
                'quoteValidityDays',
            ]);
    }

    public function test_the_bootstrap_payload_carries_the_running_flash_sale(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI', price: 10000);

        $sale = FlashSale::create([
            'title' => 'Vente flash',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);
        $sale->products()->attach($product->id, ['sale_price' => 7000]);

        $this->getJson(route('api.bootstrap'))
            ->assertOk()
            ->assertJsonPath('flashSale.title', 'Vente flash')
            ->assertJsonPath('flashSale.products.0.price', 7000)
            ->assertJsonPath('flashSale.products.0.originalPrice', 10000);
    }

    public function test_product_urls_are_absolute(): void
    {
        $this->makeProduct('robinet-kmei', 'Robinet KMEI');

        $image = $this->getJson(route('api.products.index'))
            ->assertOk()
            ->json('data.0.image');

        // L'application n'a pas d'origine à laquelle rattacher un chemin
        // relatif : une URL absolue est la seule qui s'affiche.
        $this->assertStringStartsWith('http', $image);
    }

    public function test_story_media_urls_are_absolute(): void
    {
        Story::create([
            'title' => 'Arrivage',
            'media_type' => 'image',
            'media_path' => 'stories/a.webp',
            'position' => 0,
            'is_active' => true,
        ]);

        $story = $this->getJson(route('api.stories'))->assertOk()->json('data.0');

        // Un chemin relatif se résout contre l'origine de la page dans un
        // navigateur ; une application mobile n'en a aucune et n'afficherait
        // rien du tout.
        $this->assertStringStartsWith('http', $story['mediaUrl']);
        $this->assertStringStartsWith('http', $story['thumbnailUrl']);
        $this->assertNotNull($story['publishedAt']);
    }

    public function test_hidden_products_are_never_listed_nor_readable(): void
    {
        $hidden = $this->makeProduct('masque', 'Produit masqué', active: false);

        $this->getJson(route('api.products.index'))->assertJsonCount(0, 'data');
        $this->getJson(route('api.products.show', $hidden))->assertNotFound();
    }

    public function test_the_catalogue_filters_by_category_and_sorts_by_price(): void
    {
        $other = Category::create(['slug' => 'sanitaire', 'label' => 'Sanitaire']);
        $this->makeProduct('cher', 'Cher', price: 90000);
        $this->makeProduct('pas-cher', 'Pas cher', price: 1000);
        $this->makeProduct('autre', 'Autre', price: 5000)->update(['category_id' => $other->id]);

        $this->getJson(route('api.products.index', ['categorie' => 'robinetterie', 'tri' => 'prix-asc']))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', 'pas-cher');
    }

    public function test_the_search_ranks_a_name_match_first(): void
    {
        $this->makeProduct('ballon', 'Ballon', description: 'Un robinet intégré.');
        $this->makeProduct('robinet-kmei', 'Robinet KMEI');

        $this->getJson(route('api.search', ['q' => 'robinet']))
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'robinet-kmei');
    }

    public function test_a_short_search_term_returns_nothing(): void
    {
        $this->makeProduct('robinet-kmei', 'Robinet KMEI');

        $this->getJson(route('api.search', ['q' => 'r']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_the_product_page_carries_gallery_tiers_and_reviews(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI');
        $product->images()->create(['url' => 'products/a.webp', 'position' => 0]);
        $product->priceTiers()->create(['min_qty' => 10, 'max_qty' => null, 'price' => 8000]);

        $this->getJson(route('api.products.show', $product))
            ->assertOk()
            ->assertJsonPath('data.slug', 'robinet-kmei')
            ->assertJsonCount(1, 'data.images')
            ->assertJsonCount(1, 'data.priceTiers')
            // Un visiteur non connecté ne peut pas noter.
            ->assertJsonPath('canReview', false);
    }

    public function test_the_product_video_is_absent_unless_set(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI');

        $this->getJson(route('api.products.show', $product))
            ->assertOk()
            ->assertJsonPath('data.videoUrl', null);

        $product->update(['video_url' => 'https://youtu.be/dQw4w9WgXcQ']);

        $this->getJson(route('api.products.show', $product))
            ->assertOk()
            ->assertJsonPath('data.videoUrl', 'https://youtu.be/dQw4w9WgXcQ');
    }

    public function test_the_product_page_carries_frequently_bought_together(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI');
        $collier = $this->makeProduct('collier-serrage', 'Collier de serrage');

        $order = Order::create([
            'reference' => Order::generateReference(),
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '690000000',
            'subtotal' => 0,
            'shipping' => 0,
            'total' => 0,
        ]);
        $order->items()->createMany([
            ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 1, 'quantity' => 1, 'line_total' => 1],
            ['product_id' => $collier->id, 'product_name' => $collier->name, 'unit_price' => 1, 'quantity' => 1, 'line_total' => 1],
        ]);

        $this->getJson(route('api.products.show', $product))
            ->assertOk()
            ->assertJsonCount(1, 'frequentlyBoughtWith')
            ->assertJsonPath('frequentlyBoughtWith.0.slug', 'collier-serrage');
    }

    /*
    |--------------------------------------------------------------------------
    | Authentification
    |--------------------------------------------------------------------------
    */

    public function test_a_customer_registers_and_receives_a_token(): void
    {
        $this->postJson(route('api.register'), [
            'name' => 'Client Mobile',
            'email' => 'client@example.com',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])
            ->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'notifications']]);

        // Aucune promotion possible par cette porte : le rôle n'est pas lu
        // depuis la requête.
        $this->assertSame(UserRole::Customer, User::sole()->role);
    }

    public function test_registration_never_grants_the_admin_role(): void
    {
        $this->postJson(route('api.register'), [
            'name' => 'Malin',
            'email' => 'malin@example.com',
            'role' => 'admin',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])->assertCreated();

        $this->assertFalse(User::sole()->isAdmin());
    }

    public function test_an_administrator_cannot_sign_in_from_the_app(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'motdepasse123',
            'role' => UserRole::Admin,
        ]);

        // Le back-office reste sur le web : un jeton volé sur un téléphone
        // ne doit jamais ouvrir l'administration.
        $this->postJson(route('api.login'), [
            'email' => 'admin@example.com',
            'password' => 'motdepasse123',
        ])->assertUnprocessable();
    }

    public function test_bad_credentials_do_not_reveal_whether_the_account_exists(): void
    {
        User::factory()->create(['email' => 'client@example.com']);

        $known = $this->postJson(route('api.login'), [
            'email' => 'client@example.com',
            'password' => 'faux',
        ])->json('errors.email.0');

        $unknown = $this->postJson(route('api.login'), [
            'email' => 'inconnu@example.com',
            'password' => 'faux',
        ])->json('errors.email.0');

        $this->assertSame($known, $unknown);
    }

    public function test_private_routes_refuse_an_anonymous_caller(): void
    {
        foreach (['api.me', 'api.orders.index', 'api.quotes.index', 'api.favorites'] as $route) {
            $this->getJson(route($route))->assertUnauthorized();
        }
    }

    public function test_logging_out_revokes_only_the_current_device(): void
    {
        $user = User::factory()->create();
        $kept = $user->createToken('Autre téléphone')->plainTextToken;
        $current = $user->createToken('Ce téléphone')->plainTextToken;

        $this->withToken($current)->postJson(route('api.logout'))->assertOk();

        // Le garde Sanctum mémorise l'utilisateur qu'il vient de résoudre ;
        // dans un vrai processus chaque requête repart à zéro, ici il faut
        // le dire explicitement.
        $this->forgetGuards();

        $this->withToken($current)->getJson(route('api.me'))->assertUnauthorized();

        $this->forgetGuards();

        $this->withToken($kept)->getJson(route('api.me'))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Commandes et devis
    |--------------------------------------------------------------------------
    */

    public function test_a_guest_can_order_and_gets_a_ready_made_whatsapp_message(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI', price: 10000);

        $response = $this->postJson(route('api.orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated();

        $order = Order::sole();

        $this->assertNull($order->user_id);
        $response->assertJsonPath('data.total', 23500)
            ->assertJsonPath('data.statusLabel', 'En attente');

        $message = $response->json('whatsApp.message');

        $this->assertStringContainsString($order->reference, $message);
        $this->assertStringContainsString('23 500 FCFA', $message);
    }

    public function test_an_order_placed_with_a_token_is_attached_to_the_account(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI');

        // La route est ouverte aux visiteurs : c'est le garde Sanctum qui
        // identifie quand même le porteur d'un jeton.
        Sanctum::actingAs($user);

        $this->postJson(route('api.orders.store'), [
            'customer_name' => $user->name,
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertSame($user->id, Order::sole()->user_id);
        $this->getJson(route('api.orders.index'))->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_the_server_applies_the_price_tier_and_ignores_client_prices(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI', price: 10000);
        $product->priceTiers()->create(['min_qty' => 10, 'max_qty' => null, 'price' => 8000]);

        $this->postJson(route('api.orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 10]],
        ])->assertCreated()->assertJsonPath('data.subtotal', 80000);
    }

    public function test_ordering_more_than_the_available_stock_is_refused(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI');
        $product->update(['stock' => 3]);

        $this->postJson(route('api.orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ])
            ->assertStatus(422)
            ->assertJsonPath('shortages.0.available', 3);

        $this->assertSame(0, Order::count());
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_another_customer_order_is_not_readable(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI');
        $owner = User::factory()->create();

        Sanctum::actingAs($owner);
        $this->postJson(route('api.orders.store'), [
            'customer_name' => 'Jean',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('api.orders.show', Order::sole()))->assertNotFound();
    }

    public function test_a_quote_returns_a_downloadable_pdf_link(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI');

        $url = $this->postJson(route('api.quotes.store'), [
            'customer_name' => 'BTP Central',
            'customer_phone' => '+237 690 00 00 00',
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.statusLabel', 'À traiter')
            ->json('data.pdfUrl');

        // Le jeton est dans l'URL : l'application télécharge sans session.
        $this->assertStringContainsString(Quote::sole()->token, $url);
        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    /*
    |--------------------------------------------------------------------------
    | Compte
    |--------------------------------------------------------------------------
    */

    public function test_favorites_toggle_on_and_off(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI');
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.favorites.toggle', $product))
            ->assertOk()
            ->assertJsonPath('favorite', true);

        $this->getJson(route('api.favorites'))->assertJsonCount(1, 'data');

        $this->postJson(route('api.favorites.toggle', $product))
            ->assertJsonPath('favorite', false);

        $this->getJson(route('api.favorites'))->assertJsonCount(0, 'data');
    }

    public function test_a_customer_without_a_confirmed_purchase_cannot_review_a_product(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI');
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.reviews.store', $product), [
            'rating' => 5,
            'body' => 'Excellent robinet, montage facile.',
        ])->assertUnprocessable();
    }

    public function test_a_customer_reviews_a_product_only_once(): void
    {
        $product = $this->makeProduct('robinet-kmei', 'Robinet KMEI');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = Order::create([
            'reference' => Order::generateReference(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '690000000',
            'status' => OrderStatus::Confirmed,
            'subtotal' => $product->price,
            'shipping' => 0,
            'total' => $product->price,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
            'line_total' => $product->price,
        ]);

        $this->postJson(route('api.reviews.store', $product), [
            'rating' => 5,
            'body' => 'Excellent robinet, montage facile.',
        ])->assertCreated();

        $this->postJson(route('api.reviews.store', $product), [
            'rating' => 1,
            'body' => 'Je change d’avis finalement.',
        ])->assertUnprocessable();

        $this->assertSame(5.0, (float) $product->fresh()->rating);
    }

    public function test_a_technician_request_is_recorded_and_listed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson(route('api.technician-requests.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'address' => 'Bastos, Yaoundé',
            'service' => 'Autre',
            'description' => 'Fuite sous l’évier depuis ce matin.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.statusLabel', 'En attente');

        $this->assertSame($user->id, TechnicianRequest::sole()->user_id);
        $this->getJson(route('api.technician-requests.index'))->assertJsonCount(1, 'data');
    }

    public function test_promotions_are_accepted_without_a_confirmed_address(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Sur mobile, la plupart des clients veulent les offres par
        // notification et rien d'autre : exiger un email les bloquerait.
        $this->putJson(route('api.notifications.update'), [
            'notify_order_updates' => true,
            'notify_promotions' => true,
        ])
            ->assertOk()
            ->assertJsonPath('notifications.promotions', true)
            ->assertJsonPath('notifications.verified', false);

        // L'adresse restant non confirmée, aucun email ne partira pour autant.
        $this->assertFalse($user->fresh()->acceptsEmail('promotions'));
    }

    /** Repart d'un garde vierge, comme le ferait une nouvelle requête HTTP. */
    private function forgetGuards(): void
    {
        $this->app['auth']->forgetGuards();
    }

    private function makeProduct(
        string $slug,
        string $name,
        int $price = 10000,
        string $description = 'Description de test.',
        bool $active = true,
    ): Product {
        return Product::create([
            'category_id' => $this->category->id,
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'image' => 'products/test.webp',
            'stock' => 50,
            'is_active' => $active,
        ]);
    }
}
