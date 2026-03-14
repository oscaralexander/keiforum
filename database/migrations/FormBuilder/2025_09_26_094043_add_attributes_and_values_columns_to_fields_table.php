<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            // Add attributes column after id column
            $table->text('attrs')->nullable()->after('id');
            
            // Add values columns after label_nl column
            $table->text('values_en')->nullable()->after('label_nl');
            $table->text('values_fr')->nullable()->after('values_en');
            $table->text('values_nl')->nullable()->after('values_fr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->dropColumn(['attrs', 'values_en', 'values_fr', 'values_nl']);
        });
    }
};
