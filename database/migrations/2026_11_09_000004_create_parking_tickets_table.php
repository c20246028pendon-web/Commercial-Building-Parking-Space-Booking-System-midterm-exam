<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->restrictOnDelete();
            $table->foreignId('parking_slot_id')->constrained()->restrictOnDelete();
            $table->dateTime('entry_time');
            $table->dateTime('exit_time')->nullable();
            $table->unsignedInteger('total_hours')->nullable();
            $table->decimal('hourly_rate', 10, 2);
            $table->timestamps();
            $table->index(['parking_slot_id', 'exit_time']);
            $table->index('entry_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_tickets');
    }
};
