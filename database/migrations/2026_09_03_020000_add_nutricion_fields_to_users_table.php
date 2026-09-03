<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Sexo biológico: necesario para la fórmula Mifflin-St Jeor
            // (hombres y mujeres usan constantes distintas).
            if (!Schema::hasColumn('users', 'sexo')) {
                $table->enum('sexo', ['masculino', 'femenino'])->nullable()->after('altura');
            }
            // Edad: si no está seteada, se pide al usuario al calcular TDEE.
            if (!Schema::hasColumn('users', 'edad')) {
                $table->unsignedTinyInteger('edad')->nullable()->after('sexo');
            }
            // Nivel de actividad física (factor para TDEE).
            //   sedentario   : BMR × 1.2   (oficina, sin deporte)
            //   ligero       : BMR × 1.375 (1-3 días/semana deporte suave)
            //   moderado     : BMR × 1.55  (3-5 días/semana)
            //   activo       : BMR × 1.725 (6-7 días/semana)
            //   muy_activo   : BMR × 1.9   (atleta, trabajo físico)
            if (!Schema::hasColumn('users', 'nivel_actividad')) {
                $table->enum('nivel_actividad', ['sedentario', 'ligero', 'moderado', 'activo', 'muy_activo'])
                    ->nullable()
                    ->after('edad');
            }
            // Objetivo nutricional: define el ajuste de calorías y la distribución
            // de macros sugeridos.
            if (!Schema::hasColumn('users', 'objetivo_nutricional')) {
                $table->enum('objetivo_nutricional', ['perder_grasa', 'mantener', 'ganar_masa'])
                    ->default('mantener')
                    ->after('nivel_actividad');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['sexo', 'edad', 'nivel_actividad', 'objetivo_nutricional'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
