<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_an_administrator_publishes_an_image_story(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.stories.store'), [
                'title' => 'Arrivage éviers',
                'caption' => 'Double bac inox en stock.',
                'media_type' => 'image',
                'is_active' => true,
                'media_image' => UploadedFile::fake()->image('story.jpg', 1080, 1920),
            ])
            ->assertRedirect();

        $story = Story::sole();

        $this->assertSame('Arrivage éviers', $story->title);
        // L'image est traitée comme une photo produit : WebP et filigrane.
        $this->assertStringEndsWith('.webp', $story->media_path);
        Storage::disk('public')->assertExists($story->media_path);
    }

    public function test_a_video_story_keeps_its_file_and_uses_a_poster(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.stories.store'), [
                'title' => 'Chantier en cours',
                'media_type' => 'video',
                'is_active' => true,
                'media_video' => UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4'),
                'poster' => UploadedFile::fake()->image('poster.jpg', 1080, 1920),
            ])
            ->assertRedirect();

        $story = Story::sole();

        $this->assertTrue($story->isVideo());
        $this->assertStringStartsWith('stories/', $story->media_path);
        $this->assertStringEndsWith('.webp', (string) $story->poster_path);
        // La vignette du fil s'appuie sur le poster, pas sur la vidéo.
        $this->assertStringContainsString($story->poster_path, (string) $story->thumbnailUrl());
    }

    public function test_a_story_requires_its_media_on_creation(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.stories.store'), [
                'title' => 'Sans média',
                'media_type' => 'image',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('media_image');

        $this->assertSame(0, Story::count());
    }

    public function test_an_oversized_video_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.stories.store'), [
                'title' => 'Clip trop lourd',
                'media_type' => 'video',
                'is_active' => true,
                'media_video' => UploadedFile::fake()->create('clip.mp4', 40000, 'video/mp4'),
            ])
            ->assertSessionHasErrors('media_video');
    }

    public function test_only_active_stories_reach_the_home_page(): void
    {
        $visible = $this->makeStory('Visible', active: true, position: 0);
        $hidden = $this->makeStory('Masqué', active: false, position: 1);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('stories', 1)
                ->where('stories.0.title', $visible->title));

        $this->assertDatabaseHas('stories', ['id' => $hidden->id]);
    }

    public function test_deleting_a_story_removes_its_media(): void
    {
        $this->actingAs($this->admin)->post(route('admin.stories.store'), [
            'title' => 'À supprimer',
            'media_type' => 'image',
            'is_active' => true,
            'media_image' => UploadedFile::fake()->image('story.jpg', 1080, 1920),
        ]);

        $story = Story::sole();
        $path = $story->media_path;

        $this->actingAs($this->admin)
            ->delete(route('admin.stories.destroy', $story))
            ->assertRedirect();

        $this->assertSame(0, Story::count());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_customer_cannot_manage_stories(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('admin.stories.index'))
            ->assertForbidden();
    }

    private function makeStory(string $title, bool $active, int $position): Story
    {
        return Story::create([
            'title' => $title,
            'media_type' => Story::TYPE_IMAGE,
            'media_path' => 'products/exemple.webp',
            'position' => $position,
            'is_active' => $active,
        ]);
    }
}
