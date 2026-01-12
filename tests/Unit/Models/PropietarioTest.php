<?php

namespace Tests\Unit\Models;

use App\Models\Propietario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropietarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_propietario_model_exists(): void
    {
        // Verificar que el modelo existe y se puede instanciar
        $propietario = new Propietario;

        $this->assertInstanceOf(Propietario::class, $propietario);
        $this->assertEquals('propietarios', $propietario->getTable());

        // Verificar que se puede crear un propietario
        $propietarioCreado = Propietario::factory()->create();

        $this->assertNotNull($propietarioCreado);
        $this->assertNotNull($propietarioCreado->id);
    }

    public function test_propietario_has_correct_fillable_fields(): void
    {
        $propietario = new Propietario;

        $expectedFillable = [
            'tipo_identificacion',
            'numero_identificacion',
            'nombre_completo',
            'tipo_propietario',
            'direccion_contacto',
            'telefono_contacto',
            'correo_electronico',
            'estado',
        ];

        $this->assertEquals($expectedFillable, $propietario->getFillable());

        // Verificar que se pueden crear propietarios con estos campos
        $propietario = Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'direccion_contacto' => 'Calle 123',
            'telefono_contacto' => '3001234567',
            'correo_electronico' => 'juan@example.com',
            'estado' => 'Activo',
        ]);

        $this->assertNotNull($propietario->id);
        $this->assertEquals('Juan Pérez', $propietario->nombre_completo);
        $this->assertEquals('1234567890', $propietario->numero_identificacion);
        $this->assertEquals('Cédula de Ciudadanía', $propietario->tipo_identificacion);
        $this->assertEquals('Persona Natural', $propietario->tipo_propietario);
        $this->assertEquals('Activo', $propietario->estado);
    }
}
