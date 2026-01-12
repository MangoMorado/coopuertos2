<?php

namespace Tests\Feature\Propietarios;

use App\Models\Propietario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropietarioEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_edit_propietario_form(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $response = $this->actingAs($user)->get("/propietarios/{$propietario->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('propietarios.edit');
        $response->assertViewHas('propietario');
        $this->assertEquals($propietario->id, $response->viewData('propietario')->id);
    }

    public function test_user_can_update_propietario_with_valid_data(): void
    {
        $user = User::factory()->create();

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

        $updateData = [
            'tipo_identificacion' => 'RUC/NIT',
            'numero_identificacion' => '1234567890', // Mismo número (permitido en update)
            'nombre_completo' => 'Juan Carlos Pérez',
            'tipo_propietario' => 'Persona Jurídica',
            'direccion_contacto' => 'Calle 456',
            'telefono_contacto' => '3007654321',
            'correo_electronico' => 'juan.carlos@example.com',
            'estado' => 'Inactivo',
        ];

        $response = $this->actingAs($user)->put("/propietarios/{$propietario->id}", $updateData);

        $response->assertRedirect(route('propietarios.index'));
        $response->assertSessionHas('success', 'Propietario actualizado correctamente.');

        $this->assertDatabaseHas('propietarios', [
            'id' => $propietario->id,
            'tipo_identificacion' => 'RUC/NIT',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Carlos Pérez',
            'tipo_propietario' => 'Persona Jurídica',
            'direccion_contacto' => 'Calle 456',
            'telefono_contacto' => '3007654321',
            'correo_electronico' => 'juan.carlos@example.com',
            'estado' => 'Inactivo',
        ]);
    }

    public function test_user_cannot_update_propietario_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        // Intentar actualizar con datos inválidos
        $response = $this->actingAs($user)->put("/propietarios/{$propietario->id}", [
            'tipo_identificacion' => 'Tipo Inválido',
            'numero_identificacion' => '',
            'nombre_completo' => '',
            'tipo_propietario' => 'Tipo Inválido',
            'estado' => 'Estado Inválido',
            'correo_electronico' => 'correo-invalido',
        ]);

        $response->assertSessionHasErrors([
            'tipo_identificacion',
            'numero_identificacion',
            'nombre_completo',
            'tipo_propietario',
            'estado',
            'correo_electronico',
        ]);

        // Verificar que el propietario no se actualizó con datos inválidos
        $propietario->refresh();
        $this->assertEquals('Cédula de Ciudadanía', $propietario->tipo_identificacion);
        $this->assertEquals('Juan Pérez', $propietario->nombre_completo);
        $this->assertEquals('Persona Natural', $propietario->tipo_propietario);
        $this->assertEquals('Activo', $propietario->estado);
    }

    public function test_user_cannot_update_propietario_numero_identificacion_to_existing_one(): void
    {
        $user = User::factory()->create();

        $propietario1 = Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1111111111',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $propietario2 = Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '2222222222',
            'nombre_completo' => 'María González',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        // Intentar actualizar propietario2 con el número de identificación de propietario1
        $response = $this->actingAs($user)->put("/propietarios/{$propietario2->id}", [
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1111111111', // Número de otro propietario
            'nombre_completo' => 'María González',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $response->assertSessionHasErrors(['numero_identificacion']);

        // Verificar que el número de identificación no cambió
        $propietario2->refresh();
        $this->assertEquals('2222222222', $propietario2->numero_identificacion);
    }

    public function test_user_can_update_propietario_with_same_numero_identificacion(): void
    {
        $user = User::factory()->create();

        $propietario = Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $updateData = [
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890', // Mismo número (permitido)
            'nombre_completo' => 'Juan Carlos Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ];

        $response = $this->actingAs($user)->put("/propietarios/{$propietario->id}", $updateData);

        $response->assertRedirect(route('propietarios.index'));
        $response->assertSessionHasNoErrors();

        $propietario->refresh();
        $this->assertEquals('Juan Carlos Pérez', $propietario->nombre_completo);
        $this->assertEquals('1234567890', $propietario->numero_identificacion);
    }
}
