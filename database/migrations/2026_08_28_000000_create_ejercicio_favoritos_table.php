<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejercicio_favoritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ejercicio_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Un user no puede marcar el mismo ejercicio 2 veces como favorito
            $table->unique(['user_id', 'ejercicio_id']);
            // Para listar los favoritos de un user ordenados por cuando los marco
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejercicio_favoritos');
    }
};
