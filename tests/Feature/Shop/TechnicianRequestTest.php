<?php

namespace Tests\Feature\Shop;

use App\Enums\TechnicianRequestStatus;
use App\Models\TechnicianRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_request_a_technician(): void
    {
        $this->post(route('technician-requests.store'), $this->payload())
            ->assertRedirect();

        $request = TechnicianRequest::sole();

        $this->assertNull($request->user_id);
        $this->assertNull($request->technician_id);
        $this->assertSame(TechnicianRequestStatus::Pending, $request->status);
        $this->assertStringStartsWith('INT-', $request->reference);
    }

    public function test_a_signed_in_customer_finds_the_request_in_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('technician-requests.store'), $this->payload())
            ->assertRedirect();

        $this->assertSame($user->id, TechnicianRequest::sole()->user_id);

        $this->actingAs($user)
            ->get(route('account.technician-requests'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('requests.data', 1));
    }

    public function test_the_service_must_be_one_of_the_configured_options(): void
    {
        $this->post(route('technician-requests.store'), [
            ...$this->payload(),
            'service' => 'Réparation de fusée',
        ])->assertSessionHasErrors('service');

        $this->assertSame(0, TechnicianRequest::count());
    }

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '+237 690 00 00 00',
            'address' => 'Bastos, Yaoundé',
            'service' => 'Dépannage urgence',
            'description' => 'Fuite sous l’évier de la cuisine.',
        ];
    }
}
