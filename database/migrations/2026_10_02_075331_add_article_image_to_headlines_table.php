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
        Schema::table('headlines', function (Blueprint $table) {
            $table->string('article_image_url', 2048)->nullable()->after('image_url');
            $table->string('article_image_caption')->nullable()->after('article_image_url');
            $table->string('article_image_credit')->nullable()->after('article_image_caption');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('headlines', function (Blueprint $table) {
            $table->dropColumn(['article_image_url', 'article_image_caption', 'article_image_credit']);
        });
    }
};
