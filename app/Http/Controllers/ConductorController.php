<?php
namespace App\Http\Controllers;

use App\Models\Conductor;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class ConductorController extends Controller
{
    public function index()
    {
        $conductores = Conductor::latest()->paginate(10);
        return view('conductores.index', compact('conductores'));
    }

    public function create()
    {
        return view('conductores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'foto' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('conductores', 'public');
        }

        $conductor = Conductor::create($validated);

        return redirect()->route('conductores.index')->with('success', 'Conductor creado correctamente.');
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

    public function destroy(Conductor $conductor)
    {
        $conductor->delete();
        return back()->with('success', 'Conductor eliminado.');
    }
}
