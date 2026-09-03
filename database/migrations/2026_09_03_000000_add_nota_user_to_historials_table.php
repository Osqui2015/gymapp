<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            // Nota libre del usuario por set: "sentí el bíceps", "dolor lumbar", "fácil", etc.
            // Complementa comentario_trainer (que es la devolución del trainer).
            if (!Schema::hasColumn('historials', 'nota_user')) {
                $table->text('nota_user')->nullable()->after('comentario_trainer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            if (Schema::hasColumn('historials', 'nota_user')) {
                $table->dropColumn('nota_user');
            }
        });
    }
};
