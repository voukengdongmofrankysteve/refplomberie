<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductWarrantyBadge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'slug' => [
                'required',
                'string',
                'max:180',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'description' => ['required', 'string', 'max:2000'],
            // Facultative : un champ vide efface simplement la vidéo.
            'video_url' => ['nullable', 'url', 'max:500'],
            'price' => ['required', 'integer', 'min:0'],
            'old_price' => ['nullable', 'integer', 'min:0', 'gt:price'],
            'badge' => ['nullable', 'string', 'max:40'],
            'warranty_badges' => ['array', 'max:'.count(ProductWarrantyBadge::cases())],
            'warranty_badges.*' => [Rule::enum(ProductWarrantyBadge::class)],
            'stock' => ['required', 'integer', 'min:0'],
            // Absent du formulaire : la valeur en place — ou celle par défaut
            // en base — est conservée.
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],

            // Image principale : soit un nouveau fichier, soit celle déjà en place.
            'image_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'image' => ['required_without:image_file', 'nullable', 'string', 'max:500'],

            // Galerie : les entrées conservées, puis les nouveaux fichiers.
            'images' => ['array', 'max:10'],
            'images.*' => ['required', 'string', 'max:500'],
            'gallery_files' => ['array', 'max:10'],
            'gallery_files.*' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],

            'price_tiers' => ['array', 'max:10'],
            'price_tiers.*.min_qty' => ['required', 'integer', 'min:1'],
            'price_tiers.*.max_qty' => ['nullable', 'integer', 'gte:price_tiers.*.min_qty'],
            'price_tiers.*.price' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'catégorie',
            'name' => 'nom',
            'slug' => 'identifiant URL',
            'description' => 'description',
            'video_url' => 'vidéo tutoriel',
            'price' => 'prix',
            'old_price' => 'ancien prix',
            'image' => 'image principale',
            'image_file' => 'image principale',
            'gallery_files' => 'galerie',
            'stock' => 'stock',
            'low_stock_threshold' => 'seuil d’alerte',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required_without' => 'Téléversez une image principale pour ce produit.',
            'image_file.max' => "L'image principale ne doit pas dépasser 8 Mo.",
            'gallery_files.*.max' => 'Chaque image de la galerie doit faire moins de 8 Mo.',
        ];
    }
}
