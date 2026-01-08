<?php

namespace App\Observers;

use App\Models\Conductor;

class ConductorObserver
{
    /**
     * Handle the Conductor "deleted" event.
     */
    public function deleted(Conductor $conductor): void
    {
        // Eliminar archivo de carnet si existe
        if ($conductor->ruta_carnet) {
            $rutaCompleta = storage_path('app/'.$conductor->ruta_carnet);
            if (file_exists($rutaCompleta)) {
                @unlink($rutaCompleta);
            }
        }
    }
}
