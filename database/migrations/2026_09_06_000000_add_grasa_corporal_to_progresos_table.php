<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progresos', function (Blueprint $table) {
            if (! Schema::hasColumn('progresos', 'grasa_corporal')) {
                $table->decimal('grasa_corporal', 5, 2)->nullable()->after('peso');
            }
        });
    }

    public function down(): void
    {
        Schema::table('progresos', function (Blueprint $table) {
            if (Schema::hasColumn('progresos', 'grasa_corporal')) {
                $table->dropColumn('grasa_corporal');
            }
        });
    }
};
