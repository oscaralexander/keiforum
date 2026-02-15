<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ForumsTableSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/forums.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error("JSON file not found at: {$jsonPath}");
            return;
        }

        $json = file_get_contents($jsonPath);
        $forums = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Invalid JSON in forums.json: ' . json_last_error_msg());
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::table('forums')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        foreach ($forums as $forum) {
            DB::table('forums')->insert([
                'name' => $forum['name'],
                'slug' => $forum['slug'],
            ]);
        }

        $this->command->info('Seeded ' . count($forums) . ' forums.');
    }
}
