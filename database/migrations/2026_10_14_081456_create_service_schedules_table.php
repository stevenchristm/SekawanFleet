<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
    {
        Schema::create('service_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->string('service_type', 100); // ganti oli, servis berkala, ban, dll
            $table->string('status', 20)->default('scheduled');
            $table->decimal('cost', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_date']);
        });

        DB::statement("ALTER TABLE service_schedules ADD CONSTRAINT chk_service_status
            CHECK (status IN ('scheduled','done'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('service_schedules');
    }
};
