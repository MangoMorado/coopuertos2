<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Carnet de {{ $conductor->nombre }}
        </h2>
    </x-slot>

    <div class="max-w-md mx-auto mt-10 p-6 text-center relative" style="width:350px; height:220px; background-image: url('{{ asset('images/fondo_carnet.png') }}'); background-size: cover; background-position: center; border-radius: 10px;">
        <div class="absolute top-4 left-4 text-left text-black">
            <h3 class="text-lg font-bold">{{ $conductor->nombre }}</h3>
            <p>Cédula: {{ $conductor->cedula }}</p>
            <p>Vehículo: {{ $conductor->placa }}</p>
            <p>Propietario: {{ $conductor->propietario }}</p>
            <p>Vigencia: OCT/2025 - SEP/2026</p>
        </div>

        <div class="absolute bottom-4 left-4">
            {!! QrCode::size(70)->generate(route('conductor.public', $conductor->uuid)) !!}
        </div>
    </div>
</x-app-layout>
