@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Gestión de Carnets
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-6">
        @if (session('success'))
            <div class="mb-4 {{ $isDark ? 'bg-green-900 border-green-700 text-green-200' : 'bg-green-100 border-green-300 text-green-800' }} border px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif

        <!-- Información de Tamaños Recomendados -->
        <div class="mb-6 {{ $isDark ? 'bg-blue-900 border-blue-700' : 'bg-blue-50 border-blue-200' }} border rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 {{ $isDark ? 'text-blue-300' : 'text-blue-600' }} mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-semibold {{ $textTitle }} mb-2">Tamaños Recomendados para Plantilla de Carnet:</p>
                    <ul class="text-xs {{ $textBody }} space-y-1 list-disc list-inside">
                        <li><strong>Estándar (Vertical):</strong> 1011 x 638 px (300 DPI) - Tamaño tarjeta de crédito</li>
                        <li><strong>Vertical:</strong> 1080 x 720 px (300 DPI) - 90mm x 60mm</li>
                        <li><strong>Horizontal:</strong> 1200 x 750 px (300 DPI) - 100mm x 62.5mm</li>
                        <li><strong>Resolución mínima:</strong> 300 DPI para impresión de calidad</li>
                    </ul>
                    <p class="text-xs {{ $textBody }} mt-2">Formatos aceptados: JPG, PNG, GIF. Máximo 5MB</p>
                </div>
            </div>
        </div>

        <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold {{ $textTitle }}">Plantilla de Carnet</h3>
                <a href="{{ route('carnets.personalizar') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    <span>Personalizar</span>
                </a>
            </div>

            @if($template && $template->imagen_plantilla)
                <div class="mb-4">
                    <p class="text-sm {{ $textBody }} mb-2">Vista previa de la plantilla:</p>
                    <div class="border {{ $borderCard }} rounded-lg p-4 inline-block">
                        @php
                            $extension = strtolower(pathinfo($template->imagen_plantilla, PATHINFO_EXTENSION));
                            $imagePath = public_path($template->imagen_plantilla);
                        @endphp
                        @if($extension === 'svg' && file_exists($imagePath))
                            @php
                                $svgContent = file_get_contents($imagePath);
                                // Codificar SVG para uso en data URI
                                // Usar base64 para evitar problemas con caracteres especiales
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
            @else
                <div class="text-center py-12 {{ $textBody }}">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-4">No hay plantilla configurada. Haz clic en "Personalizar" para crear una.</p>
                </div>
            @endif

            @if($template && $template->variables_config)
                <div class="mt-6">
                    <h4 class="text-md font-semibold {{ $textTitle }} mb-3">Variables configuradas:</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach($template->variables_config as $var => $config)
                            @if(isset($config['activo']) && $config['activo'])
                                <div class="flex items-center space-x-2 {{ $isDark ? 'bg-gray-700' : 'bg-gray-50' }} px-3 py-2 rounded">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm {{ $textBody }}">{{ $var }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Botón para descargar todos los carnets -->
        <div class="mt-6 {{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
            <div class="flex justify-center">
                <a href="{{ route('carnets.exportar') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-md transition flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Descargar todos los carnets</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
