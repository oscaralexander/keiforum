<?php

use App\Enums\Gender;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('id')->constrained('areas');
            $table->string('bio')->nullable()->after('area_id');
            $table->date('birthdate')->nullable()->after('bio');
            $table->string('email_verification_token')->nullable()->after('email');
            $table->enum('gender', array_column(Gender::cases(), 'value'))->nullable()->after('email_verified_at');
            $table->boolean('has_avatar')->default(false)->after('gender');
            $table->string('username')->unique()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropUnique(['username']);
            $table->dropColumn(['area_id', 'bio', 'birthdate', 'email_verification_token', 'gender', 'has_avatar', 'username']);
        });
    }
};
