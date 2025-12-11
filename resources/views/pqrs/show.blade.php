@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $textMuted = $isDark ? 'text-gray-400' : 'text-gray-600';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            PQRS #{{ $pqr->id }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-6">
        <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Fecha:</strong> {{ $pqr->fecha->format('d/m/Y') }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Nombre:</strong> {{ $pqr->nombre }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Vehículo:</strong> {{ $pqr->vehiculo_placa ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Número de Tiquete:</strong> {{ $pqr->numero_tiquete ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Tipo:</strong> 
                        <span class="px-2 py-1 text-xs rounded-full {{ $isDark ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800' }}">
                            {{ $pqr->tipo }}
                        </span>
                    </p>
                </div>
                <div class="space-y-3">
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Correo:</strong> {{ $pqr->correo_electronico ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Teléfono:</strong> {{ $pqr->numero_telefono ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Calificación:</strong> 
                        @if($pqr->calificacion)
                            <div class="flex items-center mt-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-5 h-5 {{ $i <= $pqr->calificacion ? 'text-yellow-400 fill-current' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @endfor
                                <span class="ml-2">({{ $pqr->calificacion }}/5)</span>
                            </div>
                        @else
                            <span class="{{ $textMuted }}">-</span>
                        @endif
                    </p>
                </div>
            </div>

            @if($pqr->comentarios)
            <div class="pt-4 border-t {{ $borderCard }}">
                <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Comentarios:</strong></p>
                <p class="{{ $textTitle }} mt-2 whitespace-pre-wrap">{{ $pqr->comentarios }}</p>
            </div>
            @endif

            @if($pqr->adjuntos && count($pqr->adjuntos) > 0)
            <div class="pt-4 border-t {{ $borderCard }}">
                <p class="{{ $textMuted }} mb-3"><strong class="{{ $textTitle }}">Adjuntos:</strong></p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($pqr->adjuntos as $index => $adjunto)
                        @php
                            $ext = strtolower(pathinfo($adjunto, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                            $isVideo = in_array($ext, ['mp4', 'avi', 'mov']);
                            $url = asset($adjunto);
                        @endphp
                        <div class="relative">
                            @if($isImage)
                                <a href="{{ $url }}" target="_blank">
                                    <img src="{{ $url }}" alt="Adjunto {{ $index + 1 }}" class="w-full h-32 object-cover rounded border {{ $borderCard }}">
                                </a>
                            @elseif($isVideo)
                                <video src="{{ $url }}" class="w-full h-32 object-cover rounded border {{ $borderCard }}" controls></video>
                            @else
                                <a href="{{ $url }}" target="_blank" class="block p-4 border {{ $borderCard }} rounded text-center {{ $textTitle }} hover:bg-gray-100">
                                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="text-xs">Documento</span>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="flex gap-3 pt-4 border-t {{ $borderCard }}">
                <a href="{{ route('pqrs.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm">Regresar</a>
                <a href="{{ route('pqrs.edit', $pqr) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm">Editar</a>
            </div>
        </div>
    </div>
</x-app-layout>
