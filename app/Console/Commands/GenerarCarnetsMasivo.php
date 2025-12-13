<?php

namespace App\Console\Commands;

use App\Http\Controllers\CarnetController;
use App\Models\CarnetTemplate;
use App\Models\Conductor;
use Illuminate\Console\Command;

class GenerarCarnetsMasivo extends Command
{
    protected $signature = 'carnet:generar-masivo {sessionId}';
    protected $description = 'Genera carnets en masa para una sesión';

    public function handle()
    {
        $sessionId = $this->argument('sessionId');
        $download = \App\Models\CarnetDownload::where('session_id', $sessionId)->first();
        
        if (!$download) {
            $this->error('Sesión no encontrada');
            return 1;
        }
        
        $template = CarnetTemplate::where('activo', true)->first();
        if (!$template) {
            $this->error('No hay plantilla activa');
            return 1;
        }
        
        $conductores = Conductor::all();
        
        $controller = new CarnetController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('procesarCarnets');
        $method->setAccessible(true);
        $method->invoke($controller, $sessionId, $template, $conductores);
        
        $this->info('Procesamiento completado');
        return 0;
    }
}
