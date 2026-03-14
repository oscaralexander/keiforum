<?php

use App\Enums\FieldType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->string('description_en')->nullable();
            $table->string('description_fr')->nullable();
            $table->string('description_nl')->nullable();
            $table->string('label_en');
            $table->string('label_fr');
            $table->string('label_nl');
            $table->enum('type', array_column(FieldType::cases(), 'value'));
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fields');
    }
};