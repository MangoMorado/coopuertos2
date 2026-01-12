<?php

namespace Tests\Feature\Propietarios;

use App\Models\Propietario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropietarioShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_propietario_details(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($user)->get("/propietarios/{$propietario->id}");

        $response->assertStatus(200);
        $response->assertViewIs('propietarios.show');
        $response->assertViewHas('propietario');
        $this->assertEquals($propietario->id, $response->viewData('propietario')->id);
    }

    public function test_propietario_show_displays_all_information(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::create([
            'tipo_identificacion' => 'RUC/NIT',
            'numero_identificacion' => '1234567890123',
            'nombre_completo' => 'Empresa XYZ S.A.',
            'tipo_propietario' => 'Persona Jurídica',
            'direccion_contacto' => 'Av. Principal 123',
            'telefono_contacto' => '+593 999999999',
            'correo_electronico' => 'contacto@empresaxyz.com',
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($user)->get("/propietarios/{$propietario->id}");

        $response->assertStatus(200);
        $propietarioEnVista = $response->viewData('propietario');

        // Verificar que todos los campos están presentes
        $this->assertEquals('RUC/NIT', $propietarioEnVista->tipo_identificacion);
        $this->assertEquals('1234567890123', $propietarioEnVista->numero_identificacion);
        $this->assertEquals('Empresa XYZ S.A.', $propietarioEnVista->nombre_completo);
        $this->assertEquals('Persona Jurídica', $propietarioEnVista->tipo_propietario);
        $this->assertEquals('Av. Principal 123', $propietarioEnVista->direccion_contacto);
        $this->assertEquals('+593 999999999', $propietarioEnVista->telefono_contacto);
        $this->assertEquals('contacto@empresaxyz.com', $propietarioEnVista->correo_electronico);
        $this->assertEquals('Activo', $propietarioEnVista->estado);

        // Verificar que la vista contiene la información
        $response->assertSee('RUC/NIT', false);
        $response->assertSee('1234567890123', false);
        $response->assertSee('Empresa XYZ S.A.', false);
        $response->assertSee('Persona Jurídica', false);
        $response->assertSee('Av. Principal 123', false);
        $response->assertSee('+593 999999999', false);
        $response->assertSee('contacto@empresaxyz.com', false);
        $response->assertSee('Activo', false);
    }
}
