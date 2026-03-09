<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained('posts');
            $table->foreignId('topic_id')->constrained('topics');
            $table->foreignId('user_id')->constrained('users');
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by_id')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};



















