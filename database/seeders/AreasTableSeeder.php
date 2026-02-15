<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AreasTableSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/areas.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error("JSON file not found at: {$jsonPath}");
            return;
        }

        $json = file_get_contents($jsonPath);
        $areas = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Invalid JSON in areas.json: ' . json_last_error_msg());
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::table('areas')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        foreach ($areas as $area) {
            DB::table('areas')->insert([
                'name' => $area['name'],
                'slug' => $area['slug'],
            ]);
        }

        $this->command->info('Seeded ' . count($areas) . ' areas.');
    }
}
