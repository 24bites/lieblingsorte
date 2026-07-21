<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Wandern', 'slug' => 'wandern', 'icon' => 'mountain-snow', 'description' => 'Wanderwege, Steige und Almtouren für jedes Niveau.'],
            ['name' => 'Natur & Landschaft', 'slug' => 'natur-landschaft', 'icon' => 'leaf', 'description' => 'Beeindruckende Naturschauspiele, Schluchten und Aussichtspunkte.'],
            ['name' => 'Kulinarik', 'slug' => 'kulinarik', 'icon' => 'utensils', 'description' => 'Regionale Spezialitäten, Almhütten und Genussorte.'],
            ['name' => 'Kultur & Geschichte', 'slug' => 'kultur-geschichte', 'icon' => 'landmark', 'description' => 'Burgen, Schlösser, Museen und historische Altstädte.'],
            ['name' => 'Seen & Baden', 'slug' => 'seen-baden', 'icon' => 'waves', 'description' => 'Badeseen, Wasserfälle und erfrischende Ausflugsziele.'],
            ['name' => 'Aussicht & Panorama', 'slug' => 'aussicht-panorama', 'icon' => 'eye', 'description' => 'Gipfel, Aussichtsplattformen und Panoramawege.'],
            ['name' => 'Familie & Ausflug', 'slug' => 'familie-ausflug', 'icon' => 'users', 'description' => 'Ideale Ziele für einen Familienausflug mit Kindern.'],
            ['name' => 'Indoor & Schlechtwetter', 'slug' => 'indoor-schlechtwetter', 'icon' => 'home', 'description' => 'Museen, Erlebniswelten und Ideen für Regentage.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
