<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'email' => 'mail@keiforum.nl',
            'email_verified_at' => now(),
            'name' => 'Keiforum',
            'username' => 'keiforum',
            'password' => Hash::make('L0d3st@r'),
        ]);

        $this->call([
            AreasTableSeeder::class,
            ForumsTableSeeder::class,
        ]);
    }
}
