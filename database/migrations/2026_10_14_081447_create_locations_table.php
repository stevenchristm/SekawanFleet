<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('type', 20); // head_office | branch_office | mine
            $table->text('address')->nullable();
            $table->timestamps();

            $table->unique(['region_id', 'name']);
        });

        DB::statement("ALTER TABLE locations ADD CONSTRAINT chk_location_type
            CHECK (type IN ('head_office','branch_office','mine'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
