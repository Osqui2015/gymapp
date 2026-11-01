<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutina_favoritas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('rutina_id')->constrained('rutinas')->onDelete('cascade');
            $table->timestamps();

            // Un user no puede tener la misma rutina marcada como favorita 2 veces
            $table->unique(['user_id', 'rutina_id']);
            // Para listar las favoritas de un user ordenadas por cuando las marcó
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutina_favoritas');
    }
};
