<?php

namespace Tests\Feature\Carnets;

use App\Models\CarnetGenerationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarnetExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_export_carnets_log(): void
    {
        $user = User::factory()->create();

        // Crear un log de generación completado
        $log = CarnetGenerationLog::create([
            'session_id' => 'test-session-123',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'completado',
            'total' => 10,
            'procesados' => 10,
            'exitosos' => 10,
            'errores' => 0,
            'mensaje' => 'Proceso completado exitosamente',
            'archivo_zip' => 'carnets/test.zip',
            'completed_at' => now(),
            'logs' => [
                [
                    'timestamp' => now()->toDateTimeString(),
                    'tipo' => 'info',
                    'mensaje' => 'Generación iniciada',
                ],
                [
                    'timestamp' => now()->toDateTimeString(),
                    'tipo' => 'success',
                    'mensaje' => 'Proceso completado',
                ],
            ],
        ]);

        // Acceder a la vista de exportar con el session_id
        $response = $this->actingAs($user)->get('/carnets/exportar?session_id='.$log->session_id);

        $response->assertStatus(200);
        $response->assertViewIs('carnets.exportar');
        $response->assertViewHas('sessionId', $log->session_id);

        // Verificar que la vista muestra información del log
        $response->assertSee('Log de Eventos', false);
    }

    public function test_user_can_export_carnets_log_without_session_shows_last_completed(): void
    {
        $user = User::factory()->create();

        // Crear un log de generación completado (el más reciente)
        $ultimoLog = CarnetGenerationLog::create([
            'session_id' => 'test-session-latest',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'completado',
            'total' => 5,
            'procesados' => 5,
            'exitosos' => 5,
            'errores' => 0,
            'mensaje' => 'Proceso completado',
            'archivo_zip' => 'carnets/latest.zip',
            'completed_at' => now(),
        ]);

        // Crear otro log más antiguo
        CarnetGenerationLog::create([
            'session_id' => 'test-session-old',
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'completado',
            'total' => 3,
            'procesados' => 3,
            'exitosos' => 3,
            'errores' => 0,
            'archivo_zip' => 'carnets/old.zip',
            'completed_at' => now()->subDay(),
        ]);

        // Acceder a la vista de exportar sin session_id (debe mostrar el último completado)
        $response = $this->actingAs($user)->get('/carnets/exportar');

        $response->assertStatus(200);
        $response->assertViewIs('carnets.exportar');
        $response->assertViewHas('sessionId', $ultimoLog->session_id);

        // Verificar que la vista muestra información del log más reciente
        $response->assertSee('Log de Eventos', false);
    }
}
