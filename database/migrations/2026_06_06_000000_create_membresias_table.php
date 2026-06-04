<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membresias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tipo_plan')->default('mensual'); // mensual, trimestral, semestral, anual
            $table->decimal('precio', 10, 2)->default(0);
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['activo', 'por_vencer', 'vencido', 'cancelado'])->default('activo');
            $table->date('ultimo_pago')->nullable();
            $table->string('metodo_pago')->nullable(); // transferencia, efectivo, tarjeta, etc.
            $table->text('notas')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'estado']);
            $table->index('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membresias');
    }
};