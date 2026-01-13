<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

/**
 * Controlador para gestionar el acceso a la documentación PHPDoc
 */
class DocumentacionController extends Controller
{
    /**
     * Mostrar la documentación HTML generada
     *
     * Si la documentación existe en docs/api, la sirve.
     * Si no existe, muestra un mensaje indicando que debe generarse.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        // Verificar que el usuario tenga rol Mango
        if (! auth()->check() || ! auth()->user()->hasRole('Mango')) {
            abort(403, 'No tienes permiso para acceder a la documentación.');
        }

        $docsPath = base_path('docs/api');
        $indexPath = $docsPath.'/index.html';

        // Verificar si la documentación existe
        if (File::exists($indexPath)) {
            // Servir el contenido HTML directamente
            $content = File::get($indexPath);
            // Reemplazar rutas relativas para que funcionen con nuestra estructura de rutas
            $baseUrl = route('documentacion.asset', ['path' => '']);
            $content = preg_replace_callback(
                '/(href|src)=["\']([^"\']+)["\']/',
                function ($matches) use ($baseUrl) {
                    $attr = $matches[1];
                    $url = $matches[2];
                    // Si es una ruta relativa (no empieza con http://, https://, /, #)
                    if (! preg_match('/^(https?:\/\/|\/|#|mailto:)/', $url)) {
                        $url = rtrim($baseUrl, '/').'/'.ltrim($url, '/');
                    }

                    return $attr.'="'.$url.'"';
                },
                $content
            );

            return response($content, 200)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        // Si no existe, mostrar vista con instrucciones
        return view('documentacion.index', [
            'generada' => false,
        ]);
    }

    /**
     * Servir archivos estáticos de la documentación (CSS, JS, imágenes, HTML, etc.)
     *
     * @param  string|null  $path  Ruta del archivo relativo a docs/api
     * @return \Illuminate\Http\Response
     */
    public function asset(?string $path = null)
    {
        // Verificar que el usuario tenga rol Mango
        if (! auth()->check() || ! auth()->user()->hasRole('Mango')) {
            abort(403, 'No tienes permiso para acceder a la documentación.');
        }

        $docsPath = base_path('docs/api');
        // Limpiar el path para prevenir path traversal
        if ($path) {
            $path = str_replace('..', '', $path);
            $path = ltrim($path, '/');
        }

        $filePath = $docsPath.($path ? '/'.$path : '/index.html');

        if (! File::exists($filePath) || ! File::isFile($filePath)) {
            abort(404);
        }

        $content = File::get($filePath);
        $mimeType = $this->getMimeType($filePath);

        return response($content, 200)
            ->header('Content-Type', $mimeType);
    }

    /**
     * Obtener el tipo MIME de un archivo
     *
     * @param  string  $filePath  Ruta del archivo
     * @return string Tipo MIME
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(File::extension($filePath));

        return match ($extension) {
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'html' => 'text/html',
            default => 'application/octet-stream',
        };
    }
}
