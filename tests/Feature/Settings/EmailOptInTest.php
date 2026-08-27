<?php

namespace Tests\Feature\Settings;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class EmailOptInTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->user = User::factory()->create();
    }

    public function test_notifications_are_off_until_the_address_is_confirmed(): void
    {
        $this->assertFalse($this->user->hasVerifiedNotificationEmail());
        $this->assertFalse($this->user->acceptsEmail('orders'));
        $this->assertFalse($this->user->acceptsEmail('promotions'));
    }

    public function test_requesting_a_code_sends_it_and_leaves_the_address_unconfirmed(): void
    {
        $this->actingAs($this->user)
            ->post(route('notifications.code'), ['email' => 'Client@Example.com'])
            ->assertRedirect();

        Mail::assertSent(EmailVerificationCodeMail::class);

        $this->user->refresh();

        // L'adresse est mémorisée mais ne vaut pas consentement.
        $this->assertSame('client@example.com', $this->user->notification_email);
        $this->assertNull($this->user->notification_email_verified_at);
        $this->assertFalse($this->user->acceptsEmail('orders'));
    }

    public function test_the_code_is_never_stored_in_clear_text(): void
    {
        $this->actingAs($this->user)
            ->post(route('notifications.code'), ['email' => 'client@example.com']);

        $record = $this->user->emailVerificationCodes()->sole();

        $this->assertNotSame('', $record->code_hash);
        $this->assertStringNotContainsString(' ', $record->code_hash);
        $this->assertTrue(Hash::check($this->lastCode(), $record->code_hash));
    }

    public function test_the_right_code_activates_order_updates_only(): void
    {
        $this->actingAs($this->user)
            ->post(route('notifications.code'), ['email' => 'client@example.com']);

        $this->actingAs($this->user)
            ->post(route('notifications.confirm'), ['code' => $this->lastCode()])
            ->assertRedirect();

        $this->user->refresh();

        $this->assertTrue($this->user->hasVerifiedNotificationEmail());
        $this->assertTrue($this->user->acceptsEmail('orders'));
        // La publicité reste un choix distinct, jamais coché d'office.
        $this->assertFalse($this->user->acceptsEmail('promotions'));
        $this->assertSame(0, $this->user->emailVerificationCodes()->count());
    }

    public function test_a_wrong_code_is_refused_and_counted(): void
    {
        $this->actingAs($this->user)
            ->post(route('notifications.code'), ['email' => 'client@example.com']);

        $this->actingAs($this->user)
            ->post(route('notifications.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse($this->user->fresh()->hasVerifiedNotificationEmail());
        $this->assertSame(1, $this->user->emailVerificationCodes()->sole()->attempts);
    }

    public function test_a_code_is_dropped_after_too_many_attempts(): void
    {
        $this->actingAs($this->user)
            ->post(route('notifications.code'), ['email' => 'client@example.com']);

        $code = $this->lastCode();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->user)
                ->post(route('notifications.confirm'), ['code' => '000000']);
        }

        // Même le bon code ne passe plus : il faut en redemander un.
        $this->actingAs($this->user)
            ->post(route('notifications.confirm'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertFalse($this->user->fresh()->hasVerifiedNotificationEmail());
    }

    public function test_an_expired_code_is_refused(): void
    {
        $this->actingAs($this->user)
            ->post(route('notifications.code'), ['email' => 'client@example.com']);

        $code = $this->lastCode();
        $this->user->emailVerificationCodes()->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($this->user)
            ->post(route('notifications.confirm'), ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_changing_the_address_revokes_the_previous_confirmation(): void
    {
        $this->confirmAddress('client@example.com');

        $this->actingAs($this->user)
            ->post(route('notifications.code'), ['email' => 'autre@example.com']);

        $this->user->refresh();

        $this->assertFalse($this->user->hasVerifiedNotificationEmail());
        $this->assertFalse($this->user->acceptsEmail('orders'));
    }

    public function test_preferences_cannot_be_set_without_a_confirmed_address(): void
    {
        $this->actingAs($this->user)
            ->put(route('notifications.update'), [
                'notify_order_updates' => true,
                'notify_promotions' => true,
            ])
            ->assertRedirect();

        $this->assertFalse($this->user->fresh()->acceptsEmail('promotions'));
    }

    public function test_promotions_can_be_enabled_once_confirmed(): void
    {
        $this->confirmAddress('client@example.com');

        $this->actingAs($this->user)
            ->put(route('notifications.update'), [
                'notify_order_updates' => true,
                'notify_promotions' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($this->user->fresh()->acceptsEmail('promotions'));
    }

    public function test_disabling_forgets_the_address_and_all_consents(): void
    {
        $this->confirmAddress('client@example.com');

        $this->actingAs($this->user)
            ->delete(route('notifications.destroy'))
            ->assertRedirect();

        $this->user->refresh();

        $this->assertNull($this->user->notification_email);
        $this->assertFalse($this->user->acceptsEmail('orders'));
    }

    public function test_a_failed_delivery_leaves_the_account_untouched(): void
    {
        // Serveur mail injoignable : le client doit voir une erreur claire,
        // pas une page 500, et surtout pas un écran réclamant un code qui
        // n'arrivera jamais.
        Mail::shouldReceive('to->send')
            ->andThrow(new RuntimeException('Serveur mail injoignable pour le moment.'));

        $this->actingAs($this->user)
            ->post(route('notifications.code'), ['email' => 'client@example.com'])
            ->assertSessionHasErrors('email');

        $this->user->refresh();

        $this->assertNull($this->user->notification_email);
        $this->assertSame(0, $this->user->emailVerificationCodes()->count());
    }

    /** Code du dernier email envoyé, lu dans le mailable intercepté. */
    private function lastCode(): string
    {
        $code = null;

        Mail::assertSent(EmailVerificationCodeMail::class, function ($mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        return (string) $code;
    }

    private function confirmAddress(string $email): void
    {
        $this->actingAs($this->user)
            ->post(route('notifications.code'), ['email' => $email]);

        $this->actingAs($this->user)
            ->post(route('notifications.confirm'), ['code' => $this->lastCode()]);

        $this->user->refresh();
    }
}
