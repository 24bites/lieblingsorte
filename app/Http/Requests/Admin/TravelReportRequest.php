<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TravelReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Drops FAQ rows the admin added but never filled in (both fields
     * blank) before validation runs, so an empty trailing repeater row
     * never blocks saving the rest of the form.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('faq')) {
            return;
        }

        $faq = collect($this->input('faq', []))
            ->filter(fn ($pair) => filled($pair['question'] ?? null) || filled($pair['answer'] ?? null))
            ->values()
            ->all();

        $this->merge(['faq' => $faq]);
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
            'og_description' => ['nullable', 'string', 'max:500'],
            'faq' => ['nullable', 'array'],
            'faq.*.question' => ['required', 'string', 'max:255'],
            'faq.*.answer' => ['required', 'string', 'max:2000'],
            'is_published' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:25600', 'dimensions:min_width=400,min_height=300'],
            'gallery_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:25600', 'dimensions:min_width=400,min_height=300'],
        ];
    }
}
