<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'client-de-test.apps.googleusercontent.com',
            'services.google.client_secret' => 'secret-de-test',
            'services.google.redirect' => '/auth/google/retour',
        ]);
    }

    public function test_a_visitor_is_sent_to_google(): void
    {
        Socialite::fake('google');

        $this->get(route('auth.google'))->assertRedirect();
    }

    public function test_the_button_is_hidden_when_google_is_not_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('googleEnabled', false));

        // Et la route elle-même refuse poliment plutôt que d'exploser.
        $this->get(route('auth.google'))->assertRedirect(route('login'));
    }

    public function test_an_unknown_visitor_gets_an_account_and_is_signed_in(): void
    {
        $this->fakeGoogleUser();

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'email' => 'client@example.com',
            'google_id' => 'google-123',
            'name' => 'Jean Mbarga',
        ]);

        $user = User::sole();

        $this->assertTrue(Auth::check());
        // Google a vérifié l'adresse : pas de second contrôle par email.
        $this->assertNotNull($user->email_verified_at);
        // Aucun mot de passe n'est inventé pour lui.
        $this->assertNull($user->password);
    }

    public function test_a_returning_visitor_reuses_the_same_account(): void
    {
        $this->fakeGoogleUser();
        $this->get(route('auth.google.callback'));

        Auth::logout();
        $this->fakeGoogleUser();
        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertSame(1, User::count());
    }

    public function test_a_verified_address_links_google_to_an_existing_account(): void
    {
        $existing = User::factory()->create([
            'email' => 'client@example.com',
            'password' => Hash::make('un-mot-de-passe'),
        ]);

        $this->fakeGoogleUser();
        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertSame(1, User::count());
        $this->assertSame('google-123', $existing->fresh()->google_id);
        // Le mot de passe existant survit : le client garde ses deux moyens
        // d'entrer.
        $this->assertNotNull($existing->fresh()->password);
    }

    public function test_an_administrator_cannot_sign_in_with_google(): void
    {
        User::factory()->create([
            'email' => 'client@example.com',
            'role' => UserRole::Admin,
        ]);

        $this->fakeGoogleUser();
        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

        $this->assertGuest();
        // Le compte administrateur ne s'est pas vu attribuer d'identité
        // Google au passage : la prochaine tentative sera refusée elle aussi.
        $this->assertNull(User::sole()->google_id);
    }

    public function test_an_administrator_who_already_linked_google_is_still_refused(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $admin->forceFill(['google_id' => 'google-123'])->save();

        $this->fakeGoogleUser();
        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_an_unverified_address_never_takes_over_an_account(): void
    {
        $existing = User::factory()->create(['email' => 'client@example.com']);

        $this->fakeGoogleUser(verified: false);
        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

        $this->assertNull($existing->fresh()->google_id);
        $this->assertGuest();
    }

    public function test_a_google_account_cannot_be_entered_with_a_password(): void
    {
        $this->fakeGoogleUser();
        $this->get(route('auth.google.callback'));
        Auth::logout();

        // Sans le garde-fou, la comparaison contre un mot de passe absent
        // lèverait une erreur de type au lieu de refuser la connexion.
        $this->post(route('login.store'), [
            'email' => 'client@example.com',
            'password' => 'peu-importe',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_the_mobile_application_signs_in_with_a_verified_id_token(): void
    {
        config(['services.google.mobile_client_ids' => ['mobile-de-test.apps.googleusercontent.com']]);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response([
                'iss' => 'https://accounts.google.com',
                'aud' => 'mobile-de-test.apps.googleusercontent.com',
                'sub' => 'google-456',
                'email' => 'mobile@example.com',
                'email_verified' => 'true',
                'name' => 'Awa Nkeng',
                'picture' => 'https://example.test/photo.jpg',
            ]),
        ]);

        $this->postJson(route('api.login.google'), ['id_token' => 'jeton-signé'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $this->assertDatabaseHas('users', [
            'email' => 'mobile@example.com',
            'google_id' => 'google-456',
        ]);
    }

    public function test_the_mobile_application_refuses_an_administrator_account(): void
    {
        config(['services.google.mobile_client_ids' => ['mobile-de-test.apps.googleusercontent.com']]);

        User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::Admin,
        ]);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response([
                'iss' => 'https://accounts.google.com',
                'aud' => 'mobile-de-test.apps.googleusercontent.com',
                'sub' => 'google-789',
                'email' => 'admin@example.com',
                'email_verified' => 'true',
            ]),
        ]);

        $this->postJson(route('api.login.google'), ['id_token' => 'jeton-signé'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('id_token');
    }

    public function test_a_token_issued_to_another_application_is_refused(): void
    {
        config(['services.google.mobile_client_ids' => ['mobile-de-test.apps.googleusercontent.com']]);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response([
                'iss' => 'https://accounts.google.com',
                // Jeton parfaitement valide, mais délivré à quelqu'un d'autre.
                'aud' => 'application-tierce.apps.googleusercontent.com',
                'sub' => 'google-789',
                'email' => 'intrus@example.com',
                'email_verified' => 'true',
            ]),
        ]);

        $this->postJson(route('api.login.google'), ['id_token' => 'jeton-volé'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('id_token');

        $this->assertSame(0, User::count());
    }

    /**
     * Prépare la réponse que Socialite recevra de Google.
     */
    private function fakeGoogleUser(bool $verified = true): void
    {
        $user = SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Jean Mbarga',
            'email' => 'client@example.com',
            'avatar' => 'https://example.test/avatar.jpg',
        ]);

        // `email_verified` vit dans la charge utile brute, que Socialite
        // conserve telle quelle sous `user`.
        $user->user = [...$user->user, 'email_verified' => $verified];

        Socialite::fake('google', $user);
    }
}
