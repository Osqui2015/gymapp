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
            $table->string('tipo_serie', 32)->default('efectiva')->after('completado');
            $table->string('sesion_uuid', 64)->nullable()->after('tipo_serie')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            $table->dropIndex(['sesion_uuid']);
            $table->dropColumn(['tipo_serie', 'sesion_uuid']);
        });
    }
};
