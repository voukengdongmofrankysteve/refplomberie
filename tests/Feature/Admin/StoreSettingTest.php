<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        StoreSetting::forgetCurrent();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Réf. Plomberie — Douala',
            'address' => 'Rue Joss, Douala, Cameroun',
            'phone' => '+237 690 00 00 00',
            'whatsapp' => '237690000000',
            'email' => 'douala@refplomberie.cm',
            'hours' => 'Lun–Ven : 8h – 17h',
            'latitude' => 4.0511,
            'longitude' => 9.7679,
            'map_zoom' => 17,
            'meta_title' => 'Plomberie à Douala',
            'meta_description' => 'Matériel de plomberie livré à Douala.',
            'meta_keywords' => 'plomberie, douala',
            'google_site_verification' => 'jeton-search-console',
            'is_indexable' => true,
            'facebook_url' => 'https://facebook.com/refplomberie',
            'instagram_url' => null,
            'linkedin_url' => null,
            ...$overrides,
        ];
    }

    public function test_an_administrator_opens_the_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/settings/index')
                ->has('settings')
                ->has('mapEmbedUrl')
                ->has('seoUrls'));
    }

    public function test_a_customer_cannot_reach_the_settings_page(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->put(route('admin.settings.update'), $this->payload())
            ->assertForbidden();
    }

    public function test_the_map_location_can_be_changed(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), $this->payload())
            ->assertRedirect();

        StoreSetting::forgetCurrent();
        $store = StoreSetting::current();

        $this->assertSame(4.0511, $store->latitude);
        $this->assertSame(9.7679, $store->longitude);
        $this->assertSame(17, $store->map_zoom);

        // Les coordonnées GPS priment sur l'adresse dans l'URL de la carte.
        $this->assertStringContainsString('4.0511%2C9.7679', $store->mapEmbedUrl());
        $this->assertStringContainsString('z=17', $store->mapEmbedUrl());
    }

    public function test_the_address_is_used_when_no_coordinates_are_given(): void
    {
        $this->actingAs($this->admin)->put(
            route('admin.settings.update'),
            $this->payload(['latitude' => null, 'longitude' => null]),
        );

        StoreSetting::forgetCurrent();
        $store = StoreSetting::current();

        $this->assertSame('Rue Joss, Douala, Cameroun', $store->mapQuery());
        $this->assertStringContainsString('Rue%20Joss', $store->mapEmbedUrl());
    }

    public function test_the_new_map_url_reaches_the_storefront(): void
    {
        $this->actingAs($this->admin)->put(
            route('admin.settings.update'),
            $this->payload(),
        );

        StoreSetting::forgetCurrent();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'store.mapEmbedUrl',
                StoreSetting::current()->mapEmbedUrl(),
            ));
    }

    public function test_seo_fields_reach_the_rendered_page(): void
    {
        $this->actingAs($this->admin)->put(
            route('admin.settings.update'),
            $this->payload(),
        );

        StoreSetting::forgetCurrent();

        $html = $this->get(route('home'))->getContent();

        $this->assertStringContainsString('Matériel de plomberie livré à Douala.', $html);
        $this->assertStringContainsString('jeton-search-console', $html);
        $this->assertStringContainsString('Plomberie à Douala', $html);
    }

    public function test_the_share_image_is_optimised_on_upload(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            ...$this->payload(),
            'og_image_file' => UploadedFile::fake()->image('partage.jpg', 1200, 630),
        ]);

        StoreSetting::forgetCurrent();
        $store = StoreSetting::current();

        $this->assertNotNull($store->og_image);
        $this->assertStringEndsWith('.webp', $store->og_image);
        Storage::disk('public')->assertExists($store->og_image);
    }

    public function test_the_whatsapp_number_must_be_digits_only(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), $this->payload([
                'whatsapp' => '+237 690 00 00 00',
            ]))
            ->assertSessionHasErrors('whatsapp');
    }

    public function test_out_of_range_coordinates_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), $this->payload([
                'latitude' => 120,
                'longitude' => -400,
            ]))
            ->assertSessionHasErrors(['latitude', 'longitude']);
    }
}
