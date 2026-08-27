<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_an_administrator_creates_a_testimonial(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.testimonials.store'), [
                'name' => 'Marc Dupont',
                'role' => 'Propriétaire, Yaoundé',
                'text' => 'Livraison rapide et matériel de qualité.',
                'rating' => 5,
                'is_active' => true,
            ])
            ->assertRedirect();

        $testimonial = Testimonial::sole();

        $this->assertSame('Marc Dupont', $testimonial->name);
        $this->assertSame(5, $testimonial->rating);
        $this->assertTrue($testimonial->is_active);
    }

    public function test_a_new_testimonial_without_a_position_is_appended_last(): void
    {
        Testimonial::create([
            'name' => 'Première',
            'text' => 'Réponse.',
            'rating' => 5,
            'position' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post(route('admin.testimonials.store'), [
            'name' => 'Seconde',
            'text' => 'Réponse.',
            'rating' => 4,
            'is_active' => true,
        ]);

        $second = Testimonial::where('name', 'Seconde')->sole();

        $this->assertGreaterThan(5, $second->position);
    }

    public function test_an_administrator_updates_and_deletes_a_testimonial(): void
    {
        $testimonial = Testimonial::create([
            'name' => 'Ancien nom',
            'text' => 'Ancien texte.',
            'rating' => 3,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.testimonials.update', $testimonial), [
                'name' => 'Nouveau nom',
                'text' => 'Nouveau texte.',
                'rating' => 5,
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertSame('Nouveau nom', $testimonial->fresh()->name);
        $this->assertFalse($testimonial->fresh()->is_active);

        $this->actingAs($this->admin)
            ->delete(route('admin.testimonials.destroy', $testimonial))
            ->assertRedirect();

        $this->assertSame(0, Testimonial::count());
    }

    public function test_a_customer_cannot_manage_testimonials(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('admin.testimonials.index'))
            ->assertForbidden();
    }

    public function test_the_rating_must_stay_within_one_to_five(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.testimonials.store'), [
                'name' => 'Marc Dupont',
                'text' => 'Texte.',
                'rating' => 6,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('rating');

        $this->assertSame(0, Testimonial::count());
    }

    public function test_the_home_page_only_shows_active_testimonials_computed_with_initials(): void
    {
        $visible = Testimonial::create([
            'name' => 'Marc Dupont',
            'text' => 'Texte visible.',
            'rating' => 5,
            'position' => 0,
            'is_active' => true,
        ]);
        $hidden = Testimonial::create([
            'name' => 'Caché',
            'text' => 'Texte masqué.',
            'rating' => 5,
            'position' => 1,
            'is_active' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('testimonials', 1)
                ->where('testimonials.0.name', $visible->name)
                ->where('testimonials.0.initials', 'MD'));

        $this->assertDatabaseHas('testimonials', ['id' => $hidden->id]);
    }

    public function test_the_home_page_carries_no_testimonials_prop_content_when_none_exist(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('testimonials', 0));
    }
}
