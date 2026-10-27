<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('activities')->where('category', 'live-muziek')->update(['category' => 'muziek']);
        DB::table('activities')->where('category', 'film')->update(['category' => 'anders']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('activities')->whereIn('category', ['dans', 'kunst'])->update(['category' => 'anders']);
        DB::table('activities')->where('category', 'muziek')->update(['category' => 'live-muziek']);
    }
};
