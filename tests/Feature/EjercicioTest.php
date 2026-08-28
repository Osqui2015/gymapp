<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Musculo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_index_returns_musculos_per_ejercicio(): void
    {
        $pectoral = Musculo::create([
            'slug' => 'pectoral-major',
            'nombre_es' => 'Pectoral mayor',
            'nombre_en' => 'Pectoralis major',
            'body_part' => 'chest',
            'orden' => 1,
        ]);
        $deltoides = Musculo::create([
            'slug' => 'deltoid-anterior',
            'nombre_es' => 'Deltoides anterior',
            'nombre_en' => 'Anterior deltoid',
            'body_part' => 'shoulders',
            'orden' => 2,
        ]);

        $press = Ejercicio::create([
            'nombre' => 'Press de Banca',
            'equipamiento' => 'Barra',
            'grupo_muscular' => 'Pecho',
        ]);
        $press->musculos()->attach($pectoral->id, ['tipo' => 'primario', 'peso' => 1.0]);
        $press->musculos()->attach($deltoides->id, ['tipo' => 'secundario', 'peso' => 0.4]);

        $response = $this->getJson('/api/ejercicios');

        $response->assertStatus(200)
            ->assertJsonFragment(['slug' => 'pectoral-major'])
            ->assertJsonFragment(['slug' => 'deltoid-anterior']);
    }

    public function test_can_filter_ejercicios_by_musculo_slug(): void
    {
        $pectoral = Musculo::create([
            'slug' => 'pectoral-major',
            'nombre_es' => 'Pectoral mayor',
            'nombre_en' => 'Pectoralis major',
            'body_part' => 'chest',
            'orden' => 1,
        ]);
        $cuadriceps = Musculo::create([
            'slug' => 'quadriceps',
            'nombre_es' => 'Cuádriceps',
            'nombre_en' => 'Quadriceps',
            'body_part' => 'legs',
            'orden' => 2,
        ]);

        $press = Ejercicio::create([
            'nombre' => 'Press de Banca',
            'equipamiento' => 'Barra',
            'grupo_muscular' => 'Pecho',
        ]);
        $press->musculos()->attach($pectoral->id, ['tipo' => 'primario', 'peso' => 1.0]);

        $sentadilla = Ejercicio::create([
            'nombre' => 'Sentadilla Libre',
            'equipamiento' => 'Barra',
            'grupo_muscular' => 'Cuádriceps',
        ]);
        $sentadilla->musculos()->attach($cuadriceps->id, ['tipo' => 'primario', 'peso' => 1.0]);

        $response = $this->getJson('/api/ejercicios?musculo_slug=pectoral-major');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['nombre' => 'Press de Banca'])
            ->assertJsonMissing(['nombre' => 'Sentadilla Libre']);
    }

    public function test_musculos_endpoint_returns_catalogo(): void
    {
        Musculo::create([
            'slug' => 'pectoral-major',
            'nombre_es' => 'Pectoral mayor',
            'nombre_en' => 'Pectoralis major',
            'body_part' => 'chest',
            'orden' => 1,
        ]);
        Musculo::create([
            'slug' => 'quadriceps',
            'nombre_es' => 'Cuádriceps',
            'nombre_en' => 'Quadriceps',
            'body_part' => 'legs',
            'orden' => 2,
        ]);

        $response = $this->getJson('/api/musculos');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['slug' => 'pectoral-major', 'nombre_es' => 'Pectoral mayor']);
    }
}
