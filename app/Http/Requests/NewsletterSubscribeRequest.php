<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewsletterSubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'consent' => ['required', 'accepted'],
            'homepage' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'consent.required' => 'Bitte bestätige die Werbeerlaubnis, um dich anzumelden.',
            'consent.accepted' => 'Bitte bestätige die Werbeerlaubnis, um dich anzumelden.',
        ];
    }
}
