<?php

namespace Tests\Feature\Carnets;

use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CarnetQrExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Asegurar que el directorio temp existe
        $tempDir = storage_path('app/temp');
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Limpiar archivos temporales después de cada test
        $tempDir = storage_path('app/temp');
        if (File::exists($tempDir)) {
            $files = File::allFiles($tempDir);
            foreach ($files as $file) {
                if (str_ends_with($file->getFilename(), '.zip')) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    public function test_user_can_export_qrs_as_zip(): void
    {
        $user = User::factory()->create();

        // Crear algunos conductores de prueba
        $conductor1 = Conductor::factory()->create([
            'cedula' => '1234567890',
            'uuid' => 'test-uuid-1',
        ]);
        $conductor2 = Conductor::factory()->create([
            'cedula' => '0987654321',
            'uuid' => 'test-uuid-2',
        ]);
        $conductor3 = Conductor::factory()->create([
            'cedula' => '1122334455',
            'uuid' => 'test-uuid-3',
        ]);

        $response = $this->actingAs($user)->get('/carnets/exportar-qrs');

        // Verificar que la respuesta es una descarga exitosa
        $response->assertStatus(200);
        $response->assertDownload();
        $this->assertTrue(
            $response->headers->has('Content-Disposition'),
            'La respuesta debe incluir el header Content-Disposition para descarga'
        );

        // Verificar que el nombre del archivo es correcto
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('qrs_conductores_', $contentDisposition);
        $this->assertStringContainsString('.zip', $contentDisposition);
    }

    public function test_export_qrs_returns_error_when_no_conductores(): void
    {
        $user = User::factory()->create();

        // No crear conductores

        $response = $this->actingAs($user)->get('/carnets/exportar-qrs');

        // Debe redirigir con error porque no hay conductores
        $response->assertRedirect(route('carnets.exportar'));
        $response->assertSessionHas('error', 'No hay conductores para exportar QRs.');
    }

    public function test_export_qrs_requires_authentication(): void
    {
        // No autenticar usuario

        $response = $this->get('/carnets/exportar-qrs');

        // Debe redirigir al login
        $response->assertRedirect(route('login'));
    }
}
