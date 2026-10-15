<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('start_odometer');
            $table->unsignedInteger('end_odometer')->nullable();
            $table->decimal('fuel_liters', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('returned_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE vehicle_usages ADD CONSTRAINT chk_usage_odometer
            CHECK (end_odometer IS NULL OR end_odometer >= start_odometer)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_usages');
    }
};
