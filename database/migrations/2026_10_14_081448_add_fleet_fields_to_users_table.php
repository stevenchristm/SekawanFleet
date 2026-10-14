<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role', 20)->default('approver')->after('password');
            $table->string('position', 100)->nullable()->after('role');
            $table->index('role');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_user_role
            CHECK (role IN ('admin','approver'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_user_role');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'position']);
        });
    }
};
