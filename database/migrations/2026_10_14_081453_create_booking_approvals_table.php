<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('level');
            $table->string('status', 20)->default('waiting');
            $table->text('note')->nullable();
            $table->timestampTz('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'level']);
            $table->unique(['booking_id', 'approver_id']);
            $table->index(['approver_id', 'status']); // inbox approver
        });

        DB::statement("ALTER TABLE booking_approvals ADD CONSTRAINT chk_approval_status
            CHECK (status IN ('waiting','pending','approved','rejected','skipped'))");
        DB::statement('ALTER TABLE booking_approvals ADD CONSTRAINT chk_approval_level CHECK (level >= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_approvals');
    }
};
