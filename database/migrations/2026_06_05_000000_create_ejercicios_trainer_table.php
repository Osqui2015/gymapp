<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejercicios_trainer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->string('nombre');
            $table->string('grupo_muscular');
            $table->string('equipamiento')->default('Ninguno');
            $table->string('descripcion')->nullable();
            $table->timestamps();
            
            $table->unique(['trainer_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejercicios_trainer');
    }
};