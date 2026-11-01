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
        Schema::create('sesiones_entrenamiento', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rutina_nombre');
            $table->string('dia');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duracion_segundos')->nullable();
            $table->decimal('volumen_total', 10, 2)->default(0);
            $table->unsignedSmallInteger('series_completadas')->default(0);
            $table->unsignedSmallInteger('series_totales')->default(0);
            $table->unsignedSmallInteger('prs_superados')->default(0);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones_entrenamiento');
    }
};
