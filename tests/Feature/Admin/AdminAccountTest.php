<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_the_administrator_account(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', config('shop.admin.email'))->sole();

        $this->assertTrue($admin->isAdmin());
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(
            Hash::check(config('shop.admin.password'), $admin->password),
        );
    }

    public function test_running_the_seeder_twice_does_not_reset_the_password(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', config('shop.admin.email'))->sole();
        $admin->update(['password' => Hash::make('un-mot-de-passe-choisi')]);

        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(
            Hash::check('un-mot-de-passe-choisi', $admin->fresh()->password),
        );
        $this->assertSame(1, User::where('email', config('shop.admin.email'))->count());
    }

    public function test_the_administrator_signs_in_with_the_login_form(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->post(route('login.store'), [
            'email' => config('shop.admin.email'),
            'password' => config('shop.admin.password'),
        ])->assertRedirect();

        $this->assertTrue(auth()->check());
        $this->assertTrue(auth()->user()->isAdmin());

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_public_registration_can_never_create_an_administrator(): void
    {
        // Même en poussant un rôle dans la requête, l'inscription reste cliente.
        $this->post(route('register.store'), [
            'name' => 'Pirate',
            'email' => 'pirate@example.test',
            'password' => 'Motdepasse!2026',
            'password_confirmation' => 'Motdepasse!2026',
            'role' => UserRole::Admin->value,
        ]);

        $user = User::where('email', 'pirate@example.test')->sole();

        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertFalse($user->isAdmin());

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }
}
