@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    
    // Colores según el tema
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $textSubtitle = $isDark ? 'text-gray-400' : 'text-gray-600';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
    $bgInfo = $isDark ? 'bg-blue-900 border-blue-700' : 'bg-blue-100 border-blue-300';
    $textInfo = $isDark ? 'text-blue-200' : 'text-blue-800';
@endphp

<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 sm:py-8 px-4 sm:px-6">
        <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
            <div class="mb-6">
                <h3 class="text-2xl font-bold {{ $textTitle }} mb-2">Documentación PHPDoc</h3>
                <p class="{{ $textSubtitle }}">Documentación técnica generada automáticamente a partir de los bloques PHPDoc del código fuente.</p>
            </div>

            @if($generada)
                <div class="{{ $bgInfo }} border rounded-lg p-4 mb-6">
                    <p class="{{ $textInfo }}">
                        <strong>✓ Documentación disponible:</strong> La documentación ha sido generada y está lista para consultar.
                    </p>
                </div>
            @else
                <div class="{{ $bgInfo }} border rounded-lg p-4 mb-6">
                    <p class="{{ $textInfo }} mb-4">
                        <strong>⚠ Documentación no generada:</strong> La documentación HTML aún no ha sido generada. Ejecuta el comando para generarla.
                    </p>
                    <div class="mt-4">
                        <h4 class="font-semibold {{ $textInfo }} mb-2">Para generar la documentación:</h4>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded p-4 font-mono text-sm text-gray-100">
                            <code>php artisan docs:generate</code>
                        </div>
                        <p class="{{ $textInfo }} text-sm mt-2">
                            O usando Composer:
                        </p>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded p-4 font-mono text-sm text-gray-100 mt-2">
                            <code>composer run docs:generate</code>
                        </div>
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                <div>
                    <h4 class="font-semibold {{ $textTitle }} mb-2">Información</h4>
                    <ul class="list-disc list-inside space-y-1 {{ $textBody }}">
                        <li>La documentación se genera a partir de los bloques PHPDoc en el código fuente</li>
                        <li>Incluye todas las clases, métodos, propiedades y relaciones documentadas</li>
                        <li>Se actualiza ejecutando el comando de generación</li>
                        <li>La documentación se guarda en <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">docs/api/</code></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold {{ $textTitle }} mb-2">Notas</h4>
                    <ul class="list-disc list-inside space-y-1 {{ $textBody }}">
                        <li>Esta funcionalidad requiere phpDocumentor instalado como dependencia de desarrollo</li>
                        <li>La documentación se genera en formato HTML navegable</li>
                        <li>Puedes acceder a la documentación una vez generada desde este mismo enlace</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
