<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->text('comment_en');
            $table->text('comment_fr');
            $table->text('comment_nl');
            $table->integer('position')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_comments');
    }
};
