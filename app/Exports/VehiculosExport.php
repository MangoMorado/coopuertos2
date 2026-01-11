<?php

namespace App\Exports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VehiculosExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Vehicle::with(['asignaciones' => function ($q) {
            $q->where('estado', 'activo')->with('conductor');
        }])->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Placa',
            'Tipo',
            'Marca',
            'Modelo',
            'Año de Fabricación',
            'Chasis/VIN',
            'Capacidad Pasajeros',
            'Capacidad Carga (kg)',
            'Combustible',
            'Última Revisión Técnica',
            'Estado',
            'Propietario',
            'Conductor',
        ];
    }

    /**
     * @param  \App\Models\Vehicle  $vehiculo
     */
    public function map($vehiculo): array
    {
        $conductor = $vehiculo->asignaciones->first();
        $conductorNombre = $conductor && $conductor->conductor
            ? "{$conductor->conductor->nombres} {$conductor->conductor->apellidos} ({$conductor->conductor->cedula})"
            : 'Sin asignar';

        return [
            $vehiculo->placa,
            $vehiculo->tipo,
            $vehiculo->marca,
            $vehiculo->modelo,
            $vehiculo->anio_fabricacion,
            $vehiculo->chasis_vin,
            $vehiculo->capacidad_pasajeros,
            $vehiculo->capacidad_carga_kg,
            $vehiculo->combustible,
            $vehiculo->ultima_revision_tecnica ? date('Y-m-d', strtotime($vehiculo->ultima_revision_tecnica)) : '',
            $vehiculo->estado,
            $vehiculo->propietario_nombre,
            $conductorNombre,
        ];
    }
}
