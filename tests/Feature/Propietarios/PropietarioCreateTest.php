<?php

namespace Tests\Feature\Propietarios;

use App\Models\Propietario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropietarioCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_view_create_propietario_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/propietarios/create');

        $response->assertStatus(200);
        $response->assertViewIs('propietarios.create');
    }

    public function test_user_can_create_propietario_with_valid_data(): void
    {
        $user = User::factory()->create();

        $propietarioData = [
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'direccion_contacto' => 'Calle 123 #45-67',
            'telefono_contacto' => '3001234567',
            'correo_electronico' => 'juan@example.com',
            'estado' => 'Activo',
        ];

        $response = $this->actingAs($user)->post('/propietarios', $propietarioData);

        $response->assertRedirect(route('propietarios.index'));
        $response->assertSessionHas('success', 'Propietario creado correctamente.');

        $this->assertDatabaseHas('propietarios', [
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'tipo_propietario' => 'Persona Natural',
        ]);
    }

    public function test_user_cannot_create_propietario_without_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/propietarios', []);

        $response->assertSessionHasErrors([
            'tipo_identificacion',
            'numero_identificacion',
            'nombre_completo',
            'tipo_propietario',
            'estado',
        ]);

        // Verificar que no se creó ningún propietario
        $this->assertDatabaseCount('propietarios', 0);
    }

    public function test_user_cannot_create_propietario_with_duplicate_numero_identificacion(): void
    {
        $user = User::factory()->create();

        // Crear un propietario existente
        Propietario::factory()->create(['numero_identificacion' => '1234567890']);

        $propietarioData = [
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890', // Número duplicado
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ];

        $response = $this->actingAs($user)->post('/propietarios', $propietarioData);

        $response->assertSessionHasErrors(['numero_identificacion']);
        $this->assertDatabaseCount('propietarios', 1);
    }

    public function test_user_can_create_propietario_with_minimal_data(): void
    {
        $user = User::factory()->create();

        $propietarioData = [
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
            // Campos opcionales omitidos
        ];

        $response = $this->actingAs($user)->post('/propietarios', $propietarioData);

        $response->assertRedirect(route('propietarios.index'));
        $response->assertSessionHas('success');

        $propietario = Propietario::where('numero_identificacion', '1234567890')->first();
        $this->assertNotNull($propietario);
        $this->assertNull($propietario->direccion_contacto);
        $this->assertNull($propietario->telefono_contacto);
        $this->assertNull($propietario->correo_electronico);
    }

    public function test_user_can_create_propietario_as_persona_juridica(): void
    {
        $user = User::factory()->create();

        $propietarioData = [
            'tipo_identificacion' => 'RUC/NIT',
            'numero_identificacion' => '9001234561',
            'nombre_completo' => 'Empresa S.A.S',
            'tipo_propietario' => 'Persona Jurídica',
            'direccion_contacto' => 'Calle Principal 123',
            'telefono_contacto' => '6012345678',
            'correo_electronico' => 'contacto@empresa.com',
            'estado' => 'Activo',
        ];

        $response = $this->actingAs($user)->post('/propietarios', $propietarioData);

        $response->assertRedirect(route('propietarios.index'));

        $this->assertDatabaseHas('propietarios', [
            'numero_identificacion' => '9001234561',
            'nombre_completo' => 'Empresa S.A.S',
            'tipo_identificacion' => 'RUC/NIT',
            'tipo_propietario' => 'Persona Jurídica',
        ]);
    }

    public function test_user_can_create_propietario_with_invalid_email_returns_error(): void
    {
        $user = User::factory()->create();

        $propietarioData = [
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'correo_electronico' => 'correo-invalido', // Email inválido
            'estado' => 'Activo',
        ];

        $response = $this->actingAs($user)->post('/propietarios', $propietarioData);

        $response->assertSessionHasErrors(['correo_electronico']);
    }

    public function test_propietario_created_successfully_redirects_to_index(): void
    {
        $user = User::factory()->create();

        $propietarioData = [
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ];

        $response = $this->actingAs($user)->post('/propietarios', $propietarioData);

        $response->assertRedirect(route('propietarios.index'));
        $response->assertSessionHas('success', 'Propietario creado correctamente.');
    }

    public function test_user_can_create_propietario_with_all_fields(): void
    {
        $user = User::factory()->create();

        $propietarioData = [
            'tipo_identificacion' => 'Pasaporte',
            'numero_identificacion' => '123456789',
            'nombre_completo' => 'María González',
            'tipo_propietario' => 'Persona Natural',
            'direccion_contacto' => 'Calle 123 #45-67, Barrio Centro',
            'telefono_contacto' => '573001234567',
            'correo_electronico' => 'maria.gonzalez@example.com',
            'estado' => 'Inactivo',
        ];

        $response = $this->actingAs($user)->post('/propietarios', $propietarioData);

        $response->assertRedirect(route('propietarios.index'));

        $propietario = Propietario::where('numero_identificacion', '123456789')->first();
        $this->assertNotNull($propietario);
        $this->assertEquals('Pasaporte', $propietario->tipo_identificacion);
        $this->assertEquals('María González', $propietario->nombre_completo);
        $this->assertEquals('Calle 123 #45-67, Barrio Centro', $propietario->direccion_contacto);
        $this->assertEquals('573001234567', $propietario->telefono_contacto);
        $this->assertEquals('maria.gonzalez@example.com', $propietario->correo_electronico);
        $this->assertEquals('Inactivo', $propietario->estado);
    }

    public function test_user_cannot_create_propietario_with_non_numeric_identificacion(): void
    {
        $user = User::factory()->create();

        $propietarioData = [
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => 'ABC123456', // Contiene letras
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ];

        $response = $this->actingAs($user)->post('/propietarios', $propietarioData);

        $response->assertSessionHasErrors(['numero_identificacion']);
        $this->assertDatabaseMissing('propietarios', ['numero_identificacion' => 'ABC123456']);
    }

    public function test_user_cannot_create_propietario_with_non_numeric_telefono(): void
    {
        $user = User::factory()->create();

        $propietarioData = [
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'telefono_contacto' => '+57 300 123 4567', // Contiene espacios y símbolos
            'estado' => 'Activo',
        ];

        $response = $this->actingAs($user)->post('/propietarios', $propietarioData);

        $response->assertSessionHasErrors(['telefono_contacto']);
        $this->assertDatabaseMissing('propietarios', ['numero_identificacion' => '1234567890']);
    }

    public function test_user_can_create_propietario_with_numeric_only_fields(): void
    {
        $user = User::factory()->create();

        $propietarioData = [
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'telefono_contacto' => '3001234567',
            'estado' => 'Activo',
        ];

        $response = $this->actingAs($user)->post('/propietarios', $propietarioData);

        $response->assertRedirect(route('propietarios.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('propietarios', [
            'numero_identificacion' => '1234567890',
            'telefono_contacto' => '3001234567',
        ]);
    }
}
