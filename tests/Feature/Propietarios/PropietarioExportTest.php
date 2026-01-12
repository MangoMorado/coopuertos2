<?php

namespace Tests\Feature\Propietarios;

use App\Exports\PropietariosExport;
use App\Models\Propietario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropietarioExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_export_propietarios_to_excel(): void
    {
        $user = User::factory()->create();

        // Crear algunos propietarios de prueba
        Propietario::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/propietarios/exportar');

        $response->assertStatus(200);

        // Verificar que la respuesta es una descarga de archivo
        $this->assertTrue(
            $response->headers->has('Content-Disposition'),
            'La respuesta debe incluir el header Content-Disposition para descarga'
        );

        // Verificar que el tipo de contenido es Excel
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('spreadsheet', $contentType ?? '');
    }

    public function test_exported_excel_contains_all_propietarios(): void
    {
        $user = User::factory()->create();

        // Crear propietarios de prueba con datos específicos
        $propietario1 = Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Activo',
        ]);

        $propietario2 = Propietario::create([
            'tipo_identificacion' => 'RUC/NIT',
            'numero_identificacion' => '0987654321001',
            'nombre_completo' => 'Empresa XYZ S.A.',
            'tipo_propietario' => 'Persona Jurídica',
            'estado' => 'Activo',
        ]);

        $propietario3 = Propietario::create([
            'tipo_identificacion' => 'Pasaporte',
            'numero_identificacion' => 'P123456789',
            'nombre_completo' => 'María González',
            'tipo_propietario' => 'Persona Natural',
            'estado' => 'Inactivo',
        ]);

        // Verificar que todos los propietarios existen en la base de datos
        $this->assertDatabaseHas('propietarios', ['numero_identificacion' => '1234567890']);
        $this->assertDatabaseHas('propietarios', ['numero_identificacion' => '0987654321001']);
        $this->assertDatabaseHas('propietarios', ['numero_identificacion' => 'P123456789']);

        // Verificar que el export contiene todos los propietarios
        $export = new PropietariosExport;
        $collection = $export->collection();

        $this->assertGreaterThanOrEqual(3, $collection->count());

        // Verificar que cada propietario está en la colección
        $numerosIdentificacion = $collection->pluck('numero_identificacion')->toArray();
        $this->assertContains('1234567890', $numerosIdentificacion);
        $this->assertContains('0987654321001', $numerosIdentificacion);
        $this->assertContains('P123456789', $numerosIdentificacion);
    }

    public function test_exported_excel_has_correct_columns(): void
    {
        $user = User::factory()->create();

        // Crear un propietario de prueba
        Propietario::create([
            'tipo_identificacion' => 'Cédula de Ciudadanía',
            'numero_identificacion' => '1234567890',
            'nombre_completo' => 'Juan Pérez',
            'tipo_propietario' => 'Persona Natural',
            'direccion_contacto' => 'Av. Principal 123',
            'telefono_contacto' => '+593 999999999',
            'correo_electronico' => 'juan@example.com',
            'estado' => 'Activo',
        ]);

        // Verificar que el export tiene las columnas correctas
        $export = new PropietariosExport;
        $headings = $export->headings();

        $expectedColumns = [
            'Tipo de Identificación',
            'Número de Identificación',
            'Nombre Completo',
            'Tipo de Propietario',
            'Dirección',
            'Teléfono',
            'Correo Electrónico',
            'Estado',
        ];

        $this->assertEquals($expectedColumns, $headings);
        $this->assertCount(8, $headings);
    }
}
