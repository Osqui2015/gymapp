<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ejercicios', function (Blueprint $table) {
            // Atribución de la fuente del video/imagen (ej: "Fitness Addict (Facebook)")
            $table->string('fuente_credito', 150)->nullable()->after('url_video');
            // Tipo de fuente: facebook, youtube, vimeo, custom, etc.
            $table->string('fuente_tipo', 30)->nullable()->after('fuente_credito');
        });
    }

    public function down(): void
    {
        Schema::table('ejercicios', function (Blueprint $table) {
            $table->dropColumn(['fuente_credito', 'fuente_tipo']);
        });
    }
};
