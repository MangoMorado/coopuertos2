<?php

namespace App\Http\Controllers;

use App\Models\Propietario;
use Illuminate\Http\Request;

class PropietarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Propietario::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_identificacion', 'like', '%' . $search . '%')
                  ->orWhere('nombre_completo', 'like', '%' . $search . '%')
                  ->orWhere('telefono_contacto', 'like', '%' . $search . '%')
                  ->orWhere('correo_electronico', 'like', '%' . $search . '%')
                  ->orWhere('direccion_contacto', 'like', '%' . $search . '%');
            });
        }

        $propietarios = $query->latest()->paginate(10)->withQueryString();

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
            'numero_identificacion' => 'required|string|unique:propietarios,numero_identificacion,' . $propietario->id . ',id|max:50',
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
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $propietarios = Propietario::where('nombre_completo', 'like', "%{$query}%")
            ->orWhere('numero_identificacion', 'like', "%{$query}%")
            ->orWhere('telefono_contacto', 'like', "%{$query}%")
            ->orWhere('correo_electronico', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json($propietarios->map(function ($propietario) {
            return [
                'id' => $propietario->id,
                'nombre_completo' => $propietario->nombre_completo,
                'numero_identificacion' => $propietario->numero_identificacion,
                'tipo_identificacion' => $propietario->tipo_identificacion,
                'telefono' => $propietario->telefono_contacto,
                'label' => "{$propietario->nombre_completo} ({$propietario->numero_identificacion})",
            ];
        }));
    }
}
