<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_rutinas', function (Blueprint $table) {
            if (! Schema::hasColumn('user_rutinas', 'assigned_by')) {
                $table->foreignId('assigned_by')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_rutinas', function (Blueprint $table) {
            if (Schema::hasColumn('user_rutinas', 'assigned_by')) {
                $table->dropConstrainedForeignId('assigned_by');
            }
        });
    }
};
