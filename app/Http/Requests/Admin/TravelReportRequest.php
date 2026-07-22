<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TravelReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'excerpt' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'author_name' => ['required', 'string', 'max:255'],
            'author_bio' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=400,min_height=300'],
            'gallery_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=400,min_height=300'],
        ];
    }
}
