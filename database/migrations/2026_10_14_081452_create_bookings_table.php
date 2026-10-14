<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('driver_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('requester_name', 100);
            $table->text('purpose');
            $table->string('destination', 150);
            $table->timestampTz('start_at');
            $table->timestampTz('end_at');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('current_level')->default(1);
            $table->timestamps();

            $table->index('start_at');
        });

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT chk_booking_status
            CHECK (status IN ('pending','approved','rejected','completed','cancelled'))");
        DB::statement('ALTER TABLE bookings ADD CONSTRAINT chk_booking_period CHECK (end_at > start_at)');
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT no_vehicle_overlap
            EXCLUDE USING gist (vehicle_id WITH =, tstzrange(start_at, end_at) WITH &&)
            WHERE (status IN ('pending','approved'))");
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT no_driver_overlap
            EXCLUDE USING gist (driver_id WITH =, tstzrange(start_at, end_at) WITH &&)
            WHERE (status IN ('pending','approved'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
