<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('rental_company_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('plate_number', 20)->unique();
            $table->string('brand_model', 100);
            $table->string('type', 20);                       // passenger | cargo
            $table->string('ownership', 20);                  // owned | rented
            $table->unsignedSmallInteger('capacity')->nullable(); // kursi / ton
            $table->string('status', 20)->default('available');
            $table->timestamps();

            $table->index(['location_id', 'status']);
        });

        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT chk_vehicle_type
            CHECK (type IN ('passenger','cargo'))");
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT chk_vehicle_ownership
            CHECK (ownership IN ('owned','rented'))");
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT chk_vehicle_status
            CHECK (status IN ('available','maintenance','inactive'))");
        // Kendaraan sewa wajib punya rental company, kendaraan milik sendiri tidak boleh punya
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT chk_vehicle_rental
            CHECK ((ownership = 'rented') = (rental_company_id IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
