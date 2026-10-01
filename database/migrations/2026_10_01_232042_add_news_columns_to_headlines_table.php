<?php

use App\Models\Headline;
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
        Schema::table('headlines', function (Blueprint $table) {
            $table->unsignedBigInteger('article_id')->nullable()->unique()->after('guid');
            $table->text('description')->nullable()->after('title');
            $table->string('verdict')->nullable()->after('pub_date');
            $table->text('verdict_reason')->nullable()->after('verdict');
            $table->foreignId('topic_id')->nullable()->after('verdict_reason')->constrained('topics')->nullOnDelete();
        });

        Headline::query()->each(function (Headline $headline): void {
            $headline->update(['article_id' => Headline::articleIdFromLink($headline->link)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('headlines', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
            $table->dropUnique(['article_id']);
            $table->dropColumn(['article_id', 'description', 'verdict', 'verdict_reason', 'topic_id']);
        });
    }
};
