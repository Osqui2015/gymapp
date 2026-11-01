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
        Schema::create('progresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('fecha');
            $table->decimal('peso', 5, 2)->nullable();
            $table->decimal('altura', 5, 2)->nullable();
            $table->integer('edad')->nullable();
            $table->enum('sexo', ['masculino', 'femenino'])->nullable();
            $table->decimal('cuello', 5, 2)->nullable();
            $table->decimal('hombros', 5, 2)->nullable();
            $table->decimal('pecho', 5, 2)->nullable();
            $table->decimal('brazos', 5, 2)->nullable();
            $table->decimal('cintura', 5, 2)->nullable();
            $table->decimal('cadera', 5, 2)->nullable();
            $table->decimal('muslos', 5, 2)->nullable();
            $table->decimal('pantorrillas', 5, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progresos');
    }
};
