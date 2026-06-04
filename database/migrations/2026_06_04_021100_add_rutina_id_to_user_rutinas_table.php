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
        Schema::table('user_rutinas', function (Blueprint $table) {
            $table->unsignedBigInteger('rutina_id')->nullable()->after('assigned_by');
            $table->foreign('rutina_id')->references('id')->on('rutinas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_rutinas', function (Blueprint $table) {
            //
        });
    }
};
