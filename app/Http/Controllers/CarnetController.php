<?php

namespace App\Http\Controllers;

use App\Models\CarnetTemplate;
use App\Models\CarnetDownload;
use App\Models\Conductor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class CarnetController extends Controller
{
    public function index()
    {
        $template = CarnetTemplate::where('activo', true)->first();
        $conductores = \App\Models\Conductor::all();
        return view('carnets.index', compact('template', 'conductores'));
    }

    public function personalizar()
    {
        $template = CarnetTemplate::where('activo', true)->first();
        
        // Variables disponibles del modelo Conductor
        $variables = [
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'nombre_completo' => 'Nombre Completo',
            'cedula' => 'Cédula',
            'conductor_tipo' => 'Tipo Conductor (A/B)',
            'rh' => 'Tipo de Sangre (RH)',
            'numero_interno' => 'Número Interno',
            'celular' => 'Celular',
            'correo' => 'Correo',
            'fecha_nacimiento' => 'Fecha de Nacimiento',
            'nivel_estudios' => 'Nivel de Estudios',
            'otra_profesion' => 'Otra Profesión',
            'estado' => 'Estado',
            'foto' => 'Foto',
            'vehiculo_placa' => 'Placa del Vehículo',
            'vehiculo_marca' => 'Marca del Vehículo',
            'vehiculo_modelo' => 'Modelo del Vehículo',
            'qr_code' => 'Código QR',
        ];

        // Inicializar configuración de variables
        $variablesConfig = [];
        if ($template && $template->variables_config) {
            $variablesConfig = $template->variables_config;
        }
        
        // Asegurar que todas las variables tengan configuración
        foreach ($variables as $key => $label) {
            if (!isset($variablesConfig[$key])) {
                if ($key === 'foto' || $key === 'qr_code') {
                    $variablesConfig[$key] = [
                        'activo' => false,
                        'x' => null,
                        'y' => null,
                        'size' => 100 // Tamaño por defecto para foto y QR
                    ];
                } else {
                    $variablesConfig[$key] = [
                        'activo' => false,
                        'x' => null,
                        'y' => null,
                        'fontSize' => 14,
                        'color' => '#000000',
                        'fontFamily' => 'Arial',
                        'fontStyle' => 'normal',
                        'centrado' => false
                    ];
                }
            }
        }

        return view('carnets.personalizar', compact('template', 'variables', 'variablesConfig'));
    }

    public function guardarPlantilla(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'imagen_plantilla' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'variables_config' => 'required|json',
        ]);

        // Desactivar todas las plantillas anteriores
        CarnetTemplate::where('activo', true)->update(['activo' => false]);

        // Manejo de la imagen si se sube
        $imagenPath = null;
        if ($request->hasFile('imagen_plantilla')) {
            $imagenPath = $this->storeImage($request->file('imagen_plantilla'));
        } else {
            // Si no se sube nueva imagen, mantener la anterior si existe
            $templateAnterior = CarnetTemplate::latest()->first();
            if ($templateAnterior && $templateAnterior->imagen_plantilla) {
                $imagenPath = $templateAnterior->imagen_plantilla;
            }
        }

        // Decodificar variables_config
        $variablesConfig = json_decode($validated['variables_config'], true);

        // Crear o actualizar plantilla
        $template = CarnetTemplate::create([
            'nombre' => $validated['nombre'] ?? 'Plantilla Principal',
            'imagen_plantilla' => $imagenPath,
            'variables_config' => $variablesConfig,
            'activo' => true,
        ]);

        return redirect()->route('carnets.index')
            ->with('success', 'Plantilla de carnet guardada correctamente.');
    }

    protected function storeImage($file): string
    {
        $uploadPath = public_path('uploads/carnets');

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return 'uploads/carnets/' . $filename;
    }

    public function descargarTodos()
    {
        try {
            $template = CarnetTemplate::where('activo', true)->first();
            
            if (!$template || !$template->imagen_plantilla) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay plantilla configurada para generar los carnets'
                ], 400);
            }
            
            $conductores = Conductor::all();
            
            if ($conductores->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay conductores para generar carnets'
                ], 400);
            }
            
            // Crear registro de descarga
            $sessionId = Str::uuid()->toString();
            $download = CarnetDownload::create([
                'session_id' => $sessionId,
                'total' => $conductores->count(),
                'procesados' => 0,
                'estado' => 'procesando',
            ]);
            
            // Procesar en segundo plano
            // Usar fastcgi_finish_request si está disponible, sino usar register_shutdown_function
            if (function_exists('fastcgi_finish_request')) {
                // Para servidores FastCGI, terminar la respuesta primero
                fastcgi_finish_request();
                // Luego procesar
                try {
                    $this->procesarCarnets($sessionId, $template, $conductores);
                } catch (\Exception $e) {
                    Log::error('Error en procesamiento: ' . $e->getMessage());
                }
            } else {
                // Para otros servidores, usar register_shutdown_function
                register_shutdown_function(function() use ($sessionId, $template, $conductores) {
                    try {
                        $controller = new CarnetController();
                        $reflection = new \ReflectionClass($controller);
                        $method = $reflection->getMethod('procesarCarnets');
                        $method->setAccessible(true);
                        $method->invoke($controller, $sessionId, $template, $conductores);
                    } catch (\Exception $e) {
                        Log::error('Error en procesamiento en segundo plano: ' . $e->getMessage());
                    }
                });
            }
            
            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'total' => $conductores->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error en descargarTodos: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la descarga: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function procesarCarnetsAsync($sessionId, $template, $conductores)
    {
        // Asegurar que el directorio temporal existe
        $tempDir = storage_path('app/temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        
        // Procesar en segundo plano usando exec para Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $phpPath = PHP_BINARY;
            $scriptPath = base_path('artisan');
            
            // Crear script batch temporal para ejecutar en segundo plano
            $batchScript = $tempDir . '/run_carnets_' . $sessionId . '.bat';
            $batchContent = "@echo off\n";
            $batchContent .= "cd /d \"" . base_path() . "\"\n";
            $batchContent .= "\"$phpPath\" \"$scriptPath\" carnet:generar-masivo \"$sessionId\"\n";
            $batchContent .= "if exist \"%~f0\" del \"%~f0\"\n"; // Auto-eliminar el script
            
            File::put($batchScript, $batchContent);
            
            // Ejecutar en segundo plano
            $command = 'start /B "" "' . $batchScript . '"';
            pclose(popen($command, "r"));
        } else {
            // Para Linux/Unix
            $phpPath = escapeshellarg(PHP_BINARY);
            $scriptPath = escapeshellarg(base_path('artisan'));
            $sessionIdEscaped = escapeshellarg($sessionId);
            exec("$phpPath $scriptPath carnet:generar-masivo $sessionIdEscaped > /dev/null 2>&1 &");
        }
    }

    protected function procesarCarnets($sessionId, $template, $conductores)
    {
        $tempDir = storage_path('app/temp/carnets_' . $sessionId);
        File::makeDirectory($tempDir, 0755, true);
        
        $download = CarnetDownload::where('session_id', $sessionId)->first();
        
        try {
            $procesados = 0;
            
            foreach ($conductores as $index => $conductor) {
                try {
                    // Generar imagen del carnet para cada conductor
                    $this->generarCarnetPDF($conductor, $template, $tempDir);
                    $procesados++;
                    
                    // Actualizar progreso después de cada carnet
                    $download->refresh();
                    $download->procesados = $procesados;
                    $download->save();
                    
                    // Pequeña pausa cada 5 carnets para no sobrecargar el servidor
                    if (($index + 1) % 5 == 0) {
                        usleep(50000); // 0.05 segundos cada 5 carnets
                    }
                } catch (\Exception $e) {
                    // Continuar con el siguiente conductor si hay error
                    \Log::error('Error generando carnet para conductor ' . $conductor->id . ': ' . $e->getMessage());
                    continue;
                }
            }
            
            // Crear archivo ZIP con todos los carnets
            $zipPath = storage_path('app/temp/carnets_' . $sessionId . '.zip');
            $zip = new ZipArchive();
            
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $files = File::files($tempDir);
                foreach ($files as $file) {
                    // Agregar archivo al ZIP con nombre más descriptivo
                    $zip->addFile($file->getPathname(), $file->getFilename());
                }
                $zip->close();
                
                // Mover ZIP a storage público
                $publicZipDir = public_path('storage/carnets');
                if (!File::exists($publicZipDir)) {
                    File::makeDirectory($publicZipDir, 0755, true);
                }
                
                $publicZipPath = $publicZipDir . '/carnets_' . $sessionId . '.zip';
                if (File::exists($zipPath)) {
                    File::move($zipPath, $publicZipPath);
                }
                
                // Actualizar registro
                $download->refresh();
                $download->estado = 'completado';
                $download->archivo_zip = 'carnets/carnets_' . $sessionId . '.zip';
                $download->save();
                
                // Limpiar archivos temporales
                if (File::exists($tempDir)) {
                    File::deleteDirectory($tempDir);
                }
            } else {
                throw new \Exception('No se pudo crear el archivo ZIP');
            }
            
        } catch (\Exception $e) {
            $download->estado = 'error';
            $download->error = $e->getMessage();
            $download->save();
            
            // Limpiar en caso de error
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    protected function generarCarnetPDF($conductor, $template, $tempDir)
    {
        // Preparar datos del conductor
        $vehiculo = $conductor->asignacionActiva && $conductor->asignacionActiva->vehicle 
            ? $conductor->asignacionActiva->vehicle 
            : null;
        
        $fotoUrl = null;
        if ($conductor->foto) {
            if (Str::startsWith($conductor->foto, 'uploads/')) {
                $fotoUrl = public_path($conductor->foto);
            } else {
                $fotoUrl = storage_path('app/public/' . $conductor->foto);
            }
        }
        
        $datosConductor = [
            'nombres' => $conductor->nombres,
            'apellidos' => $conductor->apellidos,
            'nombre_completo' => $conductor->nombres . ' ' . $conductor->apellidos,
            'cedula' => $conductor->cedula,
            'conductor_tipo' => $conductor->conductor_tipo,
            'rh' => $conductor->rh,
            'numero_interno' => $conductor->numero_interno ?? '',
            'celular' => $conductor->celular ?? '',
            'correo' => $conductor->correo ?? '',
            'fecha_nacimiento' => $conductor->fecha_nacimiento ? $conductor->fecha_nacimiento->format('d/m/Y') : '',
            'nivel_estudios' => $conductor->nivel_estudios ?? '',
            'otra_profesion' => $conductor->otra_profesion ?? '',
            'estado' => ucfirst($conductor->estado),
            'foto' => $fotoUrl,
            'vehiculo_placa' => $vehiculo ? $vehiculo->placa : 'Sin asignar',
            'vehiculo_marca' => $vehiculo ? $vehiculo->marca : '',
            'vehiculo_modelo' => $vehiculo ? $vehiculo->modelo : '',
            'qr_code' => route('conductor.public', $conductor->uuid),
        ];
        
        // Generar imagen del carnet usando GD o Imagick
        $templateImagePath = public_path($template->imagen_plantilla);
        
        if (!File::exists($templateImagePath)) {
            throw new \Exception("No se encontró la imagen de plantilla");
        }
        
        // Crear imagen desde plantilla
        $imageInfo = getimagesize($templateImagePath);
        $imageType = $imageInfo[2];
        
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $templateImage = imagecreatefromjpeg($templateImagePath);
                break;
            case IMAGETYPE_PNG:
                $templateImage = imagecreatefrompng($templateImagePath);
                break;
            case IMAGETYPE_GIF:
                $templateImage = imagecreatefromgif($templateImagePath);
                break;
            default:
                throw new \Exception("Formato de imagen no soportado");
        }
        
        $width = imagesx($templateImage);
        $height = imagesy($templateImage);
        
        // Renderizar variables sobre la imagen
        $variablesConfig = $template->variables_config;
        foreach ($variablesConfig as $key => $config) {
            if (isset($config['activo']) && $config['activo'] && isset($config['x']) && isset($config['y'])) {
                $value = $datosConductor[$key] ?? '';
                
                if ($key === 'foto' && $fotoUrl && File::exists($fotoUrl)) {
                    // Dibujar foto
                    $fotoSize = $config['size'] ?? 100;
                    $fotoImg = $this->loadImage($fotoUrl);
                    if ($fotoImg) {
                        imagecopyresampled(
                            $templateImage, $fotoImg,
                            $config['x'], $config['y'], 0, 0,
                            $fotoSize, $fotoSize,
                            imagesx($fotoImg), imagesy($fotoImg)
                        );
                        imagedestroy($fotoImg);
                    }
                } elseif ($key === 'qr_code') {
                    // Generar QR code
                    $qrSize = $config['size'] ?? 100;
                    try {
                        // Generar QR como SVG directamente (no requiere imagick)
                        $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size($qrSize)
                            ->format('svg')
                            ->generate($datosConductor['qr_code']);
                        
                        // Guardar SVG temporalmente para debug (opcional)
                        $qrTempPathSvg = $tempDir . '/qr_' . $conductor->id . '_debug.svg';
                        File::put($qrTempPathSvg, $qrCodeSvg);
                        
                        // Convertir SVG a imagen GD
                        $qrImage = $this->renderSvgToGd($qrCodeSvg, $qrSize);
                        
                        if ($qrImage) {
                            imagecopyresampled(
                                $templateImage, $qrImage,
                                $config['x'], $config['y'], 0, 0,
                                $qrSize, $qrSize,
                                imagesx($qrImage), imagesy($qrImage)
                            );
                            imagedestroy($qrImage);
                            Log::info('QR generado exitosamente para conductor ' . $conductor->id);
                        } else {
                            Log::warning('No se pudo renderizar SVG QR para conductor ' . $conductor->id . '. SVG guardado en: ' . $qrTempPathSvg);
                        }
                        
                        // Limpiar archivo temporal de debug después de un tiempo
                        // (por ahora lo dejamos para debug)
                    } catch (\Exception $e) {
                        Log::warning('Error generando QR para conductor ' . $conductor->id . ': ' . $e->getMessage());
                        Log::warning('Stack trace: ' . $e->getTraceAsString());
                        // Si falla el QR, continuar sin él
                    }
                } elseif ($key !== 'foto' && $value !== '') {
                    // Dibujar texto con tipografías personalizadas
                    $fontSize = $config['fontSize'] ?? 14;
                    $fontFamily = $config['fontFamily'] ?? 'Arial';
                    $fontStyle = $config['fontStyle'] ?? 'normal';
                    $color = $this->hexToRgb($config['color'] ?? '#000000');
                    $textColor = imagecolorallocate($templateImage, $color['r'], $color['g'], $color['b']);
                    
                    // Obtener ruta de fuente según familia
                    $fontPath = $this->getFontPath($fontFamily, $fontStyle);
                    
                    $x = $config['x'];
                    if (isset($config['centrado']) && $config['centrado']) {
                        // Calcular ancho del texto para centrarlo
                        if ($fontPath && File::exists($fontPath)) {
                            $bbox = imagettfbbox($fontSize, 0, $fontPath, $value);
                            if ($bbox) {
                                $textWidth = $bbox[4] - $bbox[0];
                                $x = ($width - $textWidth) / 2;
                            }
                        } else {
                            // Aproximación para fuente built-in
                            $textWidth = strlen($value) * imagefontwidth(5);
                            $x = ($width - $textWidth) / 2;
                        }
                    }
                    
                    // Usar fuente TrueType si está disponible
                    // GD usa la fuente TTF correcta según el estilo (bold, italic, etc.)
                    if ($fontPath && File::exists($fontPath)) {
                        imagettftext($templateImage, $fontSize, 0, $x, $config['y'], $textColor, $fontPath, $value);
                    } else {
                        // Usar fuente built-in como fallback
                        imagestring($templateImage, 5, $x, $config['y'], $value, $textColor);
                    }
                }
            }
        }
        
        // Guardar imagen temporal como PNG
        $imagePath = $tempDir . '/carnet_' . $conductor->cedula . '.png';
        imagepng($templateImage, $imagePath, 9); // Máxima calidad
        imagedestroy($templateImage);
        
        // Convertir PNG a PDF usando DomPDF
        $pdfPath = $tempDir . '/carnet_' . $conductor->cedula . '.pdf';
        try {
            $this->convertirImagenAPDF($imagePath, $pdfPath, $width, $height);
            // Eliminar PNG temporal después de crear PDF
            if (File::exists($pdfPath) && File::exists($imagePath)) {
                File::delete($imagePath);
            }
            return $pdfPath;
        } catch (\Exception $e) {
            // Si falla la conversión a PDF, mantener el PNG y renombrarlo
            Log::warning('Error convirtiendo a PDF para conductor ' . $conductor->cedula . ', usando PNG: ' . $e->getMessage());
            // Renombrar PNG a PDF para mantener consistencia en el ZIP
            $pngAsPdf = $tempDir . '/carnet_' . $conductor->cedula . '.pdf';
            File::copy($imagePath, $pngAsPdf);
            File::delete($imagePath);
            return $pngAsPdf;
        }
    }

    protected function loadImage($path)
    {
        if (!File::exists($path)) {
            return null;
        }
        
        $imageInfo = getimagesize($path);
        $imageType = $imageInfo[2];
        
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                return null;
        }
    }

    protected function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }

    protected function renderSvgToGd($svgContent, $size)
    {
        try {
            // Extraer el viewBox del SVG para obtener las dimensiones originales
            preg_match('/viewBox="([^"]*)"/i', $svgContent, $viewBoxMatch);
            $viewBox = $viewBoxMatch[1] ?? '0 0 200 200';
            $viewBoxParts = preg_split('/\s+/', trim($viewBox));
            $svgWidth = isset($viewBoxParts[2]) ? (float)$viewBoxParts[2] : 200;
            $svgHeight = isset($viewBoxParts[3]) ? (float)$viewBoxParts[3] : 200;
            
            Log::info("SVG viewBox: $viewBox, Width: $svgWidth, Height: $svgHeight, Target size: $size");
            
            // Extraer todos los rectángulos del SVG (con diferentes formatos posibles)
            // Patrón más flexible para capturar rectángulos
            preg_match_all('/<rect[^>]*>/i', $svgContent, $rectMatches);
            
            if (empty($rectMatches[0])) {
                Log::warning('No se encontraron elementos <rect> en el SVG');
                return null;
            }
            
            Log::info('Encontrados ' . count($rectMatches[0]) . ' rectángulos en el SVG');
            
            // Crear imagen en blanco
            $image = imagecreatetruecolor($size, $size);
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            imagefill($image, 0, 0, $white);
            
            // Calcular factor de escala
            $scaleX = $size / $svgWidth;
            $scaleY = $size / $svgHeight;
            
            $rectsDrawn = 0;
            
            // Procesar cada rectángulo
            foreach ($rectMatches[0] as $rectTag) {
                // Extraer atributos del rectángulo
                preg_match('/x="([^"]*)"/i', $rectTag, $xMatch);
                preg_match('/y="([^"]*)"/i', $rectTag, $yMatch);
                preg_match('/width="([^"]*)"/i', $rectTag, $wMatch);
                preg_match('/height="([^"]*)"/i', $rectTag, $hMatch);
                preg_match('/fill="([^"]*)"/i', $rectTag, $fillMatch);
                
                if (isset($xMatch[1]) && isset($yMatch[1]) && isset($wMatch[1]) && isset($hMatch[1])) {
                    $x = (float)$xMatch[1];
                    $y = (float)$yMatch[1];
                    $w = (float)$wMatch[1];
                    $h = (float)$hMatch[1];
                    $fill = isset($fillMatch[1]) ? strtolower(trim($fillMatch[1])) : '';
                    
                    // Si no hay fill explícito, verificar si hay un fill por defecto en el SVG o asumir negro
                    if (empty($fill)) {
                        // Buscar fill por defecto en el SVG
                        preg_match('/fill="([^"]*)"/i', $svgContent, $defaultFill);
                        $fill = isset($defaultFill[1]) ? strtolower(trim($defaultFill[1])) : 'black';
                    }
                    
                    // Escalar coordenadas al tamaño deseado
                    $xScaled = (int)($x * $scaleX);
                    $yScaled = (int)($y * $scaleY);
                    $wScaled = max(1, (int)($w * $scaleX));
                    $hScaled = max(1, (int)($h * $scaleY));
                    
                    // Dibujar si es negro o si no hay fill especificado (asumir negro para QR)
                    // Los QR codes tienen fondo blanco y módulos negros
                    $isBlack = ($fill === '#000000' || $fill === 'black' || $fill === '#000' || 
                               $fill === 'rgb(0,0,0)' || $fill === 'none' || empty($fill));
                    
                    // También dibujar si el fill no es blanco (puede ser que no tenga fill y sea negro por defecto)
                    $isNotWhite = ($fill !== '#ffffff' && $fill !== 'white' && $fill !== '#fff' && 
                                  $fill !== 'rgb(255,255,255)');
                    
                    if ($isBlack || (empty($fill) && $isNotWhite)) {
                        imagefilledrectangle($image, $xScaled, $yScaled, $xScaled + $wScaled - 1, $yScaled + $hScaled - 1, $black);
                        $rectsDrawn++;
                        Log::debug("Dibujado rect: x=$xScaled, y=$yScaled, w=$wScaled, h=$hScaled, fill='$fill'");
                    } else {
                        Log::debug("Rectángulo omitido: fill='$fill' (no es negro)");
                    }
                }
            }
            
            // Si no se encontraron rectángulos, intentar con paths (más común en QR codes SVG)
            if ($rectsDrawn === 0) {
                Log::info('No se encontraron rectángulos, buscando paths en el SVG');
                
                // Extraer transformaciones del grupo <g>
                $scale = 1.0;
                $translateX = 0;
                $translateY = 0;
                
                preg_match('/<g[^>]*transform="[^"]*scale\(([^)]+)\)[^"]*"/i', $svgContent, $scaleMatch);
                if (isset($scaleMatch[1])) {
                    $scale = (float)$scaleMatch[1];
                }
                
                preg_match('/<g[^>]*transform="[^"]*translate\(([^,]+),([^)]+)\)[^"]*"/i', $svgContent, $translateMatch);
                if (isset($translateMatch[1]) && isset($translateMatch[2])) {
                    $translateX = (float)$translateMatch[1];
                    $translateY = (float)$translateMatch[2];
                }
                
                Log::info("Transformaciones encontradas: scale=$scale, translate=($translateX, $translateY)");
                
                // Los QR codes SVG suelen usar paths con comandos como "M8 0L8 1L10 1L10 0Z"
                preg_match_all('/<path[^>]*d="([^"]*)"[^>]*>/i', $svgContent, $pathMatches);
                
                Log::info('Encontrados ' . count($pathMatches[1]) . ' paths en el SVG');
                
                foreach ($pathMatches[1] as $pathIndex => $pathData) {
                    Log::debug("Procesando path $pathIndex: " . substr($pathData, 0, 100));
                    
                    // Parsear comandos del path - formato: "M8 0L8 1L10 1L10 0Z" (sin espacios entre comando y número)
                    // Normalizar: agregar espacios entre comandos y números, y entre números y comandos
                    $pathData = preg_replace('/([MLHVZ])([-\d.])/', '$1 $2', $pathData);
                    $pathData = preg_replace('/([-\d.])([MLHVZ])/', '$1 $2', $pathData);
                    $pathData = preg_replace('/([-\d.])([-\d.])/', '$1 $2', $pathData); // Separar números consecutivos
                    
                    // Parsear comandos
                    preg_match_all('/([MLHVZ])\s*([-\d.\s]+)/i', $pathData, $commands);
                    
                    Log::debug("Comandos encontrados en path: " . count($commands[0]));
                    
                    $currentX = 0;
                    $currentY = 0;
                    $polygonPoints = [];
                    
                    // Función helper para dibujar un polígono
                    $drawPolygon = function($points) use ($image, $black, $scaleX, $scaleY, &$rectsDrawn) {
                        if (count($points) >= 3) {
                            $xCoords = array_column($points, 0);
                            $yCoords = array_column($points, 1);
                            $minX = min($xCoords);
                            $minY = min($yCoords);
                            $maxX = max($xCoords);
                            $maxY = max($yCoords);
                            
                            $xScaled = (int)($minX * $scaleX);
                            $yScaled = (int)($minY * $scaleY);
                            $wScaled = max(1, (int)(($maxX - $minX) * $scaleX));
                            $hScaled = max(1, (int)(($maxY - $minY) * $scaleY));
                            
                            imagefilledrectangle($image, $xScaled, $yScaled, $xScaled + $wScaled - 1, $yScaled + $hScaled - 1, $black);
                            $rectsDrawn++;
                            return true;
                        }
                        return false;
                    };
                    
                    for ($i = 0; $i < count($commands[0]); $i++) {
                        $cmd = strtoupper($commands[1][$i]);
                        $coordsStr = trim($commands[2][$i]);
                        $coords = preg_split('/[\s,]+/', $coordsStr);
                        $coords = array_filter($coords, function($v) { return $v !== '' && $v !== ' '; });
                        $coords = array_values($coords);
                        
                        if ($cmd === 'M' && count($coords) >= 2) {
                            // Si hay un polígono en progreso, cerrarlo primero
                            if (count($polygonPoints) >= 3) {
                                $drawPolygon($polygonPoints);
                            }
                            
                            // Move to - inicio de un nuevo polígono
                            $currentX = ((float)$coords[0] * $scale) + $translateX;
                            $currentY = ((float)$coords[1] * $scale) + $translateY;
                            $polygonPoints = [[$currentX, $currentY]];
                        } elseif ($cmd === 'L' && count($coords) >= 2) {
                            // Line to - puede tener múltiples pares de coordenadas
                            for ($j = 0; $j < count($coords) - 1; $j += 2) {
                                if (isset($coords[$j]) && isset($coords[$j+1])) {
                                    $currentX = ((float)$coords[$j] * $scale) + $translateX;
                                    $currentY = ((float)$coords[$j+1] * $scale) + $translateY;
                                    $polygonPoints[] = [$currentX, $currentY];
                                }
                            }
                        } elseif ($cmd === 'H' && count($coords) >= 1) {
                            // Horizontal line
                            $currentX = ((float)$coords[0] * $scale) + $translateX;
                            $polygonPoints[] = [$currentX, $currentY];
                        } elseif ($cmd === 'V' && count($coords) >= 1) {
                            // Vertical line
                            $currentY = ((float)$coords[0] * $scale) + $translateY;
                            $polygonPoints[] = [$currentX, $currentY];
                        } elseif ($cmd === 'Z') {
                            // Close path - dibujar el polígono y reiniciar
                            $drawPolygon($polygonPoints);
                            $polygonPoints = [];
                        }
                    }
                    
                    // Dibujar el último polígono si no se cerró con Z
                    if (count($polygonPoints) >= 3) {
                        $drawPolygon($polygonPoints);
                    }
                }
            }
            
            Log::info("Dibujados $rectsDrawn elementos en la imagen QR");
            
            if ($rectsDrawn > 0) {
                return $image;
            } else {
                imagedestroy($image);
                Log::warning('No se dibujaron elementos en el QR. Primeros 500 caracteres del SVG: ' . substr($svgContent, 0, 500));
                return null;
            }
        } catch (\Exception $e) {
            Log::warning('Error en renderSvgToGd: ' . $e->getMessage());
            Log::warning('Stack trace: ' . $e->getTraceAsString());
        }
        
        return null;
    }

    protected function getFontPath($fontFamily, $fontStyle = 'normal')
    {
        // Mapear familias de fuentes a nombres de archivos en Windows
        $fontMap = [
            'Arial' => ['regular' => 'arial.ttf', 'bold' => 'arialbd.ttf', 'italic' => 'ariali.ttf', 'bold italic' => 'arialbi.ttf'],
            'Helvetica' => ['regular' => 'arial.ttf', 'bold' => 'arialbd.ttf', 'italic' => 'ariali.ttf', 'bold italic' => 'arialbi.ttf'],
            'Times New Roman' => ['regular' => 'times.ttf', 'bold' => 'timesbd.ttf', 'italic' => 'timesi.ttf', 'bold italic' => 'timesbi.ttf'],
            'Courier New' => ['regular' => 'cour.ttf', 'bold' => 'courbd.ttf', 'italic' => 'couri.ttf', 'bold italic' => 'courbi.ttf'],
            'Verdana' => ['regular' => 'verdana.ttf', 'bold' => 'verdanab.ttf', 'italic' => 'verdanai.ttf', 'bold italic' => 'verdanaz.ttf'],
            'Century Gothic' => ['regular' => 'gothic.ttf', 'bold' => 'gothicb.ttf', 'italic' => 'gothici.ttf', 'bold italic' => 'gothicbi.ttf'],
        ];
        
        // Normalizar estilo
        $style = strtolower($fontStyle);
        if ($style === 'regular' || $style === 'normal') {
            $style = 'regular';
        } elseif ($style === 'bold') {
            $style = 'bold';
        } elseif ($style === 'italic' || $style === 'Italic') {
            $style = 'italic';
        } elseif ($style === 'bold italic' || $style === 'Bold Italic') {
            $style = 'bold italic';
        }
        
        // Obtener nombre de archivo según familia y estilo
        $fontFile = null;
        if (isset($fontMap[$fontFamily][$style])) {
            $fontFile = $fontMap[$fontFamily][$style];
        } elseif (isset($fontMap[$fontFamily]['regular'])) {
            $fontFile = $fontMap[$fontFamily]['regular'];
        }
        
        // Rutas posibles para las fuentes
        $possiblePaths = [];
        
        if ($fontFile) {
            // Primero buscar en public/fonts
            $possiblePaths[] = public_path("fonts/{$fontFile}");
            $possiblePaths[] = public_path("fonts/" . strtolower($fontFile));
            $possiblePaths[] = public_path("fonts/" . ucfirst($fontFile));
            
            // Luego buscar en Windows/Fonts
            $possiblePaths[] = 'C:/Windows/Fonts/' . $fontFile;
            $possiblePaths[] = 'C:/Windows/Fonts/' . strtolower($fontFile);
            $possiblePaths[] = 'C:/Windows/Fonts/' . ucfirst($fontFile);
            
            // También buscar con el nombre completo de la familia
            $possiblePaths[] = 'C:/Windows/Fonts/' . str_replace(' ', '', $fontFamily) . ($style !== 'regular' ? ' ' . ucwords($style) : '') . '.ttf';
        }
        
        // Buscar la primera fuente disponible
        foreach ($possiblePaths as $path) {
            if ($path && File::exists($path)) {
                return $path;
            }
        }
        
        // Si no se encuentra, intentar con Arial regular como fallback
        $arialPaths = [
            public_path('fonts/arial.ttf'),
            public_path('fonts/Arial.ttf'),
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/Arial.ttf',
            'C:/Windows/Fonts/ARIAL.TTF',
        ];
        
        foreach ($arialPaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }
        
        // Si no hay ninguna fuente, retornar null para usar fuente built-in
        return null;
    }

    protected function convertirImagenAPDF($imagePath, $pdfPath, $width, $height)
    {
        // Calcular dimensiones en mm (asumiendo 300 DPI)
        $dpi = 300;
        $mmPerInch = 25.4;
        $widthMM = ($width / $dpi) * $mmPerInch;
        $heightMM = ($height / $dpi) * $mmPerInch;
        
        // Leer imagen como base64
        $imageData = base64_encode(File::get($imagePath));
        $imageBase64 = 'data:image/png;base64,' . $imageData;
        
        // Crear HTML con la imagen
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: ' . $widthMM . 'mm ' . $heightMM . 'mm;
        }
        body {
            margin: 0;
            padding: 0;
            width: ' . $widthMM . 'mm;
            height: ' . $heightMM . 'mm;
        }
        img {
            width: ' . $widthMM . 'mm;
            height: ' . $heightMM . 'mm;
            display: block;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <img src="' . $imageBase64 . '" />
</body>
</html>';
        
        // Configurar DomPDF
        try {
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper([0, 0, $widthMM, $heightMM], 'portrait');
            $dompdf->render();
            
            // Guardar PDF
            File::put($pdfPath, $dompdf->output());
        } catch (\Exception $e) {
            Log::error('Error convirtiendo imagen a PDF: ' . $e->getMessage());
            // Si falla la conversión, lanzar excepción para que se maneje arriba
            throw $e;
        }
    }

    public function obtenerProgreso($sessionId)
    {
        $download = CarnetDownload::where('session_id', $sessionId)->first();
        
        if (!$download) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada'
            ], 404);
        }
        
        $archivoUrl = null;
        if ($download->archivo_zip) {
            $archivoUrl = asset('storage/' . $download->archivo_zip);
        }
        
        return response()->json([
            'success' => true,
            'total' => $download->total,
            'procesados' => $download->procesados,
            'estado' => $download->estado,
            'progreso' => $download->total > 0 ? round(($download->procesados / $download->total) * 100, 2) : 0,
            'archivo' => $archivoUrl,
            'error' => $download->error
        ]);
    }

    public function descargarZip($sessionId)
    {
        $download = CarnetDownload::where('session_id', $sessionId)->first();
        
        if (!$download || $download->estado !== 'completado' || !$download->archivo_zip) {
            return redirect()->route('carnets.index')
                ->with('error', 'El archivo ZIP no está disponible');
        }
        
        $filePath = public_path('storage/' . $download->archivo_zip);
        
        if (!File::exists($filePath)) {
            return redirect()->route('carnets.index')
                ->with('error', 'El archivo ZIP no se encontró');
        }
        
        return response()->download($filePath, 'carnets_' . date('YmdHis') . '.zip')
            ->deleteFileAfterSend(true);
    }
}
