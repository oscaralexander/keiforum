<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('headlines', function (Blueprint $table) {
            $table->id();
            $table->string('guid')->unique();
            $table->string('title');
            $table->string('link');
            $table->string('image_url')->nullable();
            $table->dateTime('pub_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('headlines');
    }
};
