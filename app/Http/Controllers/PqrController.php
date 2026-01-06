<?php

namespace App\Http\Controllers;

use App\Models\Pqr;
use App\Models\PqrTaquilla;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PqrController extends Controller
{
    public function index(Request $request)
    {
        $query = Pqr::with(['vehiculo', 'usuarioAsignado']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', '%' . $search . '%')
                  ->orWhere('correo_electronico', 'like', '%' . $search . '%')
                  ->orWhere('numero_tiquete', 'like', '%' . $search . '%')
                  ->orWhere('numero_telefono', 'like', '%' . $search . '%')
                  ->orWhere('vehiculo_placa', 'like', '%' . $search . '%')
                  ->orWhere('tipo', 'like', '%' . $search . '%')
                  ->orWhere('estado', 'like', '%' . $search . '%')
                  ->orWhereHas('usuarioAsignado', function($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                  });
            });
        }

        $pqrs = $query->latest()->paginate(10)->withQueryString();

        // Obtener PQRS de Taquilla
        $queryTaquilla = PqrTaquilla::with(['usuarioAsignado']);

        if ($request->filled('search_taquilla')) {
            $search = $request->search_taquilla;
            $queryTaquilla->where(function($q) use ($search) {
                $q->where('nombre', 'like', '%' . $search . '%')
                  ->orWhere('correo', 'like', '%' . $search . '%')
                  ->orWhere('telefono', 'like', '%' . $search . '%')
                  ->orWhere('sede', 'like', '%' . $search . '%')
                  ->orWhere('tipo', 'like', '%' . $search . '%')
                  ->orWhere('estado', 'like', '%' . $search . '%')
                  ->orWhereHas('usuarioAsignado', function($q) use ($search) {
                      $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                  });
            });
        }

        $pqrsTaquilla = $queryTaquilla->latest()->paginate(10)->withQueryString();

        return view('pqrs.index', compact('pqrs', 'pqrsTaquilla'));
    }

    public function create()
    {
        return view('pqrs.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        // Manejo de adjuntos
        if ($request->hasFile('adjuntos')) {
            $adjuntos = [];
            foreach ($request->file('adjuntos') as $file) {
                $adjuntos[] = $this->storeFile($file);
            }
            $validated['adjuntos'] = $adjuntos;
        }

        // Buscar vehículo por placa si se proporciona
        if (!empty($validated['vehiculo_placa'])) {
            $vehiculo = Vehicle::where('placa', Str::upper($validated['vehiculo_placa']))->first();
            if ($vehiculo) {
                $validated['vehiculo_id'] = $vehiculo->id;
            }
        }

        // Si no se proporciona estado, usar 'Radicada' por defecto
        if (empty($validated['estado'])) {
            $validated['estado'] = 'Radicada';
        }

        Pqr::create($validated);

        // Si es desde el formulario público, redirigir al formulario
        if ($request->routeIs('pqrs.store.public')) {
            return redirect()->back()->with('success', 'PQRS enviado correctamente. ¡Gracias por tu feedback!');
        }

        return redirect()->route('pqrs.index')->with('success', 'PQRS creado correctamente.');
    }

    public function show(Pqr $pqr)
    {
        $pqr->load(['vehiculo', 'usuarioAsignado']);
        return view('pqrs.show', compact('pqr'));
    }

    public function edit(Pqr $pqr)
    {
        $pqr->load(['vehiculo', 'usuarioAsignado']);
        return view('pqrs.edit', compact('pqr'));
    }

    public function update(Request $request, Pqr $pqr)
    {
        $validated = $this->validateData($request);

        // Manejo de nuevos adjuntos
        if ($request->hasFile('adjuntos')) {
            $adjuntos = $pqr->adjuntos ?? [];
            foreach ($request->file('adjuntos') as $file) {
                $adjuntos[] = $this->storeFile($file);
            }
            $validated['adjuntos'] = $adjuntos;
        }

        // Buscar vehículo por placa si se proporciona
        if (!empty($validated['vehiculo_placa'])) {
            $vehiculo = Vehicle::where('placa', Str::upper($validated['vehiculo_placa']))->first();
            if ($vehiculo) {
                $validated['vehiculo_id'] = $vehiculo->id;
            }
        }

        $pqr->update($validated);

        return redirect()->route('pqrs.index')->with('success', 'PQRS actualizado correctamente.');
    }

    public function destroy(Pqr $pqr)
    {
        // Eliminar archivos adjuntos
        if ($pqr->adjuntos) {
            foreach ($pqr->adjuntos as $adjunto) {
                $this->deleteFile($adjunto);
            }
        }

        $pqr->delete();
        return back()->with('success', 'PQRS eliminado correctamente.');
    }

    public function publicForm()
    {
        return view('pqrs.form-public');
    }

    public function editFormTemplate()
    {
        $configPath = storage_path('app/pqrs_form_config.json');
        
        if (File::exists($configPath)) {
            $fields = json_decode(File::get($configPath), true);
            // Normalizar campos: agregar valores por defecto si faltan
            foreach ($fields as &$field) {
                if ($field['type'] === 'autocomplete') {
                    $field['autocomplete_source'] = $field['autocomplete_source'] ?? 'vehiculos';
                    $field['autocomplete_columns'] = $field['autocomplete_columns'] ?? [];
                    $field['autocomplete_label_field'] = $field['autocomplete_label_field'] ?? null;
                }
                if ($field['type'] === 'textarea' && !isset($field['rows'])) {
                    $field['rows'] = 4;
                }
                if ($field['type'] === 'rating' && !isset($field['max_rating'])) {
                    $field['max_rating'] = 5;
                }
                if ($field['type'] === 'file') {
                    $field['multiple'] = $field['multiple'] ?? true;
                    $field['accept'] = $field['accept'] ?? 'image/*,.pdf,.doc,.docx,video/*';
                    $field['help_text'] = $field['help_text'] ?? 'Formatos permitidos: Imágenes, Documentos, Videos. Máximo 10MB por archivo.';
                }
                if ($field['type'] === 'logo') {
                    $field['logo_path'] = $field['logo_path'] ?? '/images/logo.svg';
                }
            }
            unset($field);
        } else {
            // Configuración por defecto
            $fields = $this->getDefaultFormFields();
            File::put($configPath, json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        return view('pqrs.edit-template', compact('fields'));
    }

    public function updateFormTemplate(Request $request)
    {
        $request->validate([
            'fields' => 'required|array',
            'fields.*.name' => 'required|string',
            'fields.*.label' => 'required|string',
            'fields.*.type' => 'required|string',
        ]);

        $configPath = storage_path('app/pqrs_form_config.json');
        $fields = $request->fields;
        
        // Procesar campos: decodificar options si vienen como JSON string
        foreach ($fields as &$field) {
            if (isset($field['options']) && is_string($field['options'])) {
                $decoded = json_decode($field['options'], true);
                $field['options'] = is_array($decoded) ? $decoded : [];
            }
            // Procesar autocomplete_columns si viene como JSON string
            if (isset($field['autocomplete_columns']) && is_string($field['autocomplete_columns'])) {
                $decoded = json_decode($field['autocomplete_columns'], true);
                $field['autocomplete_columns'] = is_array($decoded) ? $decoded : [];
            }
            // Convertir required y enabled a boolean
            $field['required'] = isset($field['required']) && ($field['required'] == '1' || $field['required'] === true);
            $field['enabled'] = !isset($field['enabled']) || $field['enabled'] == '1' || $field['enabled'] === true;
            $field['order'] = isset($field['order']) ? (int)$field['order'] : 999;
            
            // Procesar campos específicos por tipo
            if ($field['type'] === 'file') {
                $field['multiple'] = isset($field['multiple']) && ($field['multiple'] == '1' || $field['multiple'] === true);
                $field['accept'] = $field['accept'] ?? null;
                $field['help_text'] = $field['help_text'] ?? null;
            }
            if ($field['type'] === 'rating') {
                $field['max_rating'] = isset($field['max_rating']) ? (int)$field['max_rating'] : 5;
            }
            if ($field['type'] === 'textarea') {
                $field['rows'] = isset($field['rows']) ? (int)$field['rows'] : 4;
            }
            if ($field['type'] === 'autocomplete') {
                $field['autocomplete_source'] = $field['autocomplete_source'] ?? 'vehiculos';
                $field['autocomplete_columns'] = $field['autocomplete_columns'] ?? [];
                $field['autocomplete_label_field'] = $field['autocomplete_label_field'] ?? null;
            }
            if ($field['type'] === 'logo') {
                $field['logo_path'] = $field['logo_path'] ?? '/images/logo.svg';
            }
        }
        unset($field);
        
        // Regenerar el template
        $this->regenerateFormTemplate($fields);
        
        // Guardar configuración
        File::put($configPath, json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return redirect()->route('pqrs.edit-template')
            ->with('success', 'Formulario actualizado correctamente.');
    }

    protected function getDefaultFormFields()
    {
        return [
            [
                'id' => 'fecha',
                'name' => 'fecha',
                'label' => 'Fecha',
                'type' => 'date',
                'required' => false,
                'placeholder' => '',
                'value' => date('Y-m-d'),
                'order' => 1,
                'enabled' => true,
            ],
            [
                'id' => 'nombre',
                'name' => 'nombre',
                'label' => 'Nombre',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Ingrese su nombre completo',
                'value' => '',
                'order' => 2,
                'enabled' => true,
            ],
            [
                'id' => 'vehiculo_placa',
                'name' => 'vehiculo_placa',
                'label' => 'Vehículo (Placa)',
                'type' => 'autocomplete',
                'required' => false,
                'placeholder' => 'Buscar por placa...',
                'value' => '',
                'order' => 3,
                'enabled' => true,
                'autocomplete_source' => 'vehiculos',
            ],
            [
                'id' => 'numero_tiquete',
                'name' => 'numero_tiquete',
                'label' => 'Número de Tiquete',
                'type' => 'text',
                'required' => false,
                'placeholder' => 'Ingrese el número de tiquete',
                'value' => '',
                'order' => 4,
                'enabled' => true,
            ],
            [
                'id' => 'correo_electronico',
                'name' => 'correo_electronico',
                'label' => 'Correo Electrónico',
                'type' => 'email',
                'required' => false,
                'placeholder' => 'correo@ejemplo.com',
                'value' => '',
                'order' => 5,
                'enabled' => true,
            ],
            [
                'id' => 'numero_telefono',
                'name' => 'numero_telefono',
                'label' => 'Número de Teléfono',
                'type' => 'tel',
                'required' => false,
                'placeholder' => 'Ingrese su número de teléfono',
                'value' => '',
                'order' => 6,
                'enabled' => true,
            ],
            [
                'id' => 'calificacion',
                'name' => 'calificacion',
                'label' => 'Califica el Servicio',
                'type' => 'rating',
                'required' => false,
                'placeholder' => '',
                'value' => 0,
                'order' => 7,
                'enabled' => true,
                'max_rating' => 5,
            ],
            [
                'id' => 'tipo',
                'name' => 'tipo',
                'label' => 'Tipo',
                'type' => 'select',
                'required' => true,
                'placeholder' => '',
                'value' => '',
                'order' => 8,
                'enabled' => true,
                'options' => ['Peticiones', 'Quejas', 'Reclamos', 'Sugerencias', 'Otros'],
            ],
            [
                'id' => 'comentarios',
                'name' => 'comentarios',
                'label' => 'Comentarios',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => 'Escriba sus comentarios aquí...',
                'value' => '',
                'order' => 9,
                'enabled' => true,
                'rows' => 4,
            ],
            [
                'id' => 'adjuntos',
                'name' => 'adjuntos',
                'label' => 'Adjuntos',
                'type' => 'file',
                'required' => false,
                'placeholder' => '',
                'value' => '',
                'order' => 10,
                'enabled' => true,
                'multiple' => true,
                'accept' => 'image/*,.pdf,.doc,.docx,video/*',
                'help_text' => 'Formatos permitidos: Imágenes (jpg, png), Documentos (pdf, doc, docx), Videos (mp4, avi, mov). Máximo 10MB por archivo.',
            ],
        ];
    }

    protected function regenerateFormTemplate($fields)
    {
        // Ordenar campos por orden
        usort($fields, function($a, $b) {
            return ($a['order'] ?? 999) - ($b['order'] ?? 999);
        });

        $templatePath = resource_path('views/pqrs/form-public.blade.php');
        $template = $this->generateBladeTemplate($fields);
        
        File::put($templatePath, $template);
    }

    protected function generateBladeTemplate($fields)
    {
        $html = '<!DOCTYPE html>
<html lang="{{ str_replace(\'_\', \'-\', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Formulario PQRS - Coopuertos</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite([\'resources/css/app.css\', \'resources/js/app.js\'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white shadow-lg rounded-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Formulario PQRS</h1>
                <p class="text-gray-600 mb-6">Peticiones, Quejas, Reclamos y Sugerencias</p>

                @if (session(\'success\'))
                    <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                        {{ session(\'success\') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route(\'pqrs.store.public\') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
';

        foreach ($fields as $field) {
            if (!($field['enabled'] ?? true)) continue;

            $required = ($field['required'] ?? false) ? 'required' : '';
            $requiredStar = ($field['required'] ?? false) ? ' <span class="text-red-500">*</span>' : '';
            $placeholder = !empty($field['placeholder']) ? 'placeholder="' . htmlspecialchars($field['placeholder']) . '"' : '';
            $oldValue = 'old(\'' . $field['name'] . '\'' . (!empty($field['value']) ? ', \'' . $field['value'] . '\'' : '') . ')';

            switch ($field['type']) {
                case 'logo':
                    $html .= $this->generateLogoField($field);
                    break;
                case 'autocomplete':
                    $html .= $this->generateAutocompleteField($field, $required, $requiredStar, $placeholder);
                    break;
                case 'rating':
                    $html .= $this->generateRatingField($field, $required, $requiredStar);
                    break;
                case 'select':
                    $html .= $this->generateSelectField($field, $required, $requiredStar, $oldValue);
                    break;
                case 'textarea':
                    $rows = $field['rows'] ?? 4;
                    $html .= "                    <div>\n";
                    $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">" . htmlspecialchars($field['label']) . "$requiredStar</label>\n";
                    $html .= "                        <textarea name=\"{$field['name']}\" rows=\"$rows\" $required $placeholder\n";
                    $html .= "                                  class=\"w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500\">{{ $oldValue }}</textarea>\n";
                    $html .= "                    </div>\n\n";
                    break;
                case 'file':
                    $multiple = ($field['multiple'] ?? false) ? 'multiple' : '';
                    $accept = !empty($field['accept']) ? 'accept="' . htmlspecialchars($field['accept']) . '"' : '';
                    $helpText = !empty($field['help_text']) ? "\n                        <p class=\"text-xs text-gray-500 mb-2\">" . htmlspecialchars($field['help_text']) . "</p>" : '';
                    $html .= "                    <div>\n";
                    $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">" . htmlspecialchars($field['label']) . "$requiredStar</label>$helpText\n";
                    $html .= "                        <input type=\"file\" name=\"{$field['name']}[]\" $multiple $accept $required\n";
                    $html .= "                               class=\"w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500\">\n";
                    $html .= "                    </div>\n\n";
                    break;
                default:
                    $type = $field['type'];
                    $valueAttr = ($type === 'date' && !empty($field['value'])) ? 'value="' . $field['value'] . '"' : 'value="{{ ' . $oldValue . ' }}"';
                    if ($type === 'date' && empty($field['value'])) {
                        $valueAttr = 'value="{{ old(\'' . $field['name'] . '\', date(\'Y-m-d\')) }}"';
                    }
                    $html .= "                    <div>\n";
                    $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">" . htmlspecialchars($field['label']) . "$requiredStar</label>\n";
                    $html .= "                        <input type=\"$type\" name=\"{$field['name']}\" $valueAttr $required $placeholder\n";
                    $html .= "                               class=\"w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500\">\n";
                    $html .= "                    </div>\n\n";
            }
        }

        $html .= '                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md shadow-sm font-medium">
                            Enviar PQRS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function vehiculoAutocomplete() {
        return {
            search: \'\',
            resultados: [],
            mostrarDropdown: false,
            selectedIndex: -1,
            valorSeleccionado: \'\',
            
            buscar() {
                if (this.search.length < 1) {
                    this.resultados = [];
                    this.mostrarDropdown = false;
                    return;
                }
                
                fetch(`{{ route(\'api.vehiculos.search\') }}?q=${encodeURIComponent(this.search)}`)
                    .then(response => response.json())
                    .then(data => {
                        this.resultados = data;
                        this.mostrarDropdown = data.length > 0;
                        this.selectedIndex = -1;
                    })
                    .catch(error => {
                        console.error(\'Error:\', error);
                        this.resultados = [];
                    });
            },
            
            seleccionar(index) {
                if (index >= 0 && index < this.resultados.length) {
                    const resultado = this.resultados[index];
                    this.search = resultado.label || resultado.placa || \'\';
                    this.valorSeleccionado = resultado.label || resultado.placa || \'\';
                    this.mostrarDropdown = false;
                }
            },
            
            siguiente() {
                if (this.selectedIndex < this.resultados.length - 1) {
                    this.selectedIndex++;
                }
            },
            
            anterior() {
                if (this.selectedIndex > 0) {
                    this.selectedIndex--;
                }
            }
        };
    }
    </script>
</body>
</html>';

        return $html;
    }

    protected function generateAutocompleteField($field, $required, $requiredStar, $placeholder)
    {
        $autocompleteSource = $field['autocomplete_source'] ?? 'vehiculos';
        $autocompleteColumns = $field['autocomplete_columns'] ?? [];
        $labelField = $field['autocomplete_label_field'] ?? '';
        $fieldName = $field['name'];
        
        // Determinar la ruta API según la fuente
        $apiRoute = match($autocompleteSource) {
            'vehiculos' => 'api.vehiculos.search',
            'propietarios' => 'api.propietarios.search',
            'conductores' => 'api.conductores.search',
            default => 'api.vehiculos.search',
        };
        
        // Generar el nombre de la función JavaScript única
        $functionName = 'autocomplete_' . preg_replace('/[^a-zA-Z0-9]/', '_', $fieldName);
        
        $html = "                    <div x-data=\"{$functionName}()\">\n";
        $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">" . htmlspecialchars($field['label']) . "$requiredStar</label>\n";
        $html .= "                        <div class=\"relative\">\n";
        $html .= "                            <input \n";
        $html .= "                                type=\"text\" \n";
        $html .= "                                x-model=\"search\"\n";
        $html .= "                                @input.debounce.300ms=\"buscar()\"\n";
        $html .= "                                @focus=\"mostrarDropdown = true\"\n";
        $html .= "                                @click.away=\"mostrarDropdown = false\"\n";
        $html .= "                                @keydown.escape=\"mostrarDropdown = false\"\n";
        $html .= "                                @keydown.arrow-down.prevent=\"siguiente()\"\n";
        $html .= "                                @keydown.arrow-up.prevent=\"anterior()\"\n";
        $html .= "                                @keydown.enter.prevent=\"seleccionar(selectedIndex)\"\n";
        $html .= "                                $placeholder\n";
        $html .= "                                class=\"w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500\">\n";
        $html .= "                            <input type=\"hidden\" name=\"{$field['name']}\" x-model=\"valorSeleccionado\">\n";
        $html .= "                            \n";
        $html .= "                            <div x-show=\"mostrarDropdown && resultados.length > 0\" \n";
        $html .= "                                 x-transition\n";
        $html .= "                                 class=\"absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto\"\n";
        $html .= "                                 style=\"display: none;\">\n";
        $html .= "                                <template x-for=\"(resultado, index) in resultados\" :key=\"resultado.id\">\n";
        $html .= "                                    <div \n";
        $html .= "                                        @click=\"seleccionar(index)\"\n";
        $html .= "                                        @mouseenter=\"selectedIndex = index\"\n";
        $html .= "                                        :class=\"selectedIndex === index ? \'bg-gray-100\' : \'\'\"\n";
        $html .= "                                        class=\"px-4 py-2 cursor-pointer text-gray-900 border-b border-gray-200 last:border-b-0 hover:bg-gray-100\">\n";
        $html .= "                                        <div class=\"font-semibold\" x-text=\"resultado.label\"></div>\n";
        $html .= "                                        <div x-show=\"formatearInfoAdicional(resultado)\" class=\"text-sm text-gray-600 mt-1\" x-text=\"formatearInfoAdicional(resultado)\"></div>\n";
        $html .= "                                    </div>\n";
        $html .= "                                </template>\n";
        $html .= "                            </div>\n";
        $html .= "                        </div>\n";
        $html .= "                    </div>\n\n";
        
        // Generar la función JavaScript única
        $labelFieldJs = $labelField ? "'{$labelField}'" : "null";
        $columnsJs = !empty($autocompleteColumns) ? json_encode($autocompleteColumns) : "[]";
        
        // Filtrar columnas de display para excluir el labelField (evitar duplicación)
        $displayColumns = !empty($autocompleteColumns) && !empty($labelField) 
            ? array_filter($autocompleteColumns, fn($col) => $col !== $labelField)
            : $autocompleteColumns;
        $displayColumnsJs = !empty($displayColumns) ? json_encode(array_values($displayColumns)) : "[]";
        
        // Crear función helper para formatear información adicional
        $html .= "                    <script>\n";
        $html .= "                    function {$functionName}() {\n";
        $html .= "                        return {\n";
        $html .= "                            search: '',\n";
        $html .= "                            resultados: [],\n";
        $html .= "                            mostrarDropdown: false,\n";
        $html .= "                            selectedIndex: -1,\n";
        $html .= "                            valorSeleccionado: '',\n";
        $html .= "                            labelField: {$labelFieldJs},\n";
        $html .= "                            searchColumns: {$columnsJs},\n";
        $html .= "                            displayColumns: {$displayColumnsJs},\n";
        $html .= "                            \n";
        $html .= "                            formatearInfoAdicional(item) {\n";
        $html .= "                                if (!this.displayColumns || this.displayColumns.length === 0) return '';\n";
        $html .= "                                const partes = [];\n";
        $html .= "                                this.displayColumns.forEach(col => {\n";
        $html .= "                                    if (item[col] && item[col] !== null && item[col] !== '') {\n";
        $html .= "                                        partes.push(item[col]);\n";
        $html .= "                                    }\n";
        $html .= "                                });\n";
        $html .= "                                return partes.length > 0 ? partes.join(' - ') : '';\n";
        $html .= "                            },\n";
        $html .= "                            \n";
        $html .= "                            buscar() {\n";
        $html .= "                                if (this.search.length < 1) {\n";
        $html .= "                                    this.resultados = [];\n";
        $html .= "                                    this.mostrarDropdown = false;\n";
        $html .= "                                    return;\n";
        $html .= "                                }\n";
        $html .= "                                \n";
        // Construir URL con parámetros de columnas si están configuradas
        $html .= "                                let url = `{{ route('{$apiRoute}') }}?q=\${encodeURIComponent(this.search)}`;\n";
        $html .= "                                if (this.searchColumns && this.searchColumns.length > 0) {\n";
        $html .= "                                    const columnsParam = this.searchColumns.map(col => 'columns[]=' + encodeURIComponent(col)).join('&');\n";
        $html .= "                                    url += '&' + columnsParam;\n";
        $html .= "                                }\n";
        $html .= "                                \n";
        $html .= "                                fetch(url)\n";
        $html .= "                                    .then(response => response.json())\n";
        $html .= "                                    .then(data => {\n";
        $html .= "                                        this.resultados = data.map(item => {\n";
        $html .= "                                            let label = '';\n";
        if ($labelField) {
            $html .= "                                            if (item.{$labelField}) {\n";
            $html .= "                                                label = String(item.{$labelField});\n";
            $html .= "                                            } else if (item.label) {\n";
            $html .= "                                                label = item.label;\n";
            $html .= "                                            } else {\n";
            $html .= "                                                label = JSON.stringify(item);\n";
            $html .= "                                            }\n";
        } else {
            $html .= "                                            label = item.label || JSON.stringify(item);\n";
        }
        $html .= "                                            return { ...item, label: label };\n";
        $html .= "                                        });\n";
        $html .= "                                        this.mostrarDropdown = data.length > 0;\n";
        $html .= "                                        this.selectedIndex = -1;\n";
        $html .= "                                    })\n";
        $html .= "                                    .catch(error => {\n";
        $html .= "                                        console.error('Error:', error);\n";
        $html .= "                                        this.resultados = [];\n";
        $html .= "                                    });\n";
        $html .= "                            },\n";
        $html .= "                            \n";
        $html .= "                            seleccionar(index) {\n";
        $html .= "                                if (index >= 0 && index < this.resultados.length) {\n";
        $html .= "                                    const resultado = this.resultados[index];\n";
        if ($labelField) {
            $html .= "                                    const displayValue = resultado.{$labelField} || resultado.label || '';\n";
        } else {
            $html .= "                                    const displayValue = resultado.label || '';\n";
        }
        $html .= "                                    this.search = displayValue;\n";
        $html .= "                                    this.valorSeleccionado = displayValue;\n";
        $html .= "                                    this.mostrarDropdown = false;\n";
        $html .= "                                }\n";
        $html .= "                            },\n";
        $html .= "                            \n";
        $html .= "                            siguiente() {\n";
        $html .= "                                if (this.selectedIndex < this.resultados.length - 1) {\n";
        $html .= "                                    this.selectedIndex++;\n";
        $html .= "                                }\n";
        $html .= "                            },\n";
        $html .= "                            \n";
        $html .= "                            anterior() {\n";
        $html .= "                                if (this.selectedIndex > 0) {\n";
        $html .= "                                    this.selectedIndex--;\n";
        $html .= "                                }\n";
        $html .= "                            }\n";
        $html .= "                        };\n";
        $html .= "                    }\n";
        $html .= "                    </script>\n\n";
        
        return $html;
    }

    protected function generateLogoField($field)
    {
        $logoPath = $field['logo_path'] ?? '/images/logo.svg';
        $html = "                    <div class=\"flex justify-center my-6\">\n";
        $html .= "                        <img src=\"{$logoPath}\" alt=\"Logo\" class=\"max-h-24 max-w-full object-contain\">\n";
        $html .= "                    </div>\n\n";
        return $html;
    }

    protected function generateRatingField($field, $required, $requiredStar)
    {
        $maxRating = $field['max_rating'] ?? 5;
        $html = "                    <div>\n";
        $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-2\">" . htmlspecialchars($field['label']) . "$requiredStar</label>\n";
        $html .= "                        <div class=\"flex items-center space-x-2\" x-data=\"{ rating: {{ old('{$field['name']}', 0) }} }\">\n";
        $html .= "                            <input type=\"hidden\" name=\"{$field['name']}\" x-model=\"rating\">\n";
        for ($i = 1; $i <= $maxRating; $i++) {
            $html .= "                            <button type=\"button\" \n";
            $html .= "                                    @click=\"rating = $i\"\n";
            $html .= "                                    class=\"focus:outline-none\">\n";
            $html .= "                                <svg class=\"w-8 h-8 transition-colors\"\n";
            $html .= "                                     :class=\"rating >= $i ? 'text-yellow-400 fill-current' : 'text-gray-300'\"\n";
            $html .= "                                     fill=\"currentColor\" viewBox=\"0 0 20 20\">\n";
            $html .= "                                    <path d=\"M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z\"></path>\n";
            $html .= "                                </svg>\n";
            $html .= "                            </button>\n";
        }
        $html .= "                            <span class=\"ml-2 text-sm text-gray-600\" x-show=\"rating > 0\">\n";
        $html .= "                                <span x-text=\"rating\"></span> / $maxRating\n";
        $html .= "                            </span>\n";
        $html .= "                        </div>\n";
        $html .= "                    </div>\n\n";
        return $html;
    }

    protected function generateSelectField($field, $required, $requiredStar, $oldValue)
    {
        // Manejar options que pueden venir como string JSON o array
        $options = $field['options'] ?? [];
        
        // Si options es un string (JSON), decodificarlo
        if (is_string($options)) {
            $decoded = json_decode($options, true);
            $options = is_array($decoded) ? $decoded : [];
        }
        
        // Asegurar que options es un array
        if (!is_array($options)) {
            $options = [];
        }
        
        $html = "                    <div>\n";
        $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">" . htmlspecialchars($field['label']) . "$requiredStar</label>\n";
        $html .= "                        <select name=\"{$field['name']}\" $required\n";
        $html .= "                                class=\"w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500\">\n";
        $html .= "                            <option value=\"\">Seleccione...</option>\n";
        
        // Generar array de opciones para foreach de Blade
        if (!empty($options)) {
            $optionsList = [];
            foreach ($options as $option) {
                $optionsList[] = "'" . addslashes($option) . "'";
            }
            $optionsArrayString = '[' . implode(', ', $optionsList) . ']';
            $fieldName = $field['name'];
            
            $html .= "                            @foreach($optionsArrayString as \$opcion)\n";
            $html .= "                            <option value=\"{{ \$opcion }}\" {{ old('$fieldName') === \$opcion ? 'selected' : '' }}>{{ \$opcion }}</option>\n";
            $html .= "                            @endforeach\n";
        }
        
        $html .= "                        </select>\n";
        $html .= "                    </div>\n\n";
        return $html;
    }

    public function generateQR()
    {
        $link = url(route('pqrs.form.public'));
        return view('pqrs.qr', compact('link'));
    }

    public function deleteAttachment(Pqr $pqr, $index)
    {
        $adjuntos = $pqr->adjuntos ?? [];
        if (isset($adjuntos[$index])) {
            $this->deleteFile($adjuntos[$index]);
            unset($adjuntos[$index]);
            $pqr->adjuntos = array_values($adjuntos);
            $pqr->save();
        }
        return back()->with('success', 'Adjunto eliminado correctamente.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'fecha' => 'nullable|date',
            'nombre' => 'required|string|max:255',
            'vehiculo_placa' => 'nullable|string|max:20',
            'numero_tiquete' => 'nullable|string|max:50',
            'correo_electronico' => 'nullable|email|max:255',
            'numero_telefono' => 'nullable|string|max:20',
            'calificacion' => 'nullable|integer|min:1|max:5',
            'comentarios' => 'nullable|string',
            'tipo' => 'required|in:Peticiones,Quejas,Reclamos,Sugerencias,Otros',
            'estado' => 'nullable|in:Radicada,En Trámite,En Espera de Información,Resuelta,Cerrada',
            'usuario_asignado_id' => 'nullable|exists:users,id',
            'adjuntos.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,mp4,avi,mov|max:10240',
        ]);
    }

    protected function storeFile($file): string
    {
        $uploadPath = public_path('uploads/pqrs');

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return 'uploads/pqrs/' . $filename;
    }

    protected function deleteFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        $fullPath = public_path($path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    // ==================== MÉTODOS PARA PQRS TAQUILLA ====================

    public function publicFormTaquilla()
    {
        return view('pqrs.form-public-taquilla');
    }

    public function editFormTemplateTaquilla()
    {
        $configPath = storage_path('app/pqrs_taquilla_form_config.json');
        
        if (File::exists($configPath)) {
            $fields = json_decode(File::get($configPath), true);
            // Normalizar campos
            foreach ($fields as &$field) {
                if ($field['type'] === 'textarea' && !isset($field['rows'])) {
                    $field['rows'] = 4;
                }
                if ($field['type'] === 'rating' && !isset($field['max_rating'])) {
                    $field['max_rating'] = 5;
                }
                if ($field['type'] === 'file') {
                    $field['multiple'] = $field['multiple'] ?? true;
                    $field['accept'] = $field['accept'] ?? 'image/*,.pdf,.doc,.docx,video/*';
                    $field['help_text'] = $field['help_text'] ?? 'Formatos permitidos: Imágenes, Documentos, Videos. Máximo 10MB por archivo.';
                }
                if ($field['type'] === 'logo') {
                    $field['logo_path'] = $field['logo_path'] ?? '/images/logo.svg';
                }
            }
            unset($field);
        } else {
            $fields = $this->getDefaultFormFieldsTaquilla();
            File::put($configPath, json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        return view('pqrs.edit-template-taquilla', compact('fields'));
    }

    public function updateFormTemplateTaquilla(Request $request)
    {
        $request->validate([
            'fields' => 'required|array',
            'fields.*.name' => 'required|string',
            'fields.*.label' => 'required|string',
            'fields.*.type' => 'required|string',
        ]);

        $configPath = storage_path('app/pqrs_taquilla_form_config.json');
        $fields = $request->fields;
        
        // Procesar campos: decodificar options si vienen como JSON string
        foreach ($fields as &$field) {
            if (empty($field['id'])) {
                $field['id'] = uniqid('field_');
            }
            // Procesar options si viene como JSON string
            if (isset($field['options']) && is_string($field['options'])) {
                $decoded = json_decode($field['options'], true);
                $field['options'] = is_array($decoded) ? $decoded : [];
            }
            // Convertir required y enabled a boolean
            $field['required'] = isset($field['required']) && ($field['required'] == '1' || $field['required'] === true);
            $field['enabled'] = !isset($field['enabled']) || $field['enabled'] == '1' || $field['enabled'] === true;
            $field['order'] = isset($field['order']) ? (int)$field['order'] : 999;
            
            // Procesar campos específicos por tipo
            if ($field['type'] === 'file') {
                $field['multiple'] = isset($field['multiple']) && ($field['multiple'] == '1' || $field['multiple'] === true);
                $field['accept'] = $field['accept'] ?? null;
                $field['help_text'] = $field['help_text'] ?? null;
            }
            if ($field['type'] === 'rating') {
                $field['max_rating'] = isset($field['max_rating']) ? (int)$field['max_rating'] : 5;
            }
            if ($field['type'] === 'textarea') {
                $field['rows'] = isset($field['rows']) ? (int)$field['rows'] : 4;
            }
            if ($field['type'] === 'logo') {
                $field['logo_path'] = $field['logo_path'] ?? '/images/logo.svg';
            }
        }
        unset($field);

        File::put($configPath, json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->regenerateFormTemplateTaquilla($fields);

        return redirect()->route('pqrs.edit-template-taquilla')->with('success', 'Formulario de taquilla actualizado correctamente.');
    }

    public function storeTaquilla(Request $request)
    {
        $validated = $request->validate([
            'fecha' => 'nullable|date',
            'hora' => 'nullable',
            'nombre' => 'required|string|max:255',
            'sede' => 'nullable|string|max:255',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'tipo' => 'required|in:Peticiones,Quejas,Reclamos,Sugerencias,Otros',
            'calificacion' => 'nullable|integer|min:1|max:5',
            'comentario' => 'nullable|string',
            'adjuntos.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,mp4,avi,mov|max:10240',
        ]);

        // Manejo de adjuntos
        if ($request->hasFile('adjuntos')) {
            $adjuntos = [];
            foreach ($request->file('adjuntos') as $file) {
                $adjuntos[] = $this->storeFile($file);
            }
            $validated['adjuntos'] = $adjuntos;
        }

        // Si no se proporciona fecha/hora, usar ahora
        if (empty($validated['fecha'])) {
            $validated['fecha'] = now();
        }
        if (empty($validated['hora'])) {
            $validated['hora'] = now();
        }

        // Estado por defecto
        $validated['estado'] = 'Radicada';

        PqrTaquilla::create($validated);

        return redirect()->back()->with('success', 'PQRS de Taquilla enviado correctamente. ¡Gracias por tu feedback!');
    }

    protected function getDefaultFormFieldsTaquilla()
    {
        return [
            [
                'id' => 'logo',
                'name' => 'logo',
                'label' => 'Logo',
                'type' => 'logo',
                'required' => false,
                'order' => 0,
                'enabled' => true,
                'logo_path' => '/images/logo.svg',
            ],
            [
                'id' => 'tipo',
                'name' => 'tipo',
                'label' => 'Tipo',
                'type' => 'select',
                'required' => true,
                'placeholder' => 'Seleccione...',
                'value' => '',
                'order' => 1,
                'enabled' => true,
                'options' => ['Peticiones', 'Quejas', 'Reclamos', 'Sugerencias', 'Otros'],
            ],
            [
                'id' => 'fecha',
                'name' => 'fecha',
                'label' => 'Fecha',
                'type' => 'date',
                'required' => false,
                'placeholder' => '',
                'value' => date('Y-m-d'),
                'order' => 2,
                'enabled' => true,
            ],
            [
                'id' => 'hora',
                'name' => 'hora',
                'label' => 'Hora',
                'type' => 'time',
                'required' => false,
                'placeholder' => '',
                'value' => date('H:i'),
                'order' => 3,
                'enabled' => true,
            ],
            [
                'id' => 'nombre',
                'name' => 'nombre',
                'label' => 'Nombre',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Ingrese su nombre completo',
                'value' => '',
                'order' => 4,
                'enabled' => true,
            ],
            [
                'id' => 'sede',
                'name' => 'sede',
                'label' => 'Sede',
                'type' => 'text',
                'required' => false,
                'placeholder' => 'Ingrese la sede',
                'value' => '',
                'order' => 5,
                'enabled' => true,
            ],
            [
                'id' => 'correo',
                'name' => 'correo',
                'label' => 'Correo',
                'type' => 'email',
                'required' => false,
                'placeholder' => 'correo@ejemplo.com',
                'value' => '',
                'order' => 6,
                'enabled' => true,
            ],
            [
                'id' => 'telefono',
                'name' => 'telefono',
                'label' => 'Teléfono',
                'type' => 'tel',
                'required' => false,
                'placeholder' => 'Ingrese su número de teléfono',
                'value' => '',
                'order' => 7,
                'enabled' => true,
            ],
            [
                'id' => 'calificacion',
                'name' => 'calificacion',
                'label' => 'Califica el Servicio',
                'type' => 'rating',
                'required' => false,
                'placeholder' => '',
                'value' => 0,
                'order' => 8,
                'enabled' => true,
                'max_rating' => 5,
            ],
            [
                'id' => 'comentario',
                'name' => 'comentario',
                'label' => 'Comentario',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => 'Escriba sus comentarios aquí...',
                'value' => '',
                'order' => 9,
                'enabled' => true,
                'rows' => 4,
            ],
            [
                'id' => 'adjuntos',
                'name' => 'adjuntos',
                'label' => 'Adjuntos',
                'type' => 'file',
                'required' => false,
                'placeholder' => '',
                'value' => '',
                'order' => 10,
                'enabled' => true,
                'multiple' => true,
                'accept' => 'image/*,.pdf,.doc,.docx,video/*',
                'help_text' => 'Formatos permitidos: Imágenes (jpg, png), Documentos (pdf, doc, docx), Videos (mp4, avi, mov). Máximo 10MB por archivo.',
            ],
        ];
    }

    protected function regenerateFormTemplateTaquilla($fields)
    {
        usort($fields, function($a, $b) {
            return ($a['order'] ?? 999) - ($b['order'] ?? 999);
        });

        $templatePath = resource_path('views/pqrs/form-public-taquilla.blade.php');
        $template = $this->generateBladeTemplateTaquilla($fields);
        
        File::put($templatePath, $template);
    }

    protected function generateBladeTemplateTaquilla($fields)
    {
        $html = '<!DOCTYPE html>
<html lang="{{ str_replace(\'_\', \'-\', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Formulario PQRS Taquilla - Coopuertos</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite([\'resources/css/app.css\', \'resources/js/app.js\'])
</head>
<body class="font-sans text-gray-900 antialiased bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white shadow-lg rounded-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Formulario PQRS Taquilla</h1>
                <p class="text-gray-600 mb-6">Peticiones, Quejas, Reclamos y Sugerencias - Taquilla</p>

                @if (session(\'success\'))
                    <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                        {{ session(\'success\') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route(\'pqrs.taquilla.store\') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
';

        foreach ($fields as $field) {
            if (!($field['enabled'] ?? true)) continue;

            $required = ($field['required'] ?? false) ? 'required' : '';
            $requiredStar = ($field['required'] ?? false) ? ' <span class="text-red-500">*</span>' : '';
            $placeholder = !empty($field['placeholder']) ? 'placeholder="' . htmlspecialchars($field['placeholder']) . '"' : '';
            $oldValue = 'old(\'' . $field['name'] . '\'' . (!empty($field['value']) ? ', \'' . $field['value'] . '\'' : '') . ')';

            switch ($field['type']) {
                case 'logo':
                    $logoPath = $field['logo_path'] ?? '/images/logo.svg';
                    $html .= "                    <div class=\"flex justify-center my-6\">\n";
                    $html .= "                        <img src=\"$logoPath\" alt=\"Logo\" class=\"max-h-24 max-w-full object-contain\">\n";
                    $html .= "                    </div>\n\n";
                    break;
                case 'rating':
                    $maxRating = $field['max_rating'] ?? 5;
                    $html .= "                    <div>\n";
                    $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-2\">" . htmlspecialchars($field['label']) . "$requiredStar</label>\n";
                    $html .= "                        <div class=\"flex items-center space-x-2\" x-data=\"{ rating: {{ $oldValue }} }\">\n";
                    $html .= "                            <input type=\"hidden\" name=\"{$field['name']}\" x-model=\"rating\">\n";
                    for ($i = 1; $i <= $maxRating; $i++) {
                        $html .= "                            <button type=\"button\" @click=\"rating = $i\" class=\"focus:outline-none\">\n";
                        $html .= "                                <svg class=\"w-8 h-8 transition-colors\" :class=\"rating >= $i ? 'text-yellow-400 fill-current' : 'text-gray-300'\" fill=\"currentColor\" viewBox=\"0 0 20 20\">\n";
                        $html .= "                                    <path d=\"M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z\"></path>\n";
                        $html .= "                                </svg>\n";
                        $html .= "                            </button>\n";
                    }
                    $html .= "                            <span class=\"ml-2 text-sm text-gray-600\" x-show=\"rating > 0\"><span x-text=\"rating\"></span> / $maxRating</span>\n";
                    $html .= "                        </div>\n";
                    $html .= "                    </div>\n\n";
                    break;
                case 'select':
                    $options = $field['options'] ?? [];
                    // Asegurar que options es un array
                    if (is_string($options)) {
                        $decoded = json_decode($options, true);
                        $options = is_array($decoded) ? $decoded : [];
                    }
                    if (!is_array($options)) {
                        $options = [];
                    }
                    $html .= "                    <div>\n";
                    $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">" . htmlspecialchars($field['label']) . "$requiredStar</label>\n";
                    $html .= "                        <select name=\"{$field['name']}\" $required class=\"w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500\">\n";
                    $html .= "                            <option value=\"\">" . htmlspecialchars($field['placeholder'] ?? 'Seleccione...') . "</option>\n";
                    foreach ($options as $option) {
                        $html .= "                            <option value=\"" . htmlspecialchars($option) . "\" {{ $oldValue === '" . htmlspecialchars($option) . "' ? 'selected' : '' }}>" . htmlspecialchars($option) . "</option>\n";
                    }
                    $html .= "                        </select>\n";
                    $html .= "                    </div>\n\n";
                    break;
                case 'textarea':
                    $rows = $field['rows'] ?? 4;
                    $html .= "                    <div>\n";
                    $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">" . htmlspecialchars($field['label']) . "$requiredStar</label>\n";
                    $html .= "                        <textarea name=\"{$field['name']}\" rows=\"$rows\" $required $placeholder class=\"w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500\">{{ $oldValue }}</textarea>\n";
                    $html .= "                    </div>\n\n";
                    break;
                case 'file':
                    $multiple = ($field['multiple'] ?? false) ? 'multiple' : '';
                    $accept = !empty($field['accept']) ? 'accept="' . htmlspecialchars($field['accept']) . '"' : '';
                    $helpText = !empty($field['help_text']) ? "\n                        <p class=\"text-xs text-gray-500 mb-2\">" . htmlspecialchars($field['help_text']) . "</p>" : '';
                    $html .= "                    <div>\n";
                    $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">" . htmlspecialchars($field['label']) . "$requiredStar</label>$helpText\n";
                    $html .= "                        <input type=\"file\" name=\"{$field['name']}[]\" $multiple $accept $required class=\"w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500\">\n";
                    $html .= "                    </div>\n\n";
                    break;
                case 'date':
                case 'time':
                case 'text':
                case 'email':
                case 'tel':
                default:
                    $type = $field['type'] === 'text' ? 'text' : ($field['type'] === 'email' ? 'email' : ($field['type'] === 'tel' ? 'tel' : $field['type']));
                    $html .= "                    <div>\n";
                    $html .= "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">" . htmlspecialchars($field['label']) . "$requiredStar</label>\n";
                    $html .= "                        <input type=\"$type\" name=\"{$field['name']}\" value=\"{{ $oldValue }}\" $required $placeholder class=\"w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500\">\n";
                    $html .= "                    </div>\n\n";
                    break;
            }
        }

        $html .= '                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition">
                            Enviar PQRS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    // ==================== MÉTODOS CRUD PARA PQRS TAQUILLA ====================

    public function createTaquilla()
    {
        return view('pqrs.create-taquilla');
    }

    public function showTaquilla(PqrTaquilla $pqrTaquilla)
    {
        $pqrTaquilla->load(['usuarioAsignado']);
        return view('pqrs.show-taquilla', compact('pqrTaquilla'));
    }

    public function editTaquilla(PqrTaquilla $pqrTaquilla)
    {
        $pqrTaquilla->load(['usuarioAsignado']);
        return view('pqrs.edit-taquilla', compact('pqrTaquilla'));
    }

    public function updateTaquilla(Request $request, PqrTaquilla $pqrTaquilla)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'hora' => 'required',
            'nombre' => 'required|string|max:255',
            'sede' => 'nullable|string|max:255',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'tipo' => 'required|in:Peticiones,Quejas,Reclamos,Sugerencias,Otros',
            'calificacion' => 'nullable|integer|min:1|max:5',
            'comentario' => 'nullable|string',
            'estado' => 'required|in:Radicada,En Trámite,En Espera de Información,Resuelta,Cerrada',
            'usuario_asignado_id' => 'nullable|exists:users,id',
            'adjuntos.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,mp4,avi,mov|max:10240',
        ]);

        // Manejo de nuevos adjuntos
        if ($request->hasFile('adjuntos')) {
            $adjuntos = $pqrTaquilla->adjuntos ?? [];
            foreach ($request->file('adjuntos') as $file) {
                $adjuntos[] = $this->storeFile($file);
            }
            $validated['adjuntos'] = $adjuntos;
        }

        $pqrTaquilla->update($validated);

        return redirect()->route('pqrs.index')->with('success', 'PQRS de Taquilla actualizado correctamente.');
    }

    public function destroyTaquilla(PqrTaquilla $pqrTaquilla)
    {
        // Eliminar adjuntos
        if ($pqrTaquilla->adjuntos) {
            foreach ($pqrTaquilla->adjuntos as $adjunto) {
                $this->deleteFile($adjunto);
            }
        }

        $pqrTaquilla->delete();

        return redirect()->route('pqrs.index')->with('success', 'PQRS de Taquilla eliminado correctamente.');
    }

    public function generateQRTaquilla()
    {
        $url = url(route('pqrs.form.taquilla'));
        return view('pqrs.qr-taquilla', compact('url'));
    }
}
