<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->string('name_en');
            $table->string('name_fr');
            $table->string('name_nl');
            $table->integer('position')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_groups');
    }
};
