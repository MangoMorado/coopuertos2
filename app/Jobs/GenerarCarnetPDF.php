<?php

namespace App\Jobs;

use App\Models\Conductor;
use App\Models\CarnetTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class GenerarCarnetPDF implements ShouldQueue
{
    use Queueable;

    public $conductorId;
    public $sessionId;
    public $templatePath;
    public $variablesConfig;
    public $tempDir;

    /**
     * Create a new job instance.
     */
    public function __construct($conductorId, $sessionId, $templatePath, $variablesConfig, $tempDir)
    {
        $this->conductorId = $conductorId;
        $this->sessionId = $sessionId;
        $this->templatePath = $templatePath;
        $this->variablesConfig = $variablesConfig;
        $this->tempDir = $tempDir;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $conductor = Conductor::with(['asignacionActiva.vehicle'])->find($this->conductorId);
        
        if (!$conductor) {
            return;
        }

        // Generar el PDF del carnet (implementación simplificada)
        // En producción, aquí se generaría el PDF usando la misma lógica que en show.blade.php
        $filename = 'carnet_' . $conductor->cedula . '.pdf';
        $filePath = $this->tempDir . '/' . $filename;
        
        // Por ahora, crear un archivo placeholder
        // En producción, usarías una librería de PDF para generar el carnet completo
        File::put($filePath, 'PDF placeholder para ' . $conductor->nombres);
        
        // Actualizar progreso
        $this->actualizarProgreso();
    }

    protected function actualizarProgreso()
    {
        $download = \App\Models\CarnetDownload::where('session_id', $this->sessionId)->first();
        if ($download) {
            $download->increment('procesados');
            if ($download->procesados >= $download->total) {
                $download->estado = 'completado';
                $download->save();
            }
        }
    }
}
