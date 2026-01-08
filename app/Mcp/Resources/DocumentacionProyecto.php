<?php

namespace App\Mcp\Resources;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Server\Resource;

class DocumentacionProyecto extends Resource
{
    protected string $uri = 'coopuertos://documentacion';

    protected string $mimeType = 'text/markdown';

    /**
     * Get the resource's name.
     */
    public function name(): string
    {
        return 'Documentación del Proyecto Coopuertos';
    }

    /**
     * Get the resource's title.
     */
    public function title(): string
    {
        return 'Documentación del Proyecto Coopuertos';
    }

    /**
     * Get the resource's description.
     */
    public function description(): string
    {
        return 'Documentación completa del proyecto Coopuertos incluyendo README y características principales.';
    }

    /**
     * Handle the resource request.
     */
    public function handle(\Laravel\Mcp\Request $request): \Laravel\Mcp\Response
    {
        $readmePath = base_path('README.md');

        if (File::exists($readmePath)) {
            return \Laravel\Mcp\Response::text(File::get($readmePath));
        }

        return \Laravel\Mcp\Response::text('# Documentación del Proyecto Coopuertos\n\nDocumentación no disponible.');
    }
}
