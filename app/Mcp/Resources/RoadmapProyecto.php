<?php

namespace App\Mcp\Resources;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Server\Resource;

class RoadmapProyecto extends Resource
{
    protected string $uri = 'coopuertos://roadmap';

    protected string $mimeType = 'text/markdown';

    /**
     * Get the resource's name.
     */
    public function name(): string
    {
        return 'Roadmap del Proyecto Coopuertos';
    }

    /**
     * Get the resource's title.
     */
    public function title(): string
    {
        return 'Roadmap del Proyecto Coopuertos';
    }

    /**
     * Get the resource's description.
     */
    public function description(): string
    {
        return 'Roadmap completo del proyecto Coopuertos con todas las fases de desarrollo y funcionalidades planificadas.';
    }

    /**
     * Handle the resource request.
     */
    public function handle(\Laravel\Mcp\Request $request): \Laravel\Mcp\Response
    {
        $roadmapPath = base_path('roadmap.md');

        if (File::exists($roadmapPath)) {
            return \Laravel\Mcp\Response::text(File::get($roadmapPath));
        }

        return \Laravel\Mcp\Response::text('# Roadmap del Proyecto Coopuertos\n\nRoadmap no disponible.');
    }
}
