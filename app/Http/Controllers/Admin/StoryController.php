<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoryResource;
use App\Models\Story;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StoryController extends Controller
{
    /** Vidéos : 25 Mo, largement au-dessus d'un statut de quelques secondes. */
    private const MAX_VIDEO_KB = 25600;

    public function __construct(private readonly ProductImageService $images) {}

    public function index(): Response
    {
        $stories = Story::orderBy('position')->orderByDesc('id')->get();

        return Inertia::render('admin/stories/index', [
            'stories' => StoryResource::collection($stories)->resolve(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, creating: true);

        $story = new Story;
        $this->fill($story, $request, $data);
        $story->position = $data['position'] ?? (Story::max('position') + 1);
        $story->save();

        return back()->with('success', 'Statut publié.');
    }

    public function update(Request $request, Story $story): RedirectResponse
    {
        $data = $this->validated($request, creating: false);

        $this->fill($story, $request, $data);
        $story->save();

        return back()->with('success', 'Statut mis à jour.');
    }

    public function destroy(Story $story): RedirectResponse
    {
        $this->images->delete($story->media_path);
        $this->images->delete($story->poster_path);
        $story->delete();

        return back()->with('success', 'Statut supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'caption' => ['nullable', 'string', 'max:255'],
            'media_type' => ['required', Rule::in([Story::TYPE_IMAGE, Story::TYPE_VIDEO])],
            'link_url' => ['nullable', 'string', 'max:255'],
            'link_label' => ['nullable', 'string', 'max:60'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],

            // À la création le média est obligatoire ; en modification il n'est
            // remplacé que si un nouveau fichier est fourni.
            'media_image' => [
                $creating && $request->input('media_type') === Story::TYPE_IMAGE ? 'required' : 'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:8192',
            ],
            'media_video' => [
                $creating && $request->input('media_type') === Story::TYPE_VIDEO ? 'required' : 'nullable',
                'file',
                'mimetypes:video/mp4,video/quicktime,video/webm',
                'max:'.self::MAX_VIDEO_KB,
            ],
            'poster' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ], attributes: [
            'title' => 'titre',
            'caption' => 'légende',
            'media_type' => 'type de média',
            'media_image' => 'image',
            'media_video' => 'vidéo',
            'poster' => 'vignette',
        ], messages: [
            'media_video.mimetypes' => 'La vidéo doit être au format MP4, MOV ou WebM.',
            'media_video.max' => 'La vidéo ne doit pas dépasser 25 Mo.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(Story $story, Request $request, array $data): void
    {
        $story->fill([
            'title' => $data['title'],
            'caption' => $data['caption'] ?? null,
            'media_type' => $data['media_type'],
            'link_url' => $data['link_url'] ?? null,
            'link_label' => $data['link_label'] ?? null,
            'is_active' => $data['is_active'],
        ]);

        if (isset($data['position'])) {
            $story->position = $data['position'];
        }

        if ($request->hasFile('media_image')) {
            $previous = $story->media_path;
            // Les images passent par le même traitement que les fiches produit :
            // redimensionnement, WebP et filigrane de la marque.
            $story->media_path = $this->images->store($request->file('media_image'));
            $this->images->delete($previous);
        }

        if ($request->hasFile('media_video')) {
            $previous = $story->media_path;
            // Une vidéo ne peut pas être filigranée par GD : elle est stockée
            // telle quelle, et c'est sa vignette qui porte la marque.
            $story->media_path = $request->file('media_video')->store('stories', 'public');
            $this->images->delete($previous);
        }

        if ($request->hasFile('poster')) {
            $previous = $story->poster_path;
            $story->poster_path = $this->images->store($request->file('poster'));
            $this->images->delete($previous);
        }
    }
}
