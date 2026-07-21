<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'site_name' => 'Lieblingsorte',
            'site_claim' => 'Die besten Reisetipps für Städte und Regionen',
            'site_description' => 'Handverlesene Lieblingsorte, echte Geheimtipps und besondere Erlebnisse.',
            'contact_email' => 'hallo@lieblingsorte.test',
            'ga_measurement_id' => '',
            'ad_slot_header' => '',
            'ad_slot_sidebar' => '',
            'ad_slot_in_content' => '',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
