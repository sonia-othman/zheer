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
        Schema::create('sensor_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('device_id');
            $table->string('type'); 
            $table->string('message');
            $table->string('translation_key')->nullable();
            $table->json('translation_params')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_notifications');
        
    }
};
