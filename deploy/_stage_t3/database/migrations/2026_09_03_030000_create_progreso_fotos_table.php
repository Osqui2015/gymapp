<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progreso_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('fecha');
            // tipo: front (frente), side (perfil), back (espalda)
            $table->enum('tipo', ['front', 'side', 'back'])->default('front');
            $table->string('foto_path');          // ruta relativa al disk "public"
            $table->text('notas')->nullable();   // comentarios libres del user
            $table->unsignedInteger('peso')->nullable();  // peso al momento de la foto (snapshot)
            $table->timestamps();

            $table->index(['user_id', 'fecha']);     // galería cronológica
            $table->index(['user_id', 'tipo']);      // filtro por ángulo
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progreso_fotos');
    }
};
