<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_zone_id')->constrained()->restrictOnDelete();
            $table->string('slot_code', 20);
            $table->string('status', 20)->default('Vacant');
            $table->timestamps();
            $table->unique(['parking_zone_id', 'slot_code']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_slots');
    }
};
