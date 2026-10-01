<?php

use App\Models\TopicUser;
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
        Schema::table('topic_user', function (Blueprint $table) {
            $table->boolean('is_subscribed')->nullable()->default(null)->change();
        });

        TopicUser::query()->where('is_subscribed', false)->update(['is_subscribed' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        TopicUser::query()->whereNull('is_subscribed')->update(['is_subscribed' => false]);

        Schema::table('topic_user', function (Blueprint $table) {
            $table->boolean('is_subscribed')->default(false)->change();
        });
    }
};
