<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Los 18 músculos canónicos que openGym usa para el body map.
        // El slug es el mismo que openGym (compatibilidad con el modelo mental),
        // y svg_id mapea al <path> dentro del SVG del BodyMap.vue.
        Schema::create('musculos', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique()->comment('chest, biceps, quadriceps...');
            $table->string('nombre_es', 100);
            $table->string('nombre_en', 100);
            $table->string('body_part', 50)->comment('upper-body, lower-body, core, head');
            $table->string('svg_id', 50)->nullable()->comment('ID del <path> en BodyMap.vue');
            $table->unsignedTinyInteger('orden')->default(99)->comment('Orden de display');
            $table->timestamps();
        });

        // Aliases para colapsar las 50+ formas de nombrar el mismo músculo
        // (deltoids/delts/hombros → deltoids; abdominals/abdomen/abs → abs, etc.)
        Schema::create('musculo_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('alias', 100);
            $table->foreignId('musculo_id')->constrained('musculos')->cascadeOnDelete();
            $table->unique(['alias', 'musculo_id']);
            $table->index('alias');
        });

        // Tabla pivote ejercicio ↔ músculo con tipo (primario/secundario) y peso.
        // peso: 1.0 para primarios, 0.4 para secundarios (modelo openGym).
        Schema::create('ejercicio_musculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejercicio_id')->constrained('ejercicios')->cascadeOnDelete();
            $table->foreignId('musculo_id')->constrained('musculos')->cascadeOnDelete();
            $table->enum('tipo', ['primario', 'secundario']);
            $table->decimal('peso', 3, 2)->default(1.00);
            $table->string('fuente', 30)->default('mapeo_automatico')
                ->comment('mapeo_automatico, manual, jahel_dataset, opengym_overrides');
            $table->timestamps();
            $table->unique(['ejercicio_id', 'musculo_id', 'tipo'], 'ej_mus_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejercicio_musculos');
        Schema::dropIfExists('musculo_aliases');
        Schema::dropIfExists('musculos');
    }
};
