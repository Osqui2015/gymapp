<?php

namespace Tests\Unit;

use App\Services\TelefonoService;
use PHPUnit\Framework\TestCase;

/**
 * Test del normalizador de teléfonos (Fase 0.8).
 *
 * La tabla `users.telefono` estaba poblada con basura de seed:
 * 8 números con el patrón `11-XXXX-XXXX`, 3 de España (`+34`), 3 de
 * Tucumán sin área real y 1 duplicado exacto.
 *
 * La regla que se fija acá es conservadora a propósito: un número
 * ambiguo NO se convierte, porque un `wa.me` equivocado le escribe a
 * otra persona. Es preferible dejar el teléfono vacío y que la UI
 * muestre "teléfono no válido".
 */
class TelefonoServiceTest extends TestCase
{
    private TelefonoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new TelefonoService;
    }

    // -----------------------------------------------------------------
    // Normalización
    // -----------------------------------------------------------------

    public function test_normaliza_un_local_con_prefijo_de_movil(): void
    {
        // 11-1234-5678 -> +54 9 11 1234-5678
        $this->assertSame('+5491112345678', $this->servicio->normalizar('11-1234-5678'));
    }

    public function test_normaliza_un_local_con_area_de_tres_digitos(): void
    {
        // Tucumán (381) + 7 dígitos.
        $this->assertSame('+5493811234567', $this->servicio->normalizar('3811234567'));
    }

    public function test_normaliza_un_numero_con_espacios_y_guiones(): void
    {
        $this->assertSame('+5493811234567', $this->servicio->normalizar('+54 9 381 123-4567'));
    }

    public function test_un_numero_ya_normalizado_no_cambia(): void
    {
        $this->assertSame('+5491112345678', $this->servicio->normalizar('+5491112345678'));
    }

    public function test_respeta_el_codigo_de_pais_explicito(): void
    {
        // No asumimos Argentina si el número ya trae su código de país.
        $this->assertSame('+34906329365', $this->servicio->normalizar('+34 906-329365'));
    }

    // -----------------------------------------------------------------
    // Números que NO se pueden normalizar
    // -----------------------------------------------------------------

    public function test_un_numero_ambiguo_de_nueve_digitos_no_se_normaliza(): void
    {
        // 934-482229: no se puede saber si el 9 inicial es el prefijo de
        // móvil o parte del área. Inventar el área es peor que dejarlo
        // vacío: un wa.me equivocado le escribe a otra persona.
        $this->assertNull($this->servicio->normalizar('934-482229'));
        $this->assertNull($this->servicio->normalizar('988-83-8244'));
        $this->assertNull($this->servicio->normalizar('919 585097'));
    }

    public function test_un_vacio_o_null_devuelve_null(): void
    {
        $this->assertNull($this->servicio->normalizar(null));
        $this->assertNull($this->servicio->normalizar(''));
        $this->assertNull($this->servicio->normalizar('   '));
        $this->assertNull($this->servicio->normalizar('abc'));
    }

    public function test_un_numero_muy_corto_no_se_normaliza(): void
    {
        $this->assertNull($this->servicio->normalizar('123'));
    }

    // -----------------------------------------------------------------
    // Validación
    // -----------------------------------------------------------------

    public function test_es_valido_para_moviles_argentinos_reales(): void
    {
        $this->assertTrue($this->servicio->esValido('11-1234-5678'));
        $this->assertTrue($this->servicio->esValido('3811234567'));
        $this->assertTrue($this->servicio->esValido('+54 9 381 123-4567'));
    }

    public function test_no_es_valido_para_los_ambiguos(): void
    {
        $this->assertFalse($this->servicio->esValido('934-482229'));
        $this->assertFalse($this->servicio->esValido(null));
        $this->assertFalse($this->servicio->esValido(''));
    }

    public function test_es_movil_argentino_valido_rechaza_areas_inventadas(): void
    {
        // '34' no es un código de área argentino (La Plata es 341).
        $this->assertFalse($this->servicio->esMovilArgentinoValido('+54934482229'));
        $this->assertTrue($this->servicio->esMovilArgentinoValido('+5493811234567'));
        $this->assertTrue($this->servicio->esMovilArgentinoValido('+5491112345678'));
    }

    // -----------------------------------------------------------------
    // Links de WhatsApp
    // -----------------------------------------------------------------

    public function test_el_link_de_whatsapp_se_genera_con_el_mensaje(): void
    {
        $link = $this->servicio->whatsappLink('3811234567', 'Hola, tu cuota venció');

        $this->assertNotNull($link);
        $this->assertStringStartsWith('https://wa.me/+5493811234567?text=', $link);
        $this->assertStringContainsString(rawurlencode('Hola, tu cuota venció'), $link);
    }

    public function test_el_link_de_whatsapp_es_null_si_el_telefono_no_sirve(): void
    {
        // La UI necesita distinguir "no se puede generar" de "generado":
        // un link roto mandaría al usuario a un 404 de WhatsApp.
        $this->assertNull($this->servicio->whatsappLink('934-482229'));
        $this->assertNull($this->servicio->whatsappLink(null));
    }

    // -----------------------------------------------------------------
    // Formato legible
    // -----------------------------------------------------------------

    public function test_formatea_para_la_ui(): void
    {
        // Formato argentino: +54 9 <area> <4 dígitos>-<4 dígitos>.
        $this->assertSame('+54 9 11 1234-5678', $this->servicio->formatear('11-1234-5678'));

        // Con área de 3 dígitos (Tucumán = 381) el prefijo se detecta
        // bien: sin eso, `+54 9 381 1234567` se mostraría con un área
        // de 2 dígitos que no existe.
        $this->assertSame('+54 9 381 123-4567', $this->servicio->formatear('3811234567'));
    }
}
