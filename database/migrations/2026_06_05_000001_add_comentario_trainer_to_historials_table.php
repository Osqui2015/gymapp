<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            $table->text('comentario_trainer')->nullable()->after('completado');
            $table->foreignId('trainer_id')->nullable()->constrained('users')->onDelete('set null')->after('comentario_trainer');
        });
    }

    public function down(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->dropColumn(['comentario_trainer', 'trainer_id']);
        });
    }
};