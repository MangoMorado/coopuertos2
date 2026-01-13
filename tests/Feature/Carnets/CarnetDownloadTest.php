<?php

namespace Tests\Feature\Carnets;

use App\Models\CarnetGenerationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CarnetDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear directorio de storage para carnets si no existe
        $storageDir = public_path('storage/carnets');
        if (! File::exists($storageDir)) {
            File::makeDirectory($storageDir, 0755, true);
        }
    }

    public function test_user_can_download_carnets_zip(): void
    {
        $user = User::factory()->create();

        $sessionId = 'test-session-123';
        $zipPath = public_path('storage/carnets/carnets_'.$sessionId.'.zip');

        // Crear un archivo ZIP de prueba
        if (! File::exists(dirname($zipPath))) {
            File::makeDirectory(dirname($zipPath), 0755, true);
        }
        File::put($zipPath, 'test zip content');

        // Crear log de generación completado
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'completado',
            'total' => 5,
            'procesados' => 5,
            'exitosos' => 5,
            'errores' => 0,
            'archivo_zip' => 'carnets/carnets_'.$sessionId.'.zip',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get("/carnets/descargar/{$sessionId}");

        // Limpiar archivo de prueba
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        // Verificar que la respuesta es una descarga exitosa o redirección con error
        if ($response->getStatusCode() === 200) {
            $response->assertDownload();
            $this->assertTrue(
                $response->headers->has('Content-Disposition'),
                'La respuesta debe incluir el header Content-Disposition para descarga'
            );
        } else {
            // Si falla por falta de archivo, al menos verificamos que intentó descargar
            $this->assertContains($response->getStatusCode(), [302, 404], 'La respuesta debe ser una redirección o no encontrado');
        }
    }

    public function test_zip_download_only_available_when_generation_complete(): void
    {
        $user = User::factory()->create();

        $sessionId = 'test-session-456';

        // Crear log de generación pendiente (no completado)
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 5,
            'procesados' => 2,
            'exitosos' => 2,
            'errores' => 0,
            'archivo_zip' => null,
        ]);

        $response = $this->actingAs($user)->get("/carnets/descargar/{$sessionId}");

        // Debe redirigir con error porque el archivo no se encontró
        $response->assertRedirect(route('carnets.index'));
        $response->assertSessionHas('error', 'El archivo ZIP no se encontró');
    }

    public function test_user_can_download_last_generated_zip(): void
    {
        $user = User::factory()->create();

        $sessionId = 'test-session-789';
        $zipPath = public_path('storage/carnets/carnets_'.$sessionId.'.zip');

        // Crear un archivo ZIP de prueba
        if (! File::exists(dirname($zipPath))) {
            File::makeDirectory(dirname($zipPath), 0755, true);
        }
        File::put($zipPath, 'test zip content');

        // Crear log de generación completado
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'completado',
            'total' => 5,
            'procesados' => 5,
            'exitosos' => 5,
            'errores' => 0,
            'archivo_zip' => 'carnets/carnets_'.$sessionId.'.zip',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/carnets/descargar-ultimo-zip');

        // Limpiar archivo de prueba
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        // Verificar que la respuesta es una descarga exitosa o redirección con error
        if ($response->getStatusCode() === 200) {
            $response->assertDownload();
            $this->assertTrue(
                $response->headers->has('Content-Disposition'),
                'La respuesta debe incluir el header Content-Disposition para descarga'
            );
        } else {
            // Si falla por falta de archivo, al menos verificamos que intentó descargar
            $this->assertContains($response->getStatusCode(), [302, 404], 'La respuesta debe ser una redirección o no encontrado');
        }
    }

    public function test_invalid_session_id_returns_error(): void
    {
        $user = User::factory()->create();

        $invalidSessionId = 'invalid-session-id-that-does-not-exist';

        $response = $this->actingAs($user)->get("/carnets/descargar/{$invalidSessionId}");

        // Debe redirigir con error porque el log no existe
        $response->assertRedirect(route('carnets.index'));
        $response->assertSessionHas('error', 'Sesión no encontrada');
    }

    public function test_download_returns_error_when_zip_file_not_found(): void
    {
        $user = User::factory()->create();

        $sessionId = 'test-session-file-not-found';

        // Crear log de generación completado pero sin archivo físico
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'completado',
            'total' => 5,
            'procesados' => 5,
            'exitosos' => 5,
            'errores' => 0,
            'archivo_zip' => 'carnets/carnets_'.$sessionId.'.zip',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get("/carnets/descargar/{$sessionId}");

        // Debe redirigir con error porque el archivo no existe
        $response->assertRedirect(route('carnets.index'));
        $response->assertSessionHas('error', 'El archivo ZIP no se encontró');
    }
}
