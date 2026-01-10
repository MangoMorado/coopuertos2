<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Nuevo Propietario
        </h2>
    </x-slot>

    <div class="max-w-5xl mx-auto py-8 px-6">
        <div class="bg-white dark:bg-gray-800 shadow border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            @if ($errors->any())
                <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('propietarios.store') }}" class="space-y-4">
                @csrf

                @include('propietarios.form', ['propietario' => null])

                <div class="flex justify-end space-x-3 pt-4">
                    <a href="{{ route('propietarios.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md shadow-sm">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
