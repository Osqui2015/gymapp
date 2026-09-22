<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: agregar campos del dataset VisualGym (exercises-dataset)
 * a la tabla `ejercicios` existente, sin romper nada.
 *
 * Estrategia:
 *  - external_id: id original del dataset (ej "0001"), único.
 *  - body_part, target, media_id: campos del dataset que no existían.
 *  - image_path, gif_path: rutas locales en storage/app/public.
 *  - instructions, instruction_steps, secondary_muscles: JSON.
 *  - source_created_at: timestamp original del dataset.
 *  - source: marca de origen ("visualgym" / "manual" / null).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ejercicios', function (Blueprint $table) {
            // ID externo del dataset (ej. "0001"). Único para idempotencia.
            $table->string('external_id', 10)->nullable()->unique()->after('id')
                ->comment('ID original del dataset VisualGym (0001, 0002, ...)');

            // Body part del dataset (chest, back, upper legs, etc.).
            // El grupo_muscular actual queda como el músculo primario
            // que ya tenés mapeado a español. body_part es la categoría
            // gruesa del dataset (en inglés).
            $table->string('body_part', 50)->nullable()->after('grupo_muscular')
                ->comment('Body part del dataset (chest, back, upper legs...)');

            // Músculo target primario (pectoralis major, biceps, etc.).
            $table->string('target', 100)->nullable()->after('body_part')
                ->comment('Target muscle primario del dataset (en inglés)');

            // Media
            $table->string('media_id', 50)->nullable()->after('url_video');
            $table->string('image_path', 255)->nullable()->after('media_id')
                ->comment('Path relativo en storage/exercises/images/');
            $table->string('gif_path', 255)->nullable()->after('image_path')
                ->comment('Path relativo en storage/exercises/videos/');

            // Instrucciones multilingües (JSON). Default language: 'es'.
            $table->json('instructions')->nullable()->after('descripcion')
                ->comment('Instrucciones por idioma (en, es, it, tr, ru, zh, hi, pl, ko, fr)');

            // Pasos ordenados por idioma.
            $table->json('instruction_steps')->nullable()->after('instructions')
                ->comment('Pasos ordenados por idioma');

            // Músculos secundarios (JSON array).
            $table->json('secondary_muscles')->nullable()->after('instruction_steps')
                ->comment('Array de músculos secundarios');

            // Timestamp original del dataset.
            $table->timestamp('source_created_at')->nullable()->after('secondary_muscles');

            // Marca de origen para distinguir registros del dataset VisualGym
            // de los manuales/seeders existentes.
            $table->string('source', 30)->nullable()->default(null)->after('source_created_at')
                ->comment("Origen del registro: 'visualgym', 'manual', null");

            // Índices útiles para los filtros del catálogo.
            $table->index('body_part');
            $table->index('target');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('ejercicios', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropIndex(['body_part']);
            $table->dropIndex(['target']);
            $table->dropIndex(['source']);

            $table->dropColumn([
                'external_id',
                'body_part',
                'target',
                'media_id',
                'image_path',
                'gif_path',
                'instructions',
                'instruction_steps',
                'secondary_muscles',
                'source_created_at',
                'source',
            ]);
        });
    }
};
