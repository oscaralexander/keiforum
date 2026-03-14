<?php

use App\Models\FieldGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_comments', function (Blueprint $table) {
            $table->foreignIdFor(FieldGroup::class)
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('form_comments', function (Blueprint $table) {
            $table->dropForeign(['field_group_id']);
            $table->dropColumn('field_group_id');
        });
    }
};


