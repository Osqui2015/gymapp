<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_rutinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nivel');
            $table->string('modalidad');
            $table->string('dia_actual')->default('Día 1');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_rutinas');
    }
};