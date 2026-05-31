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
        Schema::create('rutinas', function (Blueprint $table) {
            $table->id();
            $table->string('nivel');
            $table->string('modalidad');
            $table->string('dia');
            $table->integer('series')->nullable();
            $table->string('reps_min');
            $table->string('reps_max');
            $table->decimal('descanso_min', 4, 2);
            $table->string('ejercicio_nombre');
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutinas');
    }
};
