<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hidden forums hold topics that live elsewhere, like the replies to
     * activities, and are left out of forum listings.
     */
    public function up(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('is_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
};
