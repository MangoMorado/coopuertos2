<?php

namespace App\Http\Controllers;

use App\Models\CarnetTemplate;
use App\Models\Conductor;
use App\Services\CarnetGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ConductorController extends Controller
{
    public function __construct(
        protected CarnetGeneratorService $carnetGenerator
    ) {}

    public function index(Request $request)
    {
        $query = Conductor::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cedula', 'like', '%'.$search.'%')
                    ->orWhere('nombres', 'like', '%'.$search.'%')
                    ->orWhere('apellidos', 'like', '%'.$search.'%')
                    ->orWhere('numero_interno', 'like', '%'.$search.'%')
                    ->orWhere('celular', 'like', '%'.$search.'%')
                    ->orWhere('correo', 'like', '%'.$search.'%');
            });
        }

        $conductores = $query->with(['asignacionActiva.vehicle'])->latest()->paginate(10)->withQueryString();

        if ($request->ajax() || $request->has('ajax')) {
            $theme = Auth::user()->theme ?? 'light';
            $isDark = $theme === 'dark';

            return response()->json([
                'html' => view('conductores.partials.table', compact('conductores', 'theme', 'isDark'))->render(),
                'pagination' => view('conductores.partials.pagination', compact('conductores'))->render(),
            ]);
        }

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

        // Manejo de la foto si se sube
        if ($request->hasFile('foto')) {
            $validated['foto'] = $this->storePhoto($request->file('foto'));
        }

        Conductor::create($validated);

        return redirect()->route('conductores.index')
            ->with('success', 'Conductor creado correctamente.');
    }

    public function info(Conductor $conductor)
    {
        // Cargar relaciones necesarias
        $conductor->load(['asignacionActiva.vehicle', 'asignaciones.vehicle']);

        return view('conductores.info', compact('conductor'));
    }

    public function show($uuid)
    {
        $conductor = Conductor::where('uuid', $uuid)
            ->with(['asignacionActiva.vehicle'])
            ->firstOrFail();

        // Si se solicita solo el QR
        if (request()->has('qr')) {
            $size = (int) request()->get('size', 200);
            // Usar SVG que no requiere imagick
            $qrCode = QrCode::size($size)
                ->generate(route('conductor.public', $uuid));

            return response($qrCode, 200)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', 'inline; filename="qr-code.svg"');
        }

        // Obtener plantilla activa de carnet
        $template = CarnetTemplate::where('activo', true)->first();

        // Generar imagen de previsualización usando el mismo motor del backend
        $previewImageUrl = null;
        if ($template && $template->imagen_plantilla && $template->variables_config) {
            try {
                // Crear directorio para imágenes de previsualización en public/storage
                $publicStorageDir = public_path('storage/carnet_previews');
                if (! File::exists($publicStorageDir)) {
                    File::makeDirectory($publicStorageDir, 0755, true);
                }

                // Generar imagen de previsualización
                $previewImagePath = $publicStorageDir.'/preview_'.$conductor->uuid.'.png';
                $this->carnetGenerator->generarCarnetImagen($conductor, $template, $previewImagePath);

                if (File::exists($previewImagePath)) {
                    // Ruta pública para acceder a la imagen
                    $previewImageUrl = asset('storage/carnet_previews/preview_'.$conductor->uuid.'.png');
                }
            } catch (\Exception $e) {
                Log::warning('Error generando imagen de previsualización: '.$e->getMessage(), [
                    'conductor_id' => $conductor->id,
                    'uuid' => $uuid,
                ]);
            }
        }

        return view('conductores.show', compact('conductor', 'template', 'previewImageUrl'));
    }

    /**
     * Generar y descargar carnet individual usando el sistema backend
     */
    public function descargarCarnet($uuid)
    {
        try {
            $conductor = Conductor::where('uuid', $uuid)
                ->with(['asignacionActiva.vehicle'])
                ->firstOrFail();

            // Verificar que hay una plantilla activa
            $template = CarnetTemplate::where('activo', true)->first();

            if (! $template) {
                return redirect()->route('conductor.public', $uuid)
                    ->with('error', 'No hay plantilla activa para generar el carnet.');
            }

            // Crear directorio temporal
            $tempDir = storage_path('app/temp/carnet_individual_'.$conductor->id.'_'.time());
            if (! File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            try {
                // Generar carnet PDF usando el mismo servicio que el masivo
                $pdfPath = $this->carnetGenerator->generarCarnetPDF($conductor, $template, $tempDir);

                if (! File::exists($pdfPath)) {
                    throw new \Exception('No se pudo generar el archivo PDF');
                }

                // Guardar en storage permanente (como el masivo)
                $carnetsDir = storage_path('app/carnets');
                if (! File::exists($carnetsDir)) {
                    File::makeDirectory($carnetsDir, 0755, true);
                }

                $nombreArchivo = 'carnet_'.$conductor->cedula.'_'.time().'.pdf';
                $rutaPermanente = $carnetsDir.'/'.$nombreArchivo;

                // Mover (no copiar) el archivo a la ubicación permanente
                File::move($pdfPath, $rutaPermanente);

                // Verificar que el archivo se movió correctamente
                if (! File::exists($rutaPermanente)) {
                    throw new \Exception('Error al mover el archivo PDF a la ubicación permanente');
                }

                // Actualizar conductor con ruta del carnet (como el masivo)
                $rutaRelativa = 'carnets/'.$nombreArchivo;
                $conductor->update(['ruta_carnet' => $rutaRelativa]);

                // Descargar desde la ruta permanente (como el masivo)
                $nombreDescarga = 'carnet_'.$conductor->cedula.'_'.date('YmdHis').'.pdf';

                return response()->download($rutaPermanente, $nombreDescarga)
                    ->deleteFileAfterSend(false); // No eliminar, el archivo se guarda permanentemente

            } catch (\Exception $e) {
                Log::error('Error generando carnet individual: '.$e->getMessage(), [
                    'conductor_id' => $conductor->id,
                    'uuid' => $uuid,
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->route('conductor.public', $uuid)
                    ->with('error', 'Error al generar el carnet: '.$e->getMessage());
            } finally {
                // Limpiar directorio temporal (solo si no se movió el archivo)
                if (File::exists($tempDir)) {
                    try {
                        File::deleteDirectory($tempDir);
                    } catch (\Exception $e) {
                        Log::warning('Error limpiando directorio temporal: '.$e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error en descargarCarnet: '.$e->getMessage(), [
                'uuid' => $uuid,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('conductores.index')
                ->with('error', 'Error al generar el carnet: '.$e->getMessage());
        }
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
            'cedula' => 'required|string|unique:conductors,cedula,'.$conductore->id,
            'conductor_tipo' => 'required|in:A,B',
            'rh' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
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

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return 'uploads/conductores/'.$filename;
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

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Si se especifican columnas, usar solo esas. Si no, buscar en todas las columnas comunes
        if (! empty($columns) && is_array($columns)) {
            $conductores = Conductor::where(function ($q) use ($query, $columns) {
                foreach ($columns as $column) {
                    if (in_array($column, ['cedula', 'nombres', 'apellidos', 'celular', 'correo', 'numero_interno'])) {
                        $q->orWhere($column, 'like', "%{$query}%");
                    }
                }
            })->limit(10)->get();
        } else {
            // Búsqueda por defecto en todas las columnas comunes
            $conductores = Conductor::where(function ($q) use ($query) {
                $q->where('nombres', 'like', "%{$query}%")
                    ->orWhere('apellidos', 'like', "%{$query}%")
                    ->orWhere('cedula', 'like', "%{$query}%")
                    ->orWhere('celular', 'like', "%{$query}%")
                    ->orWhere('correo', 'like', "%{$query}%")
                    ->orWhere('numero_interno', 'like', "%{$query}%");
            })->limit(10)->get();
        }

        return response()->json($conductores->map(function ($conductor) {
            return [
                'id' => $conductor->id,
                'cedula' => $conductor->cedula,
                'nombres' => $conductor->nombres,
                'apellidos' => $conductor->apellidos,
                'celular' => $conductor->celular,
                'correo' => $conductor->correo,
                'numero_interno' => $conductor->numero_interno,
                'label' => "{$conductor->nombres} {$conductor->apellidos} ({$conductor->cedula})",
            ];
        }));
    }
}
