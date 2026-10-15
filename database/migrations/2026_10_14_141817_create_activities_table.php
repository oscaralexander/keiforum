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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20)->index();
            $table->string('title');
            $table->string('slug');
            $table->text('description');
            $table->date('starts_on');
            $table->date('ends_on')->index();
            $table->time('starts_at');
            $table->time('ends_at')->nullable();
            $table->string('location_name');
            $table->string('location_address')->nullable();
            $table->string('location_place_id')->nullable();
            $table->decimal('location_latitude', 10, 7)->nullable();
            $table->decimal('location_longitude', 10, 7)->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->string('poster_path')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'starts_on', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
