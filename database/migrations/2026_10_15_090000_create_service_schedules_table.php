<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);                                   // routine | repair | inspection
            $table->date('scheduled_date');
            $table->unsignedInteger('odometer_due')->nullable();         // target km servis berkala
            $table->string('description', 255)->nullable();             // pekerjaan yang direncanakan
            $table->string('workshop', 100)->nullable();                 // bengkel / vendor
            $table->string('status', 20)->default('scheduled');          // scheduled | completed | cancelled
            $table->date('completed_date')->nullable();
            $table->unsignedInteger('odometer_at_service')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'status', 'scheduled_date']);
            $table->index(['status', 'scheduled_date']);
        });

        DB::statement("ALTER TABLE service_schedules ADD CONSTRAINT chk_service_type
            CHECK (type IN ('routine','repair','inspection'))");
        DB::statement("ALTER TABLE service_schedules ADD CONSTRAINT chk_service_status
            CHECK (status IN ('scheduled','completed','cancelled'))");
        // Data penyelesaian hanya ada (dan wajib ada) kalau status completed
        DB::statement("ALTER TABLE service_schedules ADD CONSTRAINT chk_service_completion
            CHECK ((status = 'completed') = (completed_date IS NOT NULL AND completed_by IS NOT NULL))");
        DB::statement('ALTER TABLE service_schedules ADD CONSTRAINT chk_service_cost
            CHECK (cost IS NULL OR cost >= 0)');
        // Satu kendaraan tidak boleh punya dua servis terjadwal di tanggal yang sama
        DB::statement("CREATE UNIQUE INDEX uq_service_vehicle_date ON service_schedules (vehicle_id, scheduled_date)
            WHERE status = 'scheduled'");
    }

    public function down(): void
    {
        Schema::dropIfExists('service_schedules');
    }
};
