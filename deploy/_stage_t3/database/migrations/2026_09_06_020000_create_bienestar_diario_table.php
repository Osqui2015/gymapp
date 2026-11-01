<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bienestar_diario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('horas_sueno', 3, 1)->nullable(); // Ej: 7.5 hrs
            $table->unsignedTinyInteger('calidad_sueno')->nullable(); // 1 a 5
            $table->unsignedTinyInteger('nivel_estres')->nullable(); // 1 a 5
            $table->unsignedTinyInteger('dolor_muscular')->nullable(); // 1 a 5 (DOMS)
            $table->string('notas', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bienestar_diario');
    }
};
