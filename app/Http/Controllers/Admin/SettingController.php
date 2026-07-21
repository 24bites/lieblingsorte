<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AiCronSettings;
use App\Support\OpenAiConfig;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private array $keys = [
        'site_name', 'site_claim', 'site_description', 'contact_email',
        'ga_measurement_id', 'ad_slot_header', 'ad_slot_sidebar', 'ad_slot_in_content',
    ];

    public function edit()
    {
        $settings = collect($this->keys)->mapWithKeys(fn ($key) => [$key => Setting::get($key, '')]);

        // The stored key is encrypted at rest and never sent back to the browser —
        // only whether one exists, and a short preview built from a decrypt-only helper.
        $openaiKeyPreview = OpenAiConfig::preview();
        $openaiKeyConfigured = $openaiKeyPreview !== null;
        $aiCronsEnabled = AiCronSettings::enabled();

        return view('admin.settings.edit', compact('settings', 'openaiKeyConfigured', 'openaiKeyPreview', 'aiCronsEnabled'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_claim' => ['required', 'string', 'max:255'],
            'site_description' => ['required', 'string', 'max:500'],
            'contact_email' => ['required', 'email', 'max:255'],
            'ga_measurement_id' => ['nullable', 'string', 'max:32', 'regex:/^G-[A-Z0-9]+$/'],
            'ad_slot_header' => ['nullable', 'string', 'max:5000'],
            'ad_slot_sidebar' => ['nullable', 'string', 'max:5000'],
            'ad_slot_in_content' => ['nullable', 'string', 'max:5000'],
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'remove_openai_api_key' => ['nullable', 'boolean'],
            'ai_crons_enabled' => ['nullable', 'boolean'],
        ]);

        // A blank field means "leave unchanged" — the field is never pre-filled with the real secret,
        // so submitting the form without touching it must not wipe out an already-stored key.
        if ($request->boolean('remove_openai_api_key')) {
            OpenAiConfig::clear();
        } elseif (filled($data['openai_api_key'] ?? null)) {
            OpenAiConfig::store(trim($data['openai_api_key']));
        }
        unset($data['openai_api_key'], $data['remove_openai_api_key'], $data['ai_crons_enabled']);

        AiCronSettings::setEnabled($request->boolean('ai_crons_enabled'));

        foreach ($data as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        return redirect()->route('admin.settings.edit')->with('status', 'Einstellungen wurden gespeichert.');
    }
}
