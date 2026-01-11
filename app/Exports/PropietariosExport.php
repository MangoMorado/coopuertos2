<?php

namespace App\Exports;

use App\Models\Propietario;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PropietariosExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Propietario::latest()->get();
    }

    public function headings(): array
    {
        return [
            'Tipo de Identificación',
            'Número de Identificación',
            'Nombre Completo',
            'Tipo de Propietario',
            'Dirección',
            'Teléfono',
            'Correo Electrónico',
            'Estado',
        ];
    }

    /**
     * @param  \App\Models\Propietario  $propietario
     */
    public function map($propietario): array
    {
        return [
            $propietario->tipo_identificacion,
            $propietario->numero_identificacion,
            $propietario->nombre_completo,
            $propietario->tipo_propietario,
            $propietario->direccion_contacto,
            $propietario->telefono_contacto,
            $propietario->correo_electronico,
            $propietario->estado,
        ];
    }
}
