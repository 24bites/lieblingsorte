<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lieblingsorte.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'redaktion@lieblingsorte.test'],
            [
                'name' => 'Redaktion',
                'password' => Hash::make('editor123'),
                'role' => 'editor',
                'email_verified_at' => now(),
            ]
        );
    }
}
