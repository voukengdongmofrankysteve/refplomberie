<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_an_administrator_creates_a_question(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.faqs.store'), [
                'question' => 'Livrez-vous en dehors de Yaoundé ?',
                'answer' => 'Oui, partout au Cameroun.',
                'category' => 'Livraison',
                'is_active' => true,
            ])
            ->assertRedirect();

        $faq = Faq::sole();

        $this->assertSame('Livrez-vous en dehors de Yaoundé ?', $faq->question);
        $this->assertSame('Livraison', $faq->category);
        $this->assertTrue($faq->is_active);
    }

    public function test_a_new_question_without_a_position_is_appended_last(): void
    {
        Faq::create([
            'question' => 'Première',
            'answer' => 'Réponse.',
            'position' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post(route('admin.faqs.store'), [
            'question' => 'Seconde',
            'answer' => 'Réponse.',
            'is_active' => true,
        ]);

        $second = Faq::where('question', 'Seconde')->sole();

        $this->assertGreaterThan(5, $second->position);
    }

    public function test_an_administrator_updates_and_deletes_a_question(): void
    {
        $faq = Faq::create([
            'question' => 'Ancienne question',
            'answer' => 'Ancienne réponse.',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.faqs.update', $faq), [
                'question' => 'Nouvelle question',
                'answer' => 'Nouvelle réponse.',
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertSame('Nouvelle question', $faq->fresh()->question);
        $this->assertFalse($faq->fresh()->is_active);

        $this->actingAs($this->admin)
            ->delete(route('admin.faqs.destroy', $faq))
            ->assertRedirect();

        $this->assertSame(0, Faq::count());
    }

    public function test_a_customer_cannot_manage_faqs(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('admin.faqs.index'))
            ->assertForbidden();
    }

    public function test_only_active_faqs_reach_the_home_page(): void
    {
        $visible = Faq::create([
            'question' => 'Question visible',
            'answer' => 'Réponse visible.',
            'position' => 0,
            'is_active' => true,
        ]);
        $hidden = Faq::create([
            'question' => 'Question masquée',
            'answer' => 'Réponse masquée.',
            'position' => 1,
            'is_active' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('faqs', 1)
                ->where('faqs.0.question', $visible->question));

        $this->assertDatabaseHas('faqs', ['id' => $hidden->id]);
    }

    public function test_the_mobile_api_lists_only_active_faqs_in_order(): void
    {
        Faq::create(['question' => 'B', 'answer' => 'Réponse B.', 'position' => 2, 'is_active' => true]);
        Faq::create(['question' => 'A', 'answer' => 'Réponse A.', 'position' => 1, 'is_active' => true]);
        Faq::create(['question' => 'Masquée', 'answer' => 'x', 'position' => 0, 'is_active' => false]);

        $this->getJson(route('api.faqs'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.question', 'A')
            ->assertJsonPath('data.1.question', 'B');
    }
}
