<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_topic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->foreignId('topic_id')->constrained('topics')->onDelete('cascade');           
            $table->unique(['area_id', 'topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_topic');
    }
};
