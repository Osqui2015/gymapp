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
        Schema::create('diario_nutricion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('fecha');
            $table->integer('calorias')->default(0);
            $table->integer('proteinas')->default(0);
            $table->integer('carbohidratos')->default(0);
            $table->integer('grasas')->default(0);
            $table->integer('agua_vasos')->default(0);
            $table->unique(['user_id', 'fecha']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diario_nutricion');
    }
};
