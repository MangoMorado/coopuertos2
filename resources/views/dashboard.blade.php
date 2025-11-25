<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de control') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __('¡Has iniciado sesión!') }}

                    <div class="mt-6">
                        <a 
                            href="{{ route('conductores.index') }}" 
                            class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md shadow"
                        >
                            Conductores
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
