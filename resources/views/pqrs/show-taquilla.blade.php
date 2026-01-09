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
            PQRS Taquilla #{{ $pqrTaquilla->id }}
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-6">
        <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Fecha:</strong> {{ $pqrTaquilla->fecha?->format('d/m/Y') ?? 'N/A' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Hora:</strong> {{ $pqrTaquilla->hora ? \Carbon\Carbon::parse($pqrTaquilla->hora)->format('H:i') : 'N/A' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Nombre:</strong> {{ $pqrTaquilla->nombre }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Sede:</strong> {{ $pqrTaquilla->sede ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Tipo:</strong> 
                        <span class="px-2 py-1 text-xs rounded-full {{ $isDark ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800' }}">
                            {{ $pqrTaquilla->tipo }}
                        </span>
                    </p>
                </div>
                <div class="space-y-3">
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Correo:</strong> {{ $pqrTaquilla->correo ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Teléfono:</strong> {{ $pqrTaquilla->telefono ?? '-' }}</p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Calificación:</strong> 
                        @if($pqrTaquilla->calificacion)
                            <div class="flex items-center mt-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-5 h-5 {{ $i <= $pqrTaquilla->calificacion ? 'text-yellow-400 fill-current' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @endfor
                                <span class="ml-2">({{ $pqrTaquilla->calificacion }}/5)</span>
                            </div>
                        @else
                            <span class="{{ $textMuted }}">-</span>
                        @endif
                    </p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Estado:</strong> 
                        <span class="px-2 py-1 text-xs rounded-full
                            @if($pqrTaquilla->estado === 'Radicada')
                                {{ $isDark ? 'bg-gray-700 text-gray-200' : 'bg-gray-100 text-gray-800' }}
                            @elseif($pqrTaquilla->estado === 'En Trámite')
                                {{ $isDark ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800' }}
                            @elseif($pqrTaquilla->estado === 'En Espera de Información')
                                {{ $isDark ? 'bg-yellow-900 text-yellow-200' : 'bg-yellow-100 text-yellow-800' }}
                            @elseif($pqrTaquilla->estado === 'Resuelta')
                                {{ $isDark ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800' }}
                            @else
                                {{ $isDark ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800' }}
                            @endif">
                            {{ $pqrTaquilla->estado ?? 'Radicada' }}
                        </span>
                    </p>
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Usuario Asignado:</strong> 
                        @if($pqrTaquilla->usuarioAsignado)
                            {{ $pqrTaquilla->usuarioAsignado->name }}
                            <span class="text-xs {{ $isDark ? 'text-gray-400' : 'text-gray-500' }}">
                                ({{ $pqrTaquilla->usuarioAsignado->email }})
                            </span>
                        @else
                            <span class="{{ $textMuted }}">Sin asignar</span>
                        @endif
                    </p>
                </div>
            </div>

            @if($pqrTaquilla->comentario)
                <div class="mt-4 pt-4 border-t {{ $borderCard }}">
                    <p class="{{ $textMuted }}"><strong class="{{ $textTitle }}">Comentario:</strong></p>
                    <p class="{{ $textTitle }} mt-2">{{ $pqrTaquilla->comentario }}</p>
                </div>
            @endif

            @if($pqrTaquilla->adjuntos && count($pqrTaquilla->adjuntos) > 0)
                <div class="mt-4 pt-4 border-t {{ $borderCard }}">
                    <p class="{{ $textMuted }} mb-2"><strong class="{{ $textTitle }}">Adjuntos:</strong></p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($pqrTaquilla->adjuntos as $index => $adjunto)
                            <div class="relative">
                                @if(str_contains($adjunto, '.jpg') || str_contains($adjunto, '.jpeg') || str_contains($adjunto, '.png'))
                                    <img src="{{ asset($adjunto) }}" alt="Adjunto {{ $index + 1 }}" class="w-full h-32 object-cover rounded border {{ $borderCard }}">
                                @else
                                    <div class="w-full h-32 bg-gray-200 rounded border {{ $borderCard }} flex items-center justify-center">
                                        <svg class="w-12 h-12 {{ $textMuted }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <a href="{{ asset($adjunto) }}" target="_blank" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 hover:bg-opacity-50 transition rounded">
                                    <svg class="w-8 h-8 text-white opacity-0 hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6 flex space-x-3">
                <a href="{{ route('pqrs-taquilla.edit', $pqrTaquilla) }}"
                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg shadow-md transition">
                    Editar
                </a>
                <a href="{{ route('pqrs.index') }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md transition">
                    Volver
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
