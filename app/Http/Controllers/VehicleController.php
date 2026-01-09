<?php

namespace App\Http\Controllers;

use App\Models\Conductor;
use App\Models\ConductorVehicle;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::with(['asignaciones' => function ($q) {
            $q->where('estado', 'activo')->with('conductor');
        }]);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('placa', 'like', "%$s%")
                    ->orWhere('marca', 'like', "%$s%")
                    ->orWhere('modelo', 'like', "%$s%")
                    ->orWhere('tipo', 'like', "%$s%")
                    ->orWhere('propietario_nombre', 'like', "%$s%")
                    ->orWhereHas('asignaciones.conductor', function ($q) use ($s) {
                        $q->where('estado', 'activo')
                            ->where(function ($query) use ($s) {
                                $query->where('nombres', 'like', "%$s%")
                                    ->orWhere('apellidos', 'like', "%$s%")
                                    ->orWhere('cedula', 'like', "%$s%");
                            });
                    });
            });
        }

        $vehicles = $query->latest()->paginate(10)->withQueryString();

        return view('vehiculos.index', compact('vehicles'));
    }

    public function create()
    {
        return view('vehiculos.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $validated['placa'] = Str::upper($validated['placa']);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $this->storePhoto($request->file('foto'));
        }

        // Guardar conductor_id temporalmente si existe
        $conductorId = $validated['conductor_id'] ?? null;
        unset($validated['conductor_id']);

        $vehiculo = Vehicle::create($validated);

        // Si se asignó un conductor, crear el registro en conductor_vehicle
        if ($conductorId) {
            $conductor = Conductor::find($conductorId);
            if ($conductor) {
                $conductor->asignarVehiculo($vehiculo->id);
            }
            // También actualizar el campo conductor_id en vehicles para compatibilidad
            $vehiculo->update(['conductor_id' => $conductorId]);
        }

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo creado correctamente.');
    }

    public function show(Vehicle $vehiculo)
    {
        $vehiculo->load('conductor');

        return view('vehiculos.show', compact('vehiculo'));
    }

    public function edit(Vehicle $vehiculo)
    {
        $vehiculo->load('conductor');

        return view('vehiculos.edit', compact('vehiculo'));
    }

    public function update(Request $request, Vehicle $vehiculo)
    {
        $validated = $this->validateData($request, $vehiculo->id);

        $validated['placa'] = Str::upper($validated['placa']);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $this->storePhoto($request->file('foto'));
        }

        // Guardar conductor_id temporalmente si existe
        $nuevoConductorId = $validated['conductor_id'] ?? null;
        $conductorIdAnterior = $vehiculo->conductor_id;
        unset($validated['conductor_id']);

        $vehiculo->update($validated);

        // Gestionar la asignación en conductor_vehicle
        if ($nuevoConductorId != $conductorIdAnterior) {
            // Si había un conductor anterior, desasignarlo de este vehículo específico
            if ($conductorIdAnterior) {
                ConductorVehicle::where('conductor_id', $conductorIdAnterior)
                    ->where('vehicle_id', $vehiculo->id)
                    ->where('estado', 'activo')
                    ->update([
                        'estado' => 'inactivo',
                        'fecha_desasignacion' => now(),
                    ]);
            }

            // Si se asignó un nuevo conductor, crear el registro
            if ($nuevoConductorId) {
                $nuevoConductor = Conductor::find($nuevoConductorId);
                if ($nuevoConductor) {
                    // Desactivar cualquier otro vehículo activo del conductor
                    ConductorVehicle::where('conductor_id', $nuevoConductorId)
                        ->where('estado', 'activo')
                        ->where('vehicle_id', '!=', $vehiculo->id)
                        ->update([
                            'estado' => 'inactivo',
                            'fecha_desasignacion' => now(),
                        ]);

                    // Crear o activar la asignación para este vehículo
                    ConductorVehicle::updateOrCreate(
                        [
                            'conductor_id' => $nuevoConductorId,
                            'vehicle_id' => $vehiculo->id,
                        ],
                        [
                            'estado' => 'activo',
                            'fecha_asignacion' => now(),
                            'fecha_desasignacion' => null,
                        ]
                    );
                }
            }

            // Actualizar el campo conductor_id en vehicles para compatibilidad
            $vehiculo->update(['conductor_id' => $nuevoConductorId]);
        }

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo actualizado correctamente.');
    }

    public function destroy(Vehicle $vehiculo)
    {
        $vehiculo->delete();

        return back()->with('success', 'Vehículo eliminado.');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'tipo' => 'required|in:Bus,Camioneta,Taxi',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'anio_fabricacion' => 'required|integer|min:1900|max:'.now()->year,
            'placa' => 'required|string|max:20|unique:vehicles,placa,'.($id ?? 'NULL').',id',
            'chasis_vin' => 'nullable|string|max:255',
            'capacidad_pasajeros' => 'nullable|integer|min:0',
            'capacidad_carga_kg' => 'nullable|integer|min:0',
            'combustible' => 'required|in:gasolina,diesel,hibrido,electrico',
            'ultima_revision_tecnica' => 'nullable|date',
            'estado' => 'required|in:Activo,En Mantenimiento,Fuera de Servicio',
            'propietario_nombre' => 'required|string|max:255',
            'conductor_id' => 'nullable|exists:conductors,id',
            'foto' => 'nullable|image|max:4096',
        ]);
    }

    protected function storePhoto($file): string
    {
        // Leer el contenido del archivo
        $imageContent = file_get_contents($file->getRealPath());

        // Obtener el MIME type
        $mimeType = $file->getMimeType();

        // Convertir a base64 con el formato data URI
        $base64 = base64_encode($imageContent);

        return 'data:'.$mimeType.';base64,'.$base64;
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $columns = $request->get('columns', []); // Columnas específicas a buscar

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        // Si se especifican columnas, usar solo esas. Si no, buscar en todas las columnas comunes
        if (! empty($columns) && is_array($columns)) {
            $vehicles = Vehicle::where(function ($q) use ($query, $columns) {
                foreach ($columns as $column) {
                    if (in_array($column, ['placa', 'marca', 'modelo', 'anio_fabricacion', 'chasis_vin', 'capacidad_pasajeros', 'tipo'])) {
                        $q->orWhere($column, 'like', "%{$query}%");
                    }
                }
            })->limit(10)->get();
        } else {
            // Búsqueda por defecto en todas las columnas comunes
            $vehicles = Vehicle::where(function ($q) use ($query) {
                $q->where('placa', 'like', "%{$query}%")
                    ->orWhere('marca', 'like', "%{$query}%")
                    ->orWhere('modelo', 'like', "%{$query}%")
                    ->orWhere('anio_fabricacion', 'like', "%{$query}%")
                    ->orWhere('chasis_vin', 'like', "%{$query}%")
                    ->orWhere('capacidad_pasajeros', 'like', "%{$query}%")
                    ->orWhere('tipo', 'like', "%{$query}%");
            })->limit(10)->get();
        }

        return response()->json($vehicles->map(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'placa' => $vehicle->placa,
                'marca' => $vehicle->marca,
                'modelo' => $vehicle->modelo,
                'anio_fabricacion' => $vehicle->anio_fabricacion,
                'chasis_vin' => $vehicle->chasis_vin,
                'capacidad_pasajeros' => $vehicle->capacidad_pasajeros,
                'tipo' => $vehicle->tipo,
                'label' => "{$vehicle->placa} - {$vehicle->marca} {$vehicle->modelo}",
            ];
        }));
    }
}
