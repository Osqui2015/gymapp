<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Altura del usuario (en cm). Opcional, pero ayuda a calcular IMC.
            $table->decimal('altura', 5, 2)->nullable()->after('telefono');
            // Body weight goal (en kg). El chart dibuja una línea punteada acá.
            $table->decimal('peso_objetivo', 5, 2)->nullable()->after('altura');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['altura', 'peso_objetivo']);
        });
    }
};
