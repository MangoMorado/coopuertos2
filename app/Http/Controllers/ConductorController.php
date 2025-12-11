<?php
namespace App\Http\Controllers;

use App\Models\Conductor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ConductorController extends Controller
{
public function index(Request $request)
{
    $query = Conductor::query();

    if ($request->filled('cedula')) {
        $query->where('cedula', 'like', '%' . $request->cedula . '%');
    }

    $conductores = $query->latest()->paginate(10)->withQueryString();

    return view('conductores.index', compact('conductores'));
}


    public function create()
    {
        return view('conductores.create');
    }

public function store(Request $request)
{
    $validated = $request->validate([
        'nombres' => 'required|string|max:255',
        'apellidos' => 'required|string|max:255',
        'cedula' => 'required|string|unique:conductors,cedula',
        'conductor_tipo' => 'required|in:A,B',
        'rh' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
        'vehiculo_placa' => 'nullable|string|max:20',
        'numero_interno' => 'nullable|string|max:50',
        'celular' => 'nullable|string|max:20',
        'correo' => 'nullable|email',
        'fecha_nacimiento' => 'nullable|date',
        'otra_profesion' => 'nullable|string|max:255',
        'nivel_estudios' => 'nullable|string|max:255',
        'foto' => 'nullable|image|max:2048',
        'estado' => 'required|in:activo,inactivo',
    ]);

    // Si correo está vacío, poner "No tiene"
    if (empty($validated['correo'])) {
        $validated['correo'] = 'No tiene';
    }

    // Convertir placa a mayúsculas si existe
    if (!empty($validated['vehiculo_placa'])) {
        $validated['vehiculo_placa'] = strtoupper($validated['vehiculo_placa']);
    }

    // Manejo de la foto si se sube
    if ($request->hasFile('foto')) {
        $validated['foto'] = $this->storePhoto($request->file('foto'));
    }

    Conductor::create($validated);

    return redirect()->route('conductores.index')
                     ->with('success', 'Conductor creado correctamente.');
}

public function generarCarnet(Conductor $conductor)
{
    // Ejemplo simple: devolver PDF o vista del carnet
    // Aquí puedes usar un paquete como barryvdh/laravel-dompdf para generar PDF
    return view('conductores.carnet', compact('conductor'));
}



    public function show($uuid)
    {
        $conductor = Conductor::where('uuid', $uuid)->firstOrFail();
        return view('conductores.show', compact('conductor'));
    }

    public function edit(Conductor $conductore)
    {
        return view('conductores.edit', ['conductor' => $conductore]);
    }

public function update(Request $request, Conductor $conductore)
{
    $validated = $request->validate([
        'nombres' => 'required|string|max:255',
        'apellidos' => 'required|string|max:255',
        'cedula' => 'required|string|unique:conductors,cedula,' . $conductore->id,
        'conductor_tipo' => 'required|in:A,B',
        'rh' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
        'vehiculo_placa' => 'nullable|string|max:20',
        'numero_interno' => 'nullable|string|max:50',
        'celular' => 'nullable|string|max:20',
        'correo' => 'nullable|email',
        'fecha_nacimiento' => 'nullable|date',
        'otra_profesion' => 'nullable|string|max:255',
        'nivel_estudios' => 'nullable|string|max:255',
        'empresa' => 'nullable|string|max:255',
        'licencia' => 'nullable|string|max:255',
        'vencimiento_licencia' => 'nullable|date',
        'foto' => 'nullable|image|max:2048',
        'estado' => 'required|in:activo,inactivo',
    ]);

    // Si correo está vacío, poner "No tiene"
    if (empty($validated['correo'])) {
        $validated['correo'] = 'No tiene';
    }

    // Convertir placa a mayúsculas si existe
    if (!empty($validated['vehiculo_placa'])) {
        $validated['vehiculo_placa'] = strtoupper($validated['vehiculo_placa']);
    }

    // Manejo de la foto si se sube
    if ($request->hasFile('foto')) {
        if ($conductore->foto) {
            $this->deletePhoto($conductore->foto);
        }
        $validated['foto'] = $this->storePhoto($request->file('foto'));
    }

    $conductore->update($validated);

    return redirect()
        ->route('conductores.index')
        ->with('success', 'Conductor actualizado correctamente.');
}


    public function destroy(Conductor $conductor)
    {
        if ($conductor->foto) {
            $this->deletePhoto($conductor->foto);
        }

        $conductor->delete();
        return back()->with('success', 'Conductor eliminado.');
    }

    protected function storePhoto($file): string
    {
        $uploadPath = public_path('uploads/conductores');

        if (! File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return 'uploads/conductores/' . $filename;
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
}
