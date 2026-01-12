<?php

namespace Tests\Feature\Jobs;

use App\Jobs\FinalizarGeneracionCarnets;
use App\Models\CarnetGenerationLog;
use App\Models\Conductor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FinalizarGeneracionCarnetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear directorios necesarios
        $zipDir = public_path('storage/carnets');
        File::ensureDirectoryExists($zipDir);
    }

    protected function tearDown(): void
    {
        // Limpiar archivos de prueba
        $zipDir = public_path('storage/carnets');
        if (File::exists($zipDir)) {
            File::cleanDirectory($zipDir);
        }

        $storageDir = storage_path('app/carnets');
        if (File::exists($storageDir)) {
            File::cleanDirectory($storageDir);
        }

        parent::tearDown();
    }

    protected function createTestCarnetPdf(string $path, string $cedula): void
    {
        File::ensureDirectoryExists(dirname($path));
        // Crear un archivo PDF de prueba simple
        $pdfContent = '%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< /Length 44 >>
stream
BT
/F1 12 Tf
100 700 Td
(Carnet '.$cedula.') Tj
ET
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000307 00000 n 
0000000397 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
503
%%EOF';
        File::put($path, $pdfContent);
    }

    public function test_finalizar_generacion_carnets_creates_zip(): void
    {
        $user = User::factory()->create();
        $sessionId = 'test-session-zip-'.time();

        // Crear log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 2,
            'procesados' => 2,
            'exitosos' => 2,
            'errores' => 0,
        ]);

        // Crear conductores con carnets
        $conductor1 = Conductor::factory()->create([
            'cedula' => '1234567890',
            'ruta_carnet' => 'carnets/test_1234567890.pdf',
        ]);

        $conductor2 = Conductor::factory()->create([
            'cedula' => '0987654321',
            'ruta_carnet' => 'carnets/test_0987654321.pdf',
        ]);

        // Crear archivos PDF de prueba
        $this->createTestCarnetPdf(storage_path('app/carnets/test_1234567890.pdf'), '1234567890');
        $this->createTestCarnetPdf(storage_path('app/carnets/test_0987654321.pdf'), '0987654321');

        // Ejecutar el job
        $job = new FinalizarGeneracionCarnets($sessionId);
        $job->handle();

        // Verificar que se creó el ZIP
        $zipPath = public_path('storage/carnets/carnets_'.$sessionId.'.zip');
        $this->assertFileExists($zipPath, 'El archivo ZIP debe existir');

        // Verificar que el ZIP tiene contenido
        $this->assertGreaterThan(0, filesize($zipPath), 'El ZIP debe tener contenido');

        // Verificar que es un ZIP válido (contiene el header PK)
        $handle = fopen($zipPath, 'r');
        $header = fread($handle, 4);
        fclose($handle);
        $this->assertEquals('PK', substr($header, 0, 2), 'El archivo debe ser un ZIP válido');
    }

    public function test_finalizar_generacion_carnets_updates_log_status(): void
    {
        $user = User::factory()->create();
        $sessionId = 'test-session-status-'.time();

        // Crear log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 1,
            'procesados' => 1,
            'exitosos' => 1,
            'errores' => 0,
        ]);

        // Crear conductor con carnet
        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'ruta_carnet' => 'carnets/test_1234567890.pdf',
        ]);

        // Crear archivo PDF de prueba
        $this->createTestCarnetPdf(storage_path('app/carnets/test_1234567890.pdf'), '1234567890');

        // Ejecutar el job
        $job = new FinalizarGeneracionCarnets($sessionId);
        $job->handle();

        // Verificar que el log se actualizó
        $log->refresh();
        $this->assertEquals('completado', $log->estado, 'El estado debe ser completado');
        $this->assertNotNull($log->completed_at, 'Debe tener fecha de completado');
        $this->assertEquals('carnets/carnets_'.$sessionId.'.zip', $log->archivo_zip, 'Debe tener la ruta del ZIP');
        $this->assertStringContainsString('Generación completada', $log->mensaje, 'El mensaje debe indicar completado');
    }

    public function test_finalizar_generacion_carnets_includes_all_carnets(): void
    {
        $user = User::factory()->create();
        $sessionId = 'test-session-all-'.time();

        // Crear log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 3,
            'procesados' => 3,
            'exitosos' => 3,
            'errores' => 0,
        ]);

        // Crear conductores con carnets
        $conductores = [];
        for ($i = 1; $i <= 3; $i++) {
            $cedula = "123456789{$i}";
            $conductor = Conductor::factory()->create([
                'cedula' => $cedula,
                'ruta_carnet' => "carnets/test_{$cedula}.pdf",
            ]);
            $conductores[] = $conductor;
            $this->createTestCarnetPdf(storage_path("app/carnets/test_{$cedula}.pdf"), $cedula);
        }

        // Ejecutar el job
        $job = new FinalizarGeneracionCarnets($sessionId);
        $job->handle();

        // Verificar que el ZIP contiene todos los archivos
        $zipPath = public_path('storage/carnets/carnets_'.$sessionId.'.zip');
        $this->assertFileExists($zipPath);

        // Abrir el ZIP y verificar contenido
        $zip = new \ZipArchive;
        if ($zip->open($zipPath) === true) {
            $this->assertEquals(3, $zip->numFiles, 'El ZIP debe contener 3 archivos');

            // Verificar que cada conductor tiene su carnet en el ZIP
            $filesInZip = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filesInZip[] = $zip->getNameIndex($i);
            }

            foreach ($conductores as $conductor) {
                $expectedFileName = 'carnet_'.$conductor->cedula.'.pdf';
                $this->assertContains($expectedFileName, $filesInZip, "El ZIP debe contener el carnet de {$conductor->cedula}");
            }

            $zip->close();
        } else {
            $this->fail('No se pudo abrir el ZIP para verificar su contenido');
        }
    }

    public function test_finalizar_generacion_carnets_handles_errors(): void
    {
        $user = User::factory()->create();
        $sessionId = 'test-session-error-'.time();

        // Crear log de generación
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 1,
            'procesados' => 1,
            'exitosos' => 1,
            'errores' => 0,
        ]);

        // No crear conductores con carnets (esto causará error)

        // Ejecutar el job (debe lanzar excepción)
        $job = new FinalizarGeneracionCarnets($sessionId);

        try {
            $job->handle();
            $this->fail('Se esperaba una excepción cuando no hay carnets generados');
        } catch (\Exception $e) {
            $this->assertStringContainsString('No hay carnets generados', $e->getMessage());
        }

        // Verificar que el log se actualizó con el error
        $log->refresh();
        $this->assertEquals('error', $log->estado, 'El estado debe ser error');
        $this->assertNotNull($log->error, 'Debe tener un mensaje de error');
        $this->assertNotNull($log->completed_at, 'Debe tener fecha de completado');
        $this->assertStringContainsString('Error al finalizar', $log->mensaje, 'El mensaje debe indicar error');
    }

    public function test_finalizar_generacion_carnets_cleans_temp_files(): void
    {
        $user = User::factory()->create();
        $zipDir = public_path('storage/carnets');

        // Crear 5 ZIPs viejos (simular tiempo de modificación)
        $oldZips = [];
        for ($i = 1; $i <= 5; $i++) {
            $zipPath = $zipDir.'/old_zip_'.$i.'.zip';
            $zip = new \ZipArchive;
            if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
                $zip->addFromString('test.txt', 'test content '.$i);
                $zip->close();

                // Modificar tiempo de modificación para simular archivos viejos
                touch($zipPath, time() - (3600 * $i)); // Hacer que sean más viejos progresivamente
                $oldZips[] = $zipPath;
            }
        }

        // Crear un log de generación nuevo
        $sessionId = 'test-session-clean-'.time();
        $log = CarnetGenerationLog::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'tipo' => 'masivo',
            'estado' => 'procesando',
            'total' => 1,
            'procesados' => 1,
            'exitosos' => 1,
            'errores' => 0,
        ]);

        // Crear conductor con carnet
        $conductor = Conductor::factory()->create([
            'cedula' => '1234567890',
            'ruta_carnet' => 'carnets/test_1234567890.pdf',
        ]);

        $this->createTestCarnetPdf(storage_path('app/carnets/test_1234567890.pdf'), '1234567890');

        // Ejecutar el job (esto debe limpiar ZIPs viejos)
        $job = new FinalizarGeneracionCarnets($sessionId);
        $job->handle();

        // Verificar que solo quedan los 2 ZIPs más recientes (el nuevo + 1 viejo)
        $remainingZips = collect(File::files($zipDir))
            ->filter(function ($file) {
                return strtolower($file->getExtension()) === 'zip';
            })
            ->count();

        // Debe haber como máximo 2 ZIPs (el nuevo más 1 viejo, o 2 viejos si el nuevo no se creó)
        $this->assertLessThanOrEqual(2, $remainingZips, 'Debe mantener solo los 2 ZIPs más recientes');

        // Verificar que el nuevo ZIP existe
        $newZipPath = $zipDir.'/carnets_'.$sessionId.'.zip';
        $this->assertFileExists($newZipPath, 'El nuevo ZIP debe existir');
    }

    public function test_finalizar_generacion_carnets_returns_early_when_log_not_found(): void
    {
        $sessionId = 'non-existent-session-'.time();

        // No crear log

        // Ejecutar el job (no debe lanzar excepción, solo retornar temprano)
        $job = new FinalizarGeneracionCarnets($sessionId);

        try {
            $job->handle();
            // Si no lanza excepción, está bien
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Si lanza excepción, debe ser por otra razón, no porque el log no existe
            $this->fail('No debe lanzar excepción cuando el log no existe, solo retornar temprano');
        }

        // No debe crear ningún ZIP
        $zipPath = public_path('storage/carnets/carnets_'.$sessionId.'.zip');
        $this->assertFileDoesNotExist($zipPath, 'No debe crear ZIP cuando el log no existe');
    }
}
