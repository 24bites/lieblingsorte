<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TravelTipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'region_id' => ['required', 'exists:regions,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'short_description' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'duration' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', Rule::in(['leicht', 'mittel', 'anspruchsvoll'])],
            'price_information' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'parking_information' => ['nullable', 'string', 'max:255'],
            'arrival_information' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'family_friendly' => ['nullable', 'boolean'],
            'stroller_friendly' => ['nullable', 'boolean'],
            'dog_friendly' => ['nullable', 'boolean'],
            'indoor' => ['nullable', 'boolean'],
            'free_entry' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'best_season' => ['nullable', 'string', 'max:255'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['nullable', 'string', 'max:255'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['exists:labels,id'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=400,min_height=300'],
            'gallery_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=400,min_height=300'],
        ];
    }
}
