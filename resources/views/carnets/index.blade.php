<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 sm:py-8 px-4 sm:px-6">
        <!-- Header con título y botones -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Carnets</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('carnets.exportar') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Exportar</span>
                </a>
                <a href="{{ route('carnets.personalizar') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    <span>Personalizar</span>
                </a>
            </div>
        </div>

        <!-- Card principal con preview y variables -->
        <div class="bg-white dark:bg-gray-800 shadow border border-gray-200 dark:border-gray-700 rounded-lg p-4 sm:p-6">
            @if($template && $template->imagen_plantilla)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                    <!-- Columna izquierda: Preview de la plantilla -->
                    <div class="lg:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Vista previa de la plantilla</h3>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-2 sm:p-4 inline-block max-w-full">
                            @php
                                $extension = strtolower(pathinfo($template->imagen_plantilla, PATHINFO_EXTENSION));
                                $imagePath = public_path($template->imagen_plantilla);
                            @endphp
                            @if($extension === 'svg' && file_exists($imagePath))
                                @php
                                    $svgContent = file_get_contents($imagePath);
                                    $svgEncoded = 'data:image/svg+xml;base64,' . base64_encode($svgContent);
                                @endphp
                                <img src="{{ $svgEncoded }}" 
                                     alt="Plantilla de carnet" 
                                     class="max-w-full h-auto rounded"
                                     style="max-height: 400px;">
                            @else
                                <img src="{{ asset($template->imagen_plantilla) }}" 
                                     alt="Plantilla de carnet" 
                                     class="max-w-full h-auto rounded"
                                     style="max-height: 400px;">
                            @endif
                        </div>
                    </div>

                    <!-- Columna derecha: Variables configuradas -->
                    @if($template->variables_config)
                        <div class="lg:col-span-1">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Variables configuradas</h3>
                            <div class="space-y-2">
                                @foreach($template->variables_config as $var => $config)
                                    @if(isset($config['activo']) && $config['activo'])
                                        <div class="flex items-center space-x-2 bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded border border-gray-200 dark:border-gray-600">
                                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="text-sm text-gray-700 dark:text-gray-300 font-mono">{{ $var }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-12 text-gray-700 dark:text-gray-300">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-4">No hay plantilla configurada. Haz clic en "Personalizar" para crear una.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
