<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Conductor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::with('conductor');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('placa', 'like', "%$s%")
                  ->orWhere('marca', 'like', "%$s%")
                  ->orWhere('modelo', 'like', "%$s%")
                  ->orWhere('tipo', 'like', "%$s%")
                  ->orWhere('propietario_nombre', 'like', "%$s%");
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

        Vehicle::create($validated);

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
            if ($vehiculo->foto) {
                $this->deletePhoto($vehiculo->foto);
            }
            $validated['foto'] = $this->storePhoto($request->file('foto'));
        }

        $vehiculo->update($validated);

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo actualizado correctamente.');
    }

    public function destroy(Vehicle $vehiculo)
    {
        if ($vehiculo->foto) {
            $this->deletePhoto($vehiculo->foto);
        }
        $vehiculo->delete();

        return back()->with('success', 'Vehículo eliminado.');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'tipo' => 'required|in:Bus,Camioneta,Taxi',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'anio_fabricacion' => 'required|integer|min:1900|max:' . now()->year,
            'placa' => 'required|string|max:20|unique:vehicles,placa,' . ($id ?? 'NULL') . ',id',
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
        $uploadPath = public_path('uploads/vehiculos');

        if (! File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return 'uploads/vehiculos/' . $filename;
    }

    protected function deletePhoto(?string $path): void
    {
        if (! $path) {
            return;
        }

        $fullPath = public_path($path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $columns = $request->get('columns', []); // Columnas específicas a buscar
        
        if (strlen($query) < 1) {
            return response()->json([]);
        }

        // Si se especifican columnas, usar solo esas. Si no, buscar en todas las columnas comunes
        if (!empty($columns) && is_array($columns)) {
            $vehicles = Vehicle::where(function($q) use ($query, $columns) {
                foreach ($columns as $column) {
                    if (in_array($column, ['placa', 'marca', 'modelo', 'anio_fabricacion', 'chasis_vin', 'capacidad_pasajeros', 'tipo'])) {
                        $q->orWhere($column, 'like', "%{$query}%");
                    }
                }
            })->limit(10)->get();
        } else {
            // Búsqueda por defecto en todas las columnas comunes
            $vehicles = Vehicle::where(function($q) use ($query) {
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
