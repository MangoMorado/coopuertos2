<?php

namespace App\Http\Controllers;

use App\Exports\PropietariosExport;
use App\Models\Propietario;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PropietarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Propietario::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_identificacion', 'like', '%'.$search.'%')
                    ->orWhere('nombre_completo', 'like', '%'.$search.'%')
                    ->orWhere('telefono_contacto', 'like', '%'.$search.'%')
                    ->orWhere('correo_electronico', 'like', '%'.$search.'%')
                    ->orWhere('direccion_contacto', 'like', '%'.$search.'%');
            });
        }

        $propietarios = $query->latest()->paginate(10)->withQueryString();

        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'html' => view('propietarios.partials.table', compact('propietarios'))->render(),
                'pagination' => view('propietarios.partials.pagination', compact('propietarios'))->render(),
            ]);
        }

        return view('propietarios.index', compact('propietarios'));
    }

    public function create()
    {
        return view('propietarios.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_identificacion' => 'required|in:Cédula de Ciudadanía,RUC/NIT,Pasaporte',
            'numero_identificacion' => 'required|string|unique:propietarios,numero_identificacion|max:50',
            'nombre_completo' => 'required|string|max:255',
            'tipo_propietario' => 'required|in:Persona Natural,Persona Jurídica',
            'direccion_contacto' => 'nullable|string|max:500',
            'telefono_contacto' => 'nullable|string|max:20',
            'correo_electronico' => 'nullable|email|max:255',
            'estado' => 'required|in:Activo,Inactivo',
        ]);

        Propietario::create($validated);

        return redirect()->route('propietarios.index')
            ->with('success', 'Propietario creado correctamente.');
    }

    public function show(Propietario $propietario)
    {
        return view('propietarios.show', compact('propietario'));
    }

    public function edit(Propietario $propietario)
    {
        return view('propietarios.edit', compact('propietario'));
    }

    public function update(Request $request, Propietario $propietario)
    {
        $validated = $request->validate([
            'tipo_identificacion' => 'required|in:Cédula de Ciudadanía,RUC/NIT,Pasaporte',
            'numero_identificacion' => 'required|string|unique:propietarios,numero_identificacion,'.$propietario->id.',id|max:50',
            'nombre_completo' => 'required|string|max:255',
            'tipo_propietario' => 'required|in:Persona Natural,Persona Jurídica',
            'direccion_contacto' => 'nullable|string|max:500',
            'telefono_contacto' => 'nullable|string|max:20',
            'correo_electronico' => 'nullable|email|max:255',
            'estado' => 'required|in:Activo,Inactivo',
        ]);

        $propietario->update($validated);

        return redirect()->route('propietarios.index')
            ->with('success', 'Propietario actualizado correctamente.');
    }

    public function destroy(Propietario $propietario)
    {
        $propietario->delete();

        return back()->with('success', 'Propietario eliminado correctamente.');
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $columns = $request->get('columns', []); // Columnas específicas a buscar

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Si se especifican columnas, usar solo esas. Si no, buscar en todas las columnas comunes
        if (! empty($columns) && is_array($columns)) {
            $propietarios = Propietario::where(function ($q) use ($query, $columns) {
                foreach ($columns as $column) {
                    if (in_array($column, ['numero_identificacion', 'nombre_completo', 'telefono_contacto', 'correo_electronico', 'direccion_contacto'])) {
                        $q->orWhere($column, 'like', "%{$query}%");
                    }
                }
            })->limit(10)->get();
        } else {
            // Búsqueda por defecto en todas las columnas comunes
            $propietarios = Propietario::where(function ($q) use ($query) {
                $q->where('nombre_completo', 'like', "%{$query}%")
                    ->orWhere('numero_identificacion', 'like', "%{$query}%")
                    ->orWhere('telefono_contacto', 'like', "%{$query}%")
                    ->orWhere('correo_electronico', 'like', "%{$query}%")
                    ->orWhere('direccion_contacto', 'like', "%{$query}%");
            })->limit(10)->get();
        }

        return response()->json($propietarios->map(function ($propietario) {
            return [
                'id' => $propietario->id,
                'numero_identificacion' => $propietario->numero_identificacion,
                'nombre_completo' => $propietario->nombre_completo,
                'telefono_contacto' => $propietario->telefono_contacto,
                'correo_electronico' => $propietario->correo_electronico,
                'direccion_contacto' => $propietario->direccion_contacto,
                'tipo_identificacion' => $propietario->tipo_identificacion,
                'label' => "{$propietario->nombre_completo} ({$propietario->numero_identificacion})",
            ];
        }));
    }

    public function exportar(Request $request)
    {
        $formato = $request->get('formato', 'excel');
        $nombreArchivo = 'propietarios_'.date('YmdHis');

        if ($formato === 'csv') {
            return Excel::download(new PropietariosExport, $nombreArchivo.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download(new PropietariosExport, $nombreArchivo.'.xlsx');
    }
}
