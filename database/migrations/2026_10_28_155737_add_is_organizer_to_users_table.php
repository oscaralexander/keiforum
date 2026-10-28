<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Users who already placed activities keep the
     * right to do so.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_organizer')->default(false)->after('is_admin');
        });

        DB::table('users')
            ->whereIn('id', DB::table('activities')->select('user_id'))
            ->update(['is_organizer' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_organizer');
        });
    }
};
