@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            Nuevo PQRS
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-6">
        <div class="{{ $bgCard }} shadow border {{ $borderCard }} rounded-lg p-6">
            @include('pqrs.form', ['pqr' => null, 'theme' => $theme, 'isDark' => $isDark])
        </div>
    </div>
</x-app-layout>
