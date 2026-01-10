<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Nuevo PQRS
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-6">
        <div class="bg-white dark:bg-gray-800 shadow border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            @include('pqrs.form', ['pqr' => null])
        </div>
    </div>
</x-app-layout>
