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
        Schema::create('sensor_data', function (Blueprint $table) {
            $table->id();
            $table->string('device_id');
            $table->boolean('status')->default(false)->comment('0=closed, 1=open');
            $table->json('raw_payload')->nullable();
            $table->float('temperature')->nullable();
            $table->integer('count')->nullable()->comment('Door/window opening count');
            $table->float('battery')->nullable();
            $table->timestamps();
            $table->index('device_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_data');
    }
};
