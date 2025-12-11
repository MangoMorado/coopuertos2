@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    
    // Colores según el tema
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            {{ __('Perfil') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 {{ $bgCard }} shadow sm:rounded-lg border {{ $isDark ? 'border-gray-700' : 'border-gray-200' }}">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form', ['theme' => $theme, 'isDark' => $isDark])
                </div>
            </div>

            <div class="p-4 sm:p-8 {{ $bgCard }} shadow sm:rounded-lg border {{ $isDark ? 'border-gray-700' : 'border-gray-200' }}">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form', ['theme' => $theme, 'isDark' => $isDark])
                </div>
            </div>

            <div class="p-4 sm:p-8 {{ $bgCard }} shadow sm:rounded-lg border {{ $isDark ? 'border-gray-700' : 'border-gray-200' }}">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form', ['theme' => $theme, 'isDark' => $isDark])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
