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
        Schema::create('topic_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('topics')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('last_read_post_id')->nullable()->constrained('posts')->onDelete('set null');
            $table->boolean('is_subscribed')->default(false);
            $table->foreignId('last_notified_post_id')->nullable()->constrained('posts')->onDelete('set null');
            $table->timestamps();
            $table->unique(['topic_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_user');
    }
};
