<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Conductor
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-6">
        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white p-6 rounded-lg shadow-md">
            <form method="POST" action="{{ route('conductores.update', $conductor) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('put')

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Nombre</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $conductor->nombre) }}" required
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Empresa</label>
                        <input type="text" name="empresa" value="{{ old('empresa', $conductor->empresa) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Licencia</label>
                        <input type="text" name="licencia" value="{{ old('licencia', $conductor->licencia) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Fecha de vencimiento</label>
                        <input type="date" name="vencimiento_licencia" value="{{ old('vencimiento_licencia', $conductor->vencimiento_licencia) }}"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Foto del conductor</label>
                        <input type="file" name="foto" accept="image/*"
                               class="w-full border-gray-300 rounded-lg shadow-sm">
                        @if ($conductor->foto)
                            <p class="text-sm text-gray-500 mt-1">Imagen actual:</p>
                            @php
                                $fotoActual = \Illuminate\Support\Str::startsWith($conductor->foto, 'uploads/')
                                    ? asset($conductor->foto)
                                    : asset('storage/' . $conductor->foto);
                            @endphp
                            <img src="{{ $fotoActual }}" alt="Foto actual" class="mt-2 h-24 w-24 object-cover rounded-md border">
                        @endif
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <a href="{{ route('conductores.index') }}"
                       class="btn btn-gray">
                       Cancelar
                    </a>
                    <button type="submit" class="btn btn-blue">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

