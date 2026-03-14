<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_form', function (Blueprint $table) {
            $table->foreignId('field_group_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('field_form', function (Blueprint $table) {
            $table->dropForeign(['field_group_id']);
            $table->dropColumn('field_group_id');
        });
    }
};
