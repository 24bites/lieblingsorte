<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            LabelSeeder::class,
            SettingSeeder::class,
            SuedtirolSeeder::class,
            AllgaeuSeeder::class,
            TrierSeeder::class,
            LuxemburgSeeder::class,
            DuesseldorfSeeder::class,
            LissabonSeeder::class,
            FaroSeeder::class,
            MauritiusSeeder::class,
        ]);
    }
}
