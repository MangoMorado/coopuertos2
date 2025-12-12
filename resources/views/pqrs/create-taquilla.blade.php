@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $textMuted = $isDark ? 'text-gray-400' : 'text-gray-600';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600 text-gray-100' : 'bg-white border-gray-300 text-gray-900';
    $label = $isDark ? 'text-gray-300' : 'text-gray-700';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Nuevo PQRS Taquilla
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-6">
        <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
            @include('pqrs.form-taquilla')
        </div>
    </div>
</x-app-layout>
