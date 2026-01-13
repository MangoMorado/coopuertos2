<?php

namespace App\Exports;

use App\Models\Conductor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ConductoresExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Conductor::with(['asignacionActiva.vehicle'])->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Cédula',
            'Nombres',
            'Apellidos',
            'Tipo',
            'RH',
            'Número Interno',
            'Vehículo',
            'Celular',
            'Correo',
            'Fecha de Nacimiento',
            'Otra Profesión',
            'Nivel de Estudios',
            'Estado',
        ];
    }

    /**
     * @param  \App\Models\Conductor  $conductor
     */
    public function map($conductor): array
    {
        $vehiculo = $conductor->asignacionActiva && $conductor->asignacionActiva->vehicle
            ? $conductor->asignacionActiva->vehicle->placa
            : ($conductor->vehiculo ?: 'Relevo');

        return [
            $conductor->cedula,
            $conductor->nombres,
            $conductor->apellidos,
            $conductor->conductor_tipo,
            $conductor->rh,
            $conductor->numero_interno,
            $vehiculo,
            $conductor->celular,
            $conductor->correo,
            $conductor->fecha_nacimiento ? $conductor->fecha_nacimiento->format('Y-m-d') : '',
            $conductor->otra_profesion,
            $conductor->nivel_estudios,
            $conductor->estado,
        ];
    }
}
