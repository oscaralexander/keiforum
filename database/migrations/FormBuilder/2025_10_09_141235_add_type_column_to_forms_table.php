<?php

use App\Enums\FormType;
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
        Schema::table('forms', function (Blueprint $table) {
            $table->enum('type', [
                FormType::BASELINE_INSPECTION->value,
                FormType::INCOMING_INSPECTION->value,
                FormType::DELIVERY_REPORT->value,
            ])->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
