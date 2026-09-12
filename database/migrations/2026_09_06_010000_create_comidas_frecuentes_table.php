<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comidas_frecuentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedInteger('calorias')->default(0);
            $table->unsignedInteger('proteinas')->default(0);
            $table->unsignedInteger('carbohidratos')->default(0);
            $table->unsignedInteger('grasas')->default(0);
            $table->string('porcion')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comidas_frecuentes');
    }
};
