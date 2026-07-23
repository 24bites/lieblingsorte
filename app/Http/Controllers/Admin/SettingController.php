<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AiCronSettings;
use App\Support\OpenAiConfig;
use App\Support\PinterestConfig;
use App\Support\ResendConfig;
use App\Support\TelegramConfig;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private array $keys = [
        'site_name', 'site_claim', 'site_description', 'contact_email',
        'ga_measurement_id', 'pinterest_tag_id', 'ad_slot_header', 'ad_slot_sidebar', 'ad_slot_in_content',
    ];

    public function edit()
    {
        $settings = collect($this->keys)->mapWithKeys(fn ($key) => [$key => Setting::get($key, '')]);

        // The stored key is encrypted at rest and never sent back to the browser —
        // only whether one exists, and a short preview built from a decrypt-only helper.
        $openaiKeyPreview = OpenAiConfig::preview();
        $openaiKeyConfigured = $openaiKeyPreview !== null;

        $telegramTokenPreview = TelegramConfig::preview();
        $telegramConfigured = TelegramConfig::isConfigured();
        $telegramChatId = TelegramConfig::chatId();

        $resendKeyPreview = ResendConfig::preview();
        $resendConfigured = $resendKeyPreview !== null;

        $newsletterFooterVisible = Setting::get('newsletter_footer_visible', '1') === '1';

        $aiCrons = [
            'images_ai_replace' => [
                'enabled' => AiCronSettings::enabled(AiCronSettings::IMAGES_AI_REPLACE),
                'interval' => AiCronSettings::intervalMinutes(AiCronSettings::IMAGES_AI_REPLACE),
            ],
            'regions_auto_generate' => [
                'enabled' => AiCronSettings::enabled(AiCronSettings::REGIONS_AUTO_GENERATE),
                'interval' => AiCronSettings::intervalMinutes(AiCronSettings::REGIONS_AUTO_GENERATE),
            ],
            'regions_complete_content' => [
                'enabled' => AiCronSettings::enabled(AiCronSettings::REGIONS_COMPLETE_CONTENT),
                'interval' => AiCronSettings::intervalMinutes(AiCronSettings::REGIONS_COMPLETE_CONTENT),
            ],
        ];

        $pinterestCaptionsEnabled = AiCronSettings::enabled(AiCronSettings::PINTEREST_CAPTIONS);

        $pinterestAppId = PinterestConfig::appId();
        $pinterestAppConfigured = PinterestConfig::hasAppCredentials();
        $pinterestAppSecretPreview = PinterestConfig::appSecretPreview();
        $pinterestConnected = PinterestConfig::isConfigured();
        $pinterestAccountUsername = PinterestConfig::accountUsername();

        return view('admin.settings.edit', compact(
            'settings', 'openaiKeyConfigured', 'openaiKeyPreview', 'aiCrons',
            'telegramConfigured', 'telegramTokenPreview', 'telegramChatId',
            'resendConfigured', 'resendKeyPreview', 'newsletterFooterVisible',
            'pinterestCaptionsEnabled',
            'pinterestAppId', 'pinterestAppConfigured', 'pinterestAppSecretPreview',
            'pinterestConnected', 'pinterestAccountUsername',
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_claim' => ['required', 'string', 'max:255'],
            'site_description' => ['required', 'string', 'max:500'],
            'contact_email' => ['required', 'email', 'max:255'],
            'ga_measurement_id' => ['nullable', 'string', 'max:32', 'regex:/^G-[A-Z0-9]+$/'],
            'pinterest_tag_id' => ['nullable', 'string', 'max:32', 'regex:/^[0-9]+$/'],
            'ad_slot_header' => ['nullable', 'string', 'max:5000'],
            'ad_slot_sidebar' => ['nullable', 'string', 'max:5000'],
            'ad_slot_in_content' => ['nullable', 'string', 'max:5000'],
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'remove_openai_api_key' => ['nullable', 'boolean'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'remove_telegram' => ['nullable', 'boolean'],
            'resend_api_key' => ['nullable', 'string', 'max:255'],
            'remove_resend_api_key' => ['nullable', 'boolean'],
            'pinterest_app_id' => ['nullable', 'string', 'max:255'],
            'pinterest_app_secret' => ['nullable', 'string', 'max:255'],
            'remove_pinterest_app' => ['nullable', 'boolean'],
            'newsletter_footer_visible' => ['nullable', 'boolean'],
            'images_ai_replace_enabled' => ['nullable', 'boolean'],
            'images_ai_replace_interval' => ['required', 'integer', 'min:1', 'max:59'],
            'regions_auto_generate_enabled' => ['nullable', 'boolean'],
            'regions_auto_generate_interval' => ['required', 'integer', 'min:1', 'max:59'],
            'regions_complete_content_enabled' => ['nullable', 'boolean'],
            'regions_complete_content_interval' => ['required', 'integer', 'min:1', 'max:59'],
            'pinterest_captions_enabled' => ['nullable', 'boolean'],
        ]);

        // A blank field means "leave unchanged" — the field is never pre-filled with the real secret,
        // so submitting the form without touching it must not wipe out an already-stored key.
        if ($request->boolean('remove_openai_api_key')) {
            OpenAiConfig::clear();
        } elseif (filled($data['openai_api_key'] ?? null)) {
            OpenAiConfig::store(trim($data['openai_api_key']));
        }

        if ($request->boolean('remove_telegram')) {
            TelegramConfig::clear();
        } elseif (filled($data['telegram_bot_token'] ?? null)) {
            TelegramConfig::store(trim($data['telegram_bot_token']), trim($data['telegram_chat_id'] ?? ''));
        } elseif (filled($data['telegram_chat_id'] ?? null) && TelegramConfig::botToken() !== null) {
            // Chat id changed without re-entering the token.
            TelegramConfig::store(TelegramConfig::botToken(), trim($data['telegram_chat_id']));
        }

        if ($request->boolean('remove_resend_api_key')) {
            ResendConfig::clear();
        } elseif (filled($data['resend_api_key'] ?? null)) {
            ResendConfig::store(trim($data['resend_api_key']));
        }

        if ($request->boolean('remove_pinterest_app')) {
            PinterestConfig::clearAppCredentials();
        } elseif (filled($data['pinterest_app_id'] ?? null) && filled($data['pinterest_app_secret'] ?? null)) {
            PinterestConfig::storeAppCredentials(trim($data['pinterest_app_id']), trim($data['pinterest_app_secret']));
        }

        unset(
            $data['openai_api_key'], $data['remove_openai_api_key'],
            $data['telegram_bot_token'], $data['telegram_chat_id'], $data['remove_telegram'],
            $data['resend_api_key'], $data['remove_resend_api_key'],
            $data['pinterest_app_id'], $data['pinterest_app_secret'], $data['remove_pinterest_app'],
            $data['newsletter_footer_visible'],
            $data['images_ai_replace_enabled'], $data['images_ai_replace_interval'],
            $data['regions_auto_generate_enabled'], $data['regions_auto_generate_interval'],
            $data['regions_complete_content_enabled'], $data['regions_complete_content_interval'],
            $data['pinterest_captions_enabled'],
        );

        Setting::set('newsletter_footer_visible', $request->boolean('newsletter_footer_visible') ? '1' : '0');

        AiCronSettings::setEnabled(AiCronSettings::IMAGES_AI_REPLACE, $request->boolean('images_ai_replace_enabled'));
        AiCronSettings::setIntervalMinutes(AiCronSettings::IMAGES_AI_REPLACE, (int) $request->input('images_ai_replace_interval'));
        AiCronSettings::setEnabled(AiCronSettings::REGIONS_AUTO_GENERATE, $request->boolean('regions_auto_generate_enabled'));
        AiCronSettings::setIntervalMinutes(AiCronSettings::REGIONS_AUTO_GENERATE, (int) $request->input('regions_auto_generate_interval'));
        AiCronSettings::setEnabled(AiCronSettings::REGIONS_COMPLETE_CONTENT, $request->boolean('regions_complete_content_enabled'));
        AiCronSettings::setIntervalMinutes(AiCronSettings::REGIONS_COMPLETE_CONTENT, (int) $request->input('regions_complete_content_interval'));
        AiCronSettings::setEnabled(AiCronSettings::PINTEREST_CAPTIONS, $request->boolean('pinterest_captions_enabled'));

        foreach ($data as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        return redirect()->route('admin.settings.edit')->with('status', 'Einstellungen wurden gespeichert.');
    }
}
