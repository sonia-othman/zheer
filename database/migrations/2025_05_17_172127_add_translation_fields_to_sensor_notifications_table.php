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
        Schema::table('sensor_notifications', function (Blueprint $table) {
             $table->string('translation_key')->nullable();
        $table->json('translation_params')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_notifications', function (Blueprint $table) {
            $table->dropColumn('translation_key');
        $table->dropColumn('translation_params');
        });
    }
};
