<?php

namespace Database\Seeders;

use App\Models\Label;
use Illuminate\Database\Seeder;

class LabelSeeder extends Seeder
{
    public function run(): void
    {
        $labels = [
            ['name' => 'Familie', 'slug' => 'familie', 'color' => '#2f6b4f'],
            ['name' => 'Geheimtipp', 'slug' => 'geheimtipp', 'color' => '#b8863b'],
            ['name' => 'Natur', 'slug' => 'natur', 'color' => '#3f7d5c'],
            ['name' => 'Aktiv', 'slug' => 'aktiv', 'color' => '#c1542c'],
            ['name' => 'Kulinarik', 'slug' => 'kulinarik', 'color' => '#a13d3d'],
            ['name' => 'Kultur', 'slug' => 'kultur', 'color' => '#5b4636'],
            ['name' => 'Wandern', 'slug' => 'wandern', 'color' => '#4f7942'],
            ['name' => 'Seen', 'slug' => 'seen', 'color' => '#2f6690'],
            ['name' => 'Schlechtwetter', 'slug' => 'schlechtwetter', 'color' => '#6b7280'],
            ['name' => 'Sommer', 'slug' => 'sommer', 'color' => '#d9a441'],
            ['name' => 'Winter', 'slug' => 'winter', 'color' => '#6699cc'],
        ];

        foreach ($labels as $label) {
            Label::updateOrCreate(['slug' => $label['slug']], $label);
        }
    }
}
