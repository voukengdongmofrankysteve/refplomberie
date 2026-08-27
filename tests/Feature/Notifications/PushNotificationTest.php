<?php

namespace Tests\Feature\Notifications;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\DeviceToken;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\CampaignNotification;
use App\Notifications\OrderStatusNotification;
use App\Services\CustomerNotifier;
use App\Services\FirebaseMessaging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Cache::flush();
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications en base : toujours actives
    |--------------------------------------------------------------------------
    */

    public function test_a_status_change_is_always_recorded_in_the_database(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user);

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Shipped->value,
                'note' => '',
            ])
            ->assertRedirect();

        $notification = $user->notifications()->sole();

        $this->assertSame('order', $notification->data['type']);
        $this->assertSame($order->reference, $notification->data['reference']);
        $this->assertNull($notification->read_at);
    }

    public function test_the_database_journal_reaches_even_a_customer_refusing_everything(): void
    {
        // Ni email confirmé, ni push : le journal arrive quand même. C'est le
        // seul canal que le client ne peut pas couper.
        $user = User::factory()->create(['notify_push' => false]);
        $order = $this->makeOrder($user);

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Confirmed->value,
                'note' => '',
            ]);

        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_a_customer_reads_and_clears_the_journal(): void
    {
        $user = User::factory()->create();
        $user->notify(new OrderStatusNotification($this->makeOrder($user)));

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('data.0.type', 'order');

        $this->actingAs($user)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread', 0);
    }

    public function test_the_journal_is_private_to_its_owner(): void
    {
        $owner = User::factory()->create();
        $owner->notify(new OrderStatusNotification($this->makeOrder($owner)));

        $this->actingAs(User::factory()->create())
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /*
    |--------------------------------------------------------------------------
    | Appareils
    |--------------------------------------------------------------------------
    */

    public function test_a_device_registers_and_is_reused_on_the_next_launch(): void
    {
        $user = User::factory()->create();

        foreach ([1, 2] as $ignored) {
            $this->actingAs($user)
                ->postJson(route('notifications.device.register'), [
                    'token' => 'jeton-fcm-abc',
                    'platform' => 'android',
                    'device_name' => 'Téléphone Android',
                ])
                ->assertOk()
                ->assertJsonPath('registered', true);
        }

        // Firebase renvoie le même jeton au lancement suivant : il ne doit pas
        // être dupliqué.
        $this->assertSame(1, $user->deviceTokens()->count());
    }

    public function test_a_device_changing_account_follows_its_new_owner(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)->postJson(route('notifications.device.register'), [
            'token' => 'jeton-partage',
            'platform' => 'android',
        ]);

        $this->actingAs($second)->postJson(route('notifications.device.register'), [
            'token' => 'jeton-partage',
            'platform' => 'android',
        ]);

        // Sans réattribution, l'ancien compte recevrait les notifications du
        // nouveau sur le même téléphone.
        $this->assertSame(0, $first->deviceTokens()->count());
        $this->assertSame(1, $second->deviceTokens()->count());
    }

    public function test_a_device_can_be_forgotten(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('notifications.device.register'), [
            'token' => 'jeton-fcm-abc',
            'platform' => 'web',
        ]);

        $this->actingAs($user)
            ->deleteJson(route('notifications.device.forget'), ['token' => 'jeton-fcm-abc'])
            ->assertOk();

        $this->assertSame(0, $user->deviceTokens()->count());
    }

    public function test_an_unknown_platform_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('notifications.device.register'), [
                'token' => 'jeton',
                'platform' => 'nintendo',
            ])
            ->assertUnprocessable();
    }

    /*
    |--------------------------------------------------------------------------
    | Envoi Firebase
    |--------------------------------------------------------------------------
    */

    public function test_nothing_is_sent_when_firebase_is_not_configured(): void
    {
        config(['services.firebase.project_id' => null]);

        $messaging = app(FirebaseMessaging::class);

        $this->assertFalse($messaging->isConfigured());
        $this->assertSame(0, $messaging->sendToTokens(['jeton'], 'Titre', 'Corps'));
    }

    public function test_a_push_is_posted_to_the_v1_endpoint(): void
    {
        $this->fakeFirebase();
        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/x/messages/1']),
        ]);

        $sent = app(FirebaseMessaging::class)
            ->sendToTokens(['jeton-a', 'jeton-b'], 'Commande expédiée', 'En route !');

        $this->assertSame(2, $sent);

        Http::assertSent(function ($request): bool {
            $message = $request->data()['message'];

            // L'ancienne API à clé serveur est fermée depuis juin 2024 : seul
            // le point d'entrée v1 fonctionne encore.
            return str_contains($request->url(), '/v1/projects/refplomberie/messages:send')
                && $message['notification']['title'] === 'Commande expédiée'
                && $message['android']['priority'] === 'high';
        });
    }

    public function test_a_revoked_token_is_deleted_rather_than_retried(): void
    {
        $this->fakeFirebase();

        $user = User::factory()->create();
        DeviceToken::register($user, 'jeton-mort', 'android');

        Http::fake([
            'fcm.googleapis.com/*' => Http::response([
                'error' => ['details' => [['errorCode' => 'UNREGISTERED']]],
            ], 404),
        ]);

        $sent = app(FirebaseMessaging::class)->sendToUser($user->id, 'Titre', 'Corps');

        $this->assertSame(0, $sent);
        // Le garder ferait échouer chaque envoi suivant et fausserait les
        // compteurs de la campagne.
        $this->assertSame(0, $user->deviceTokens()->count());
    }

    public function test_a_customer_without_a_device_receives_no_push(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = $this->makeOrder($user);

        $user->notify(new OrderStatusNotification($order));

        Notification::assertSentTo($user, OrderStatusNotification::class);
        $this->assertFalse($user->acceptsPush());
    }

    public function test_push_is_refused_when_the_customer_turned_it_off(): void
    {
        $user = User::factory()->create(['notify_push' => false]);
        DeviceToken::register($user, 'jeton', 'android');

        $this->assertFalse($user->acceptsPush());
    }

    /*
    |--------------------------------------------------------------------------
    | Campagnes
    |--------------------------------------------------------------------------
    */

    public function test_promotions_can_be_accepted_without_any_email_address(): void
    {
        // Le cas qui bloquait : un client qui ne veut que des notifications
        // devait d'abord confirmer une adresse email pour cocher « offres ».
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('notifications.update'), [
                'notify_order_updates' => true,
                'notify_promotions' => true,
                'notify_push' => true,
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertTrue($user->notify_promotions);
        // L'adresse reste non confirmée : aucun email ne partira pour autant.
        $this->assertFalse($user->hasVerifiedNotificationEmail());
        $this->assertFalse($user->acceptsEmail('promotions'));
    }

    public function test_a_device_alone_makes_a_customer_reachable_by_push(): void
    {
        $user = User::factory()->create(['notify_promotions' => true]);

        $this->assertFalse($user->acceptsPush());

        DeviceToken::register($user, 'jeton', 'android');

        $this->assertTrue($user->fresh()->acceptsPush());
        $this->assertSame(1, app(CustomerNotifier::class)->pushAudience());
    }

    public function test_a_push_campaign_records_a_notification_for_every_subscriber(): void
    {
        $this->fakeFirebase();
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'ok'])]);

        $subscriber = User::factory()->create(['notify_promotions' => true]);
        DeviceToken::register($subscriber, 'jeton-actif', 'android');

        $silent = User::factory()->create(['notify_promotions' => false]);

        $campaign = Campaign::create([
            'subject' => 'Offre de lancement',
            'body' => 'Bonne nouvelle !',
            'channels' => ['push'],
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.campaigns.send', $campaign))
            ->assertRedirect();

        $this->assertSame(1, $subscriber->notifications()->count());
        $this->assertSame(0, $silent->notifications()->count());
        $this->assertSame(1, $campaign->fresh()->pushed_count);
        $this->assertSame(CampaignStatus::Sent, $campaign->fresh()->status);
    }

    public function test_a_campaign_limited_to_email_sends_no_push(): void
    {
        Notification::fake();

        $subscriber = User::factory()->create([
            'notify_promotions' => true,
            'notification_email' => 'client@example.com',
            'notification_email_verified_at' => now(),
        ]);

        $campaign = Campaign::create([
            'subject' => 'Offre',
            'body' => 'Texte',
            'channels' => ['email'],
        ]);

        $this->actingAs($this->admin())->post(route('admin.campaigns.send', $campaign));

        Notification::assertNotSentTo($subscriber, CampaignNotification::class);
    }

    public function test_an_older_campaign_without_channels_still_goes_by_email(): void
    {
        $campaign = Campaign::create(['subject' => 'Offre', 'body' => 'Texte']);

        $this->assertTrue($campaign->usesChannel('email'));
        $this->assertFalse($campaign->usesChannel('push'));
    }

    /*
    |--------------------------------------------------------------------------
    | Aides
    |--------------------------------------------------------------------------
    */

    private function fakeFirebase(): void
    {
        // Compte de service factice : seul compte le fait qu'il soit lisible.
        $path = storage_path('framework/testing/compte-service.json');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0o777, true);
        }

        file_put_contents($path, json_encode([
            'type' => 'service_account',
            'project_id' => 'refplomberie',
            'client_email' => 'test@refplomberie.iam.gserviceaccount.com',
            'private_key' => 'factice',
        ]));

        config([
            'services.firebase.project_id' => 'refplomberie',
            'services.firebase.credentials' => $path,
        ]);

        // Le jeton OAuth est mis en cache : on l'y place pour ne pas avoir à
        // signer un vrai JWT dans un test.
        Cache::put('fcm-access-token', 'jeton-oauth-de-test', 60);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function makeOrder(User $user): Order
    {
        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);
        $product = Product::create([
            'category_id' => $category->id,
            'slug' => 'produit-test',
            'name' => 'Produit test',
            'description' => 'Description de test.',
            'price' => 10000,
            'image' => 'products/test.webp',
            'stock' => 50,
            'is_active' => true,
        ]);

        $order = Order::create([
            'reference' => Order::generateReference(),
            'user_id' => $user->id,
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'status' => OrderStatus::Pending,
            'subtotal' => 10000,
            'shipping' => 3500,
            'total' => 13500,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 10000,
            'quantity' => 1,
            'line_total' => 10000,
        ]);

        return $order;
    }
}
