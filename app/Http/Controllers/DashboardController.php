<?php

namespace App\Http\Controllers;

use App\Models\Conductor;
use App\Models\Propietario;
use App\Models\User;
use App\Models\Vehicle;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        // Conductores
        $conductoresCount = Conductor::count();
        $conductoresPorTipo = Conductor::selectRaw('conductor_tipo, COUNT(*) as total')
            ->groupBy('conductor_tipo')
            ->pluck('total', 'conductor_tipo')
            ->toArray();

        // Mapear tipos de conductores para mostrar
        $conductoresPorTipoFormateado = [];
        foreach ($conductoresPorTipo as $tipo => $total) {
            $nombreTipo = $tipo === 'A' ? 'Camionetas' : 'Busetas';
            $conductoresPorTipoFormateado[$nombreTipo] = $total;
        }

        // Próximos cumpleaños de conductores (próximos 15)
        $proximosCumpleanos = Conductor::whereNotNull('fecha_nacimiento')
            ->select('id', 'nombres', 'apellidos', 'cedula', 'fecha_nacimiento', 'conductor_tipo')
            ->get()
            ->map(function ($conductor) {
                $fechaNacimiento = $conductor->fecha_nacimiento;
                $hoy = now()->startOfDay();
                $anoActual = $hoy->year;
                $anoSiguiente = $anoActual + 1;

                // Crear fecha de cumpleaños para este año y el siguiente (sin hora)
                $cumpleanosEsteAno = $fechaNacimiento->copy()->year($anoActual)->startOfDay();
                $cumpleanosSiguienteAno = $fechaNacimiento->copy()->year($anoSiguiente)->startOfDay();

                // Determinar qué fecha usar
                // Si el cumpleaños de este año es hoy o en el futuro, usar ese
                // Si ya pasó, usar el del año siguiente
                if ($cumpleanosEsteAno->isBefore($hoy)) {
                    $proximoCumpleanos = $cumpleanosSiguienteAno;
                } else {
                    $proximoCumpleanos = $cumpleanosEsteAno;
                }

                // Calcular días hasta el cumpleaños (siempre positivo o 0)
                $diasRestantes = max(0, $hoy->diffInDays($proximoCumpleanos, false));

                return [
                    'id' => $conductor->id,
                    'nombres' => $conductor->nombres,
                    'apellidos' => $conductor->apellidos,
                    'cedula' => $conductor->cedula,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'tipo' => $conductor->conductor_tipo === 'A' ? 'Camionetas' : 'Busetas',
                    'proximo_cumpleanos' => $proximoCumpleanos,
                    'dias_restantes' => $diasRestantes,
                    'edad' => $proximoCumpleanos->year - $fechaNacimiento->year,
                ];
            })
            ->filter(function ($conductor) {
                // Solo incluir si el cumpleaños es en los próximos 365 días
                return $conductor['dias_restantes'] <= 365;
            })
            ->sortBy('dias_restantes')
            ->take(15)
            ->values();

        // Vehículos
        $vehiculosCount = Vehicle::count();
        $vehiculosPorTipo = Vehicle::selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->toArray();

        // Distribución de estados de vehículos
        $vehiculosPorEstado = Vehicle::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        // Calcular porcentajes
        $vehiculosEstadosConPorcentaje = [];
        foreach ($vehiculosPorEstado as $estado => $total) {
            $porcentaje = $vehiculosCount > 0 ? round(($total / $vehiculosCount) * 100, 1) : 0;
            $vehiculosEstadosConPorcentaje[$estado] = [
                'total' => $total,
                'porcentaje' => $porcentaje,
            ];
        }

        // Propietarios
        $propietariosCount = Propietario::count();
        $propietariosPorTipo = Propietario::selectRaw('tipo_propietario, COUNT(*) as total')
            ->groupBy('tipo_propietario')
            ->pluck('total', 'tipo_propietario')
            ->toArray();

        // Usuarios
        $usuariosCount = User::count();
        $roles = Role::whereIn('name', ['Mango', 'Admin', 'User'])->get();
        $usuariosPorRol = [];
        foreach ($roles as $rol) {
            $usuariosPorRol[$rol->name] = User::role($rol->name)->count();
        }

        return view('dashboard', compact(
            'conductoresCount',
            'conductoresPorTipoFormateado',
            'proximosCumpleanos',
            'vehiculosCount',
            'vehiculosPorTipo',
            'vehiculosEstadosConPorcentaje',
            'propietariosCount',
            'propietariosPorTipo',
            'usuariosCount',
            'usuariosPorRol'
        ));
    }
}
