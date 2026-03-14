<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('name_en')->unique();
            $table->string('name_nl')->unique();
            $table->string('name_fr')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('forms');
    }
}; 