<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            if (!Schema::hasColumn('historials', 'reps_realizadas')) {
                $table->integer('reps_realizadas')->nullable()->after('reps_max');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            if (Schema::hasColumn('historials', 'reps_realizadas')) {
                $table->dropColumn('reps_realizadas');
            }
        });
    }
};
