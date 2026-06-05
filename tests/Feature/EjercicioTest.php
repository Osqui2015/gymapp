<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EjercicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_ejercicios(): void
    {
        Ejercicio::create([
            'nombre' => 'Sentadilla Libre',
            'equipamiento' => 'Barra',
            'grupo_muscular' => 'Cuádriceps',
            'descripcion' => 'Sentadilla con barra sobre los hombros',
        ]);

        $response = $this->getJson('/api/ejercicios');

        $response->assertStatus(200)
            ->assertJsonFragment(['nombre' => 'Sentadilla Libre']);
    }

    public function test_can_filter_ejercicios_by_grupo_muscular(): void
    {
        Ejercicio::create([
            'nombre' => 'Sentadilla Libre',
            'equipamiento' => 'Barra',
            'grupo_muscular' => 'Cuádriceps',
        ]);

        Ejercicio::create([
            'nombre' => 'Press de Banca',
            'equipamiento' => 'Barra',
            'grupo_muscular' => 'Pecho',
        ]);

        $response = $this->getJson('/api/ejercicios?grupo_muscular=Pecho');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['nombre' => 'Press de Banca'])
            ->assertJsonMissing(['nombre' => 'Sentadilla Libre']);
    }

    public function test_can_filter_ejercicios_by_equipamiento(): void
    {
        Ejercicio::create([
            'nombre' => 'Sentadilla Libre',
            'equipamiento' => 'Barra',
            'grupo_muscular' => 'Cuádriceps',
        ]);

        Ejercicio::create([
            'nombre' => 'Prensa de Piernas',
            'equipamiento' => 'Máquina',
            'grupo_muscular' => 'Cuádriceps',
        ]);

        $response = $this->getJson('/api/ejercicios?equipamiento=Barra');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['nombre' => 'Sentadilla Libre'])
            ->assertJsonMissing(['nombre' => 'Prensa de Piernas']);
    }

    public function test_can_get_unique_equipamientos(): void
    {
        Ejercicio::create([
            'nombre' => 'Sentadilla Libre',
            'equipamiento' => 'Barra',
            'grupo_muscular' => 'Cuádriceps',
        ]);

        Ejercicio::create([
            'nombre' => 'Prensa de Piernas',
            'equipamiento' => 'Máquina',
            'grupo_muscular' => 'Cuádriceps',
        ]);

        Ejercicio::create([
            'nombre' => 'Press de Banca',
            'equipamiento' => 'Barra',
            'grupo_muscular' => 'Pecho',
        ]);

        $response = $this->getJson('/api/ejercicios/equipamientos');

        $response->assertStatus(200)
            ->assertJson(['Barra', 'Máquina']);
    }
}
