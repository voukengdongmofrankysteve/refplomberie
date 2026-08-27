<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string}>
     */
    public static function adminRoutes(): array
    {
        return [
            ['admin.dashboard'],
            ['admin.products.index'],
            ['admin.orders.index'],
            ['admin.technicians.index'],
            ['admin.technician-requests.index'],
            ['admin.messages.index'],
            ['admin.customers.index'],
            ['admin.audit-log.index'],
            ['admin.suppliers.index'],
            ['admin.purchase-orders.index'],
            ['admin.accounting.index'],
            ['admin.testimonials.index'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_an_administrator_can_open_every_back_office_page(
        string $route,
    ): void {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route($route))->assertOk();
    }

    #[DataProvider('adminRoutes')]
    public function test_a_customer_is_denied_access_to_the_back_office(
        string $route,
    ): void {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)->get(route($route))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
