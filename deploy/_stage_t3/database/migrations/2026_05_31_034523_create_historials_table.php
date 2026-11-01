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
        Schema::create('historials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('rutina_nombre');
            $table->string('dia');
            $table->string('ejercicio_nombre');
            $table->integer('series_numero');
            $table->integer('series_completadas')->default(0);
            $table->string('reps_min');
            $table->string('reps_max');
            $table->decimal('descanso_min', 4, 2);
            $table->decimal('peso', 6, 2)->nullable();
            $table->boolean('completado')->default(false);
            $table->date('fecha');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historials');
    }
};
