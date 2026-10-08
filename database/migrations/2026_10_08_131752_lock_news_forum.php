<?php

use App\Models\Forum;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Only admins may start topics in the news forum; the news user posts
     * its topics through the headline jobs instead.
     */
    public function up(): void
    {
        Forum::query()->where('slug', config('news.forum_slug'))->update(['is_locked' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Forum::query()->where('slug', config('news.forum_slug'))->update(['is_locked' => false]);
    }
};
