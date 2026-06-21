<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('historial_id')->nullable()->constrained('historials')->nullOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['alumno_id', 'created_at']);
            $table->index(['trainer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_comments');
    }
};
