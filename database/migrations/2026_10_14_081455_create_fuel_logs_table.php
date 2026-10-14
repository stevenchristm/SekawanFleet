<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
    {
        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('filled_at');
            $table->decimal('liters', 8, 2);
            $table->decimal('cost', 14, 2);
            $table->unsignedInteger('odometer')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'filled_at']);
        });

        DB::statement('ALTER TABLE fuel_logs ADD CONSTRAINT chk_fuel_positive
            CHECK (liters > 0 AND cost >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_logs');
    }
};
