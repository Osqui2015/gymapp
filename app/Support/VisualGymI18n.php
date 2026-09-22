<?php

namespace App\Support;

/**
 * Mapeo de strings en inglés del dataset VisualGym a español.
 *
 * Centraliza la traducción para que el catálogo pueda mostrar nombres
 * en español sin tener que cambiar el modelo ni la DB.
 *
 * Si un string no está en el mapeo, se devuelve el original (en inglés).
 * Esto cubre los strings reales del dataset según se observó en /api/visualgym/facets.
 */
class VisualGymI18n
{
    /**
     * body_part (categoría gruesa del dataset).
     */
    public const BODY_PARTS = [
        'back' => 'Espalda',
        'cardio' => 'Cardio',
        'chest' => 'Pecho',
        'lower arms' => 'Antebrazos',
        'lower legs' => 'Pantorrillas',
        'neck' => 'Cuello',
        'shoulders' => 'Hombros',
        'upper arms' => 'Brazos',
        'upper legs' => 'Piernas',
        'waist' => 'Cintura / Core',
    ];

    /**
     * target (músculo primario).
     */
    public const TARGETS = [
        'abductors' => 'Abductores',
        'abs' => 'Abdomen',
        'adductors' => 'Aductores',
        'biceps' => 'Bíceps',
        'calves' => 'Pantorrillas',
        'cardiovascular system' => 'Sistema cardiovascular',
        'delts' => 'Deltoides',
        'forearms' => 'Antebrazos',
        'glutes' => 'Glúteos',
        'hamstrings' => 'Isquiotibiales',
        'lats' => 'Dorsales',
        'levator scapulae' => 'Elevador de la escápula',
        'pectorals' => 'Pectorales',
        'quads' => 'Cuádriceps',
        'serratus anterior' => 'Serrato anterior',
        'spine' => 'Columna',
        'traps' => 'Trapecios',
        'triceps' => 'Tríceps',
        'upper back' => 'Espalda alta',
    ];

    /**
     * equipment (equipamiento).
     */
    public const EQUIPMENT = [
        'assisted' => 'Asistido',
        'band' => 'Banda elástica',
        'barbell' => 'Barra',
        'body weight' => 'Peso corporal',
        'bosu ball' => 'Bosu',
        'cable' => 'Polea',
        'dumbbell' => 'Mancuerna',
        'elliptical machine' => 'Elíptica',
        'ez barbell' => 'Barra Z',
        'hammer' => 'Martillo',
        'kettlebell' => 'Kettlebell',
        'leverage machine' => 'Máquina de palanca',
        'medicine ball' => 'Balón medicinal',
        'olympic barbell' => 'Barra olímpica',
        'resistance band' => 'Banda de resistencia',
        'roller' => 'Rodillo',
        'rope' => 'Cuerda',
        'skierg machine' => 'Skierg',
        'sled machine' => 'Trineo',
        'smith machine' => 'Máquina Smith',
        'stability ball' => 'Pelota de estabilidad',
        'stationary bike' => 'Bicicleta fija',
        'stepmill machine' => 'StepMill',
        'tire' => 'Neumático',
        'trap bar' => 'Barra hexagonal',
        'upper body ergometer' => 'Ergómetro de tren superior',
        'weighted' => 'Lastrado',
        'wheel roller' => 'Rueda abdominal',
    ];

    /**
     * Traduce un valor según el campo. Si no existe el mapping, devuelve el original.
     */
    public static function translate(string $field, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return match ($field) {
            'body_part' => self::BODY_PARTS[$value] ?? $value,
            'target' => self::TARGETS[$value] ?? $value,
            'equipamiento', 'equipment' => self::EQUIPMENT[$value] ?? $value,
            default => $value,
        };
    }

    /**
     * Devuelve el mapping completo de un campo. Útil para el front
     * (cachea el dict en JS para no traducir en cada render).
     */
    public static function mapping(string $field): array
    {
        return match ($field) {
            'body_part' => self::BODY_PARTS,
            'target' => self::TARGETS,
            'equipamiento', 'equipment' => self::EQUIPMENT,
            default => [],
        };
    }
}
