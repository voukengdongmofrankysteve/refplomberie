<?php

namespace Tests\Feature\Admin;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Mail\OrderStatusMail;
use App\Mail\PromotionMail;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_a_status_change_emails_a_subscribed_customer(): void
    {
        $order = $this->makeOrder($this->subscriber());

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Shipped->value,
                'note' => '',
            ])
            ->assertRedirect();

        Mail::assertSent(OrderStatusMail::class, fn ($mail): bool => $mail->order->is($order));
    }

    public function test_nothing_is_sent_to_a_customer_who_did_not_opt_in(): void
    {
        $order = $this->makeOrder(User::factory()->create());

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Shipped->value,
                'note' => '',
            ]);

        Mail::assertNothingSent();
    }

    public function test_nothing_is_sent_for_a_guest_order(): void
    {
        $order = $this->makeOrder(null);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Confirmed->value,
                'note' => '',
            ]);

        Mail::assertNothingSent();
    }

    public function test_editing_a_note_without_changing_the_status_sends_nothing(): void
    {
        $order = $this->makeOrder($this->subscriber());

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), [
                'status' => $order->status->value,
                'note' => 'Rappeler le client demain.',
            ]);

        Mail::assertNothingSent();
    }

    public function test_the_order_carries_a_prefilled_whatsapp_link(): void
    {
        $order = $this->makeOrder(null, phone: '690 00 00 00');

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertInertia(function ($page) use ($order) {
                $url = $page->toArray()['props']['order']['whatsAppUrl'];

                // Numéro local complété de l'indicatif, message pré-rempli.
                $this->assertStringStartsWith('https://wa.me/237690000000?text=', $url);
                $this->assertStringContainsString(
                    rawurlencode($order->reference),
                    $url,
                );
            });
    }

    public function test_a_campaign_only_reaches_customers_subscribed_to_promotions(): void
    {
        $this->subscriber(promotions: true);
        $this->subscriber(promotions: false);
        User::factory()->create();

        $campaign = Campaign::create([
            'subject' => 'Offre de lancement',
            'body' => 'Bonne nouvelle !',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.campaigns.send', $campaign))
            ->assertRedirect();

        Mail::assertSent(PromotionMail::class, 1);

        $campaign->refresh();

        $this->assertSame(CampaignStatus::Sent, $campaign->status);
        $this->assertSame(1, $campaign->recipients_count);
    }

    public function test_a_campaign_cannot_be_sent_twice(): void
    {
        $this->subscriber(promotions: true);

        $campaign = Campaign::create([
            'subject' => 'Offre',
            'body' => 'Texte',
            'status' => CampaignStatus::Sent,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.campaigns.send', $campaign))
            ->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_a_sent_campaign_can_no_longer_be_edited(): void
    {
        $campaign = Campaign::create([
            'subject' => 'Offre',
            'body' => 'Texte original',
            'status' => CampaignStatus::Sent,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.campaigns.update', $campaign), [
                'subject' => 'Offre réécrite',
                'body' => 'Texte réécrit',
            ]);

        $this->assertSame('Texte original', $campaign->fresh()->body);
    }

    public function test_the_campaign_body_becomes_paragraphs(): void
    {
        $campaign = Campaign::create([
            'subject' => 'Offre',
            'body' => "Premier paragraphe.\n\n\nSecond paragraphe.\n",
        ]);

        $this->assertSame(
            ['Premier paragraphe.', 'Second paragraphe.'],
            $campaign->paragraphs(),
        );
    }

    /** Client ayant confirmé son adresse et coché les thèmes voulus. */
    private function subscriber(bool $promotions = false): User
    {
        return User::factory()->create([
            'notification_email' => 'client'.uniqid().'@example.com',
            'notification_email_verified_at' => now(),
            'notify_order_updates' => true,
            'notify_promotions' => $promotions,
        ]);
    }

    private function makeOrder(?User $user, string $phone = '+237 690 00 00 00'): Order
    {
        $category = Category::create(['slug' => 'outils', 'label' => 'Outils']);
        $product = Product::create([
            'category_id' => $category->id,
            'slug' => 'produit-test',
            'name' => 'Produit test',
            'description' => 'Description de test.',
            'price' => 10000,
            'image' => 'https://example.test/image.jpg',
            'stock' => 50,
            'is_active' => true,
        ]);

        $order = Order::create([
            'reference' => Order::generateReference(),
            'user_id' => $user?->id,
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => $phone,
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
