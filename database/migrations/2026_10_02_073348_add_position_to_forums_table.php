<?php

use App\Models\Forum;
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
        Schema::table('forums', function (Blueprint $table) {
            $table->unsignedSmallInteger('position')->default(0)->after('is_locked');
        });

        $order = array_flip(['algemeen', config('news.forum_slug'), 'hulp-en-handel']);

        Forum::query()
            ->orderBy('id')
            ->get()
            ->sortBy(fn (Forum $forum) => $order[$forum->slug] ?? count($order) + $forum->id)
            ->values()
            ->each(fn (Forum $forum, int $index) => $forum->forceFill(['position' => $index + 1])->save());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
