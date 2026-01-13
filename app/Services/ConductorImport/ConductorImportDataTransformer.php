<?php

namespace App\Services\ConductorImport;

use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Transformador de datos para importación de conductores
 *
 * Transforma filas de datos CSV/Excel en arrays estructurados para crear conductores.
 * Maneja normalización de campos, validación de tipos, conversión de fechas y descarga de imágenes.
 */
class ConductorImportDataTransformer
{
    /**
     * @param  GoogleDriveImageDownloader  $imageDownloader  Descargador de imágenes desde Google Drive
     */
    public function __construct(
        private GoogleDriveImageDownloader $imageDownloader
    ) {}

    /**
     * Transforma una fila de datos en un array estructurado para crear un conductor
     *
     * Normaliza y transforma los datos según el tipo de campo: nombres/apellidos (capitalización),
     * tipos de conductor (A/B), grupos sanguíneos (RH), fechas (múltiples formatos), emails (validación),
     * y maneja la lógica de relevo (conductor sin vehículo asignado).
     *
     * @param  array<int, mixed>  $row  Fila de datos del archivo (array indexado)
     * @param  array<string, int>  $indexMap  Mapeo de campos a índices de columna (ej: ['nombres' => 0, 'cedula' => 1])
     * @return array<string, mixed> Array de datos del conductor listo para crear (ej: ['nombres' => 'Juan', 'cedula' => '123456'])
     */
    public function transformRow(array $row, array $indexMap): array
    {
        $data = [];

        foreach ($indexMap as $field => $index) {
            $value = $row[$index] ?? null;

            if ($value !== null) {
                $value = trim((string) $value);

                // Procesar según el campo
                switch ($field) {
                    case 'cedula':
                        $data['cedula'] = $value;
                        break;
                    case 'nombres':
                        $data['nombres'] = ucwords(strtolower($value));
                        break;
                    case 'apellidos':
                        $data['apellidos'] = ucwords(strtolower($value));
                        break;
                    case 'conductor_tipo':
                        $normalizedValue = strtoupper(trim($value));
                        if (strpos($normalizedValue, 'TIPO A') !== false ||
                            strpos($normalizedValue, 'CAMIONETAS') !== false ||
                            $normalizedValue === 'A') {
                            $data['conductor_tipo'] = 'A';
                        } elseif (strpos($normalizedValue, 'TIPO B') !== false ||
                                 strpos($normalizedValue, 'BUSETAS') !== false ||
                                 $normalizedValue === 'B') {
                            $data['conductor_tipo'] = 'B';
                        } else {
                            $data['conductor_tipo'] = 'B';
                        }
                        break;
                    case 'rh':
                        $rhValue = strtoupper(str_replace(' ', '', trim($value)));
                        $rhValidos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                        if (in_array($rhValue, $rhValidos)) {
                            $data['rh'] = $rhValue;
                        } else {
                            $rhValue = str_replace(['POSITIVO', 'POS', '+'], '+', $rhValue);
                            $rhValue = str_replace(['NEGATIVO', 'NEG', '-'], '-', $rhValue);
                            $data['rh'] = in_array($rhValue, $rhValidos) ? $rhValue : null;
                        }
                        break;
                    case 'vehiculo':
                        $vehiculoValue = trim((string) $value);
                        $data['vehiculo'] = ! empty($vehiculoValue) ? strtoupper($vehiculoValue) : null;
                        break;
                    case 'numero_interno':
                        $data['numero_interno'] = $value;
                        break;
                    case 'celular':
                        $data['celular'] = $value;
                        break;
                    case 'correo':
                        $data['correo'] = filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
                        break;
                    case 'fecha_nacimiento':
                        $data['fecha_nacimiento'] = $this->parseFecha($value);
                        break;
                    case 'otra_profesion':
                        $data['otra_profesion'] = $value;
                        break;
                    case 'nivel_estudios':
                        $data['nivel_estudios'] = $value;
                        break;
                    case 'foto':
                        if (! empty($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                            $data['foto'] = $this->imageDownloader->downloadAsBase64($value);
                        }
                        break;
                }
            }
        }

        // Manejar relevo
        $conductorTipo = strtolower(trim($data['conductor_tipo'] ?? ''));
        $vehiculo = strtolower(trim($data['vehiculo'] ?? ''));
        $numeroInterno = strtolower(trim($data['numero_interno'] ?? ''));

        if (strpos($conductorTipo, 'relevo') !== false ||
            strpos($vehiculo, 'relevo') !== false ||
            strpos($numeroInterno, 'relevo') !== false) {
            $data['conductor_tipo'] = null;
            $data['vehiculo'] = null;
            $data['numero_interno'] = null;
            $data['relevo'] = true;
        } elseif (empty($data['vehiculo']) || trim($data['vehiculo'] ?? '') === '') {
            $data['relevo'] = true;
        }

        return $data;
    }

    /**
     * Parsear fecha desde diferentes formatos
     */
    private function parseFecha($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Intentar diferentes formatos comunes
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Si es un timestamp de Excel
        if (is_numeric($value)) {
            try {
                $date = Date::excelToDateTimeObject($value);

                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Ignorar
            }
        }

        return null;
    }

    /**
     * Obtiene el mapeo estándar de nombres de columnas a campos de conductor
     *
     * Retorna un array asociativo que mapea diferentes variantes de nombres de columnas
     * (con/sin acentos, mayúsculas/minúsculas) a los nombres de campos internos del modelo Conductor.
     *
     * @return array<string, string> Mapeo de nombres de columnas a campos (ej: ['NOMBRES' => 'nombres', 'CEDULA' => 'cedula'])
     */
    public function getColumnMapping(): array
    {
        return [
            'NOMBRES' => 'nombres',
            'APELLIDOS' => 'apellidos',
            'CEDULA' => 'cedula',
            'CÉDULA' => 'cedula',
            'CONDUCTOR TIPO' => 'conductor_tipo',
            'TIPO CONDUCTOR' => 'conductor_tipo',
            'RH' => 'rh',
            'GRUPO SANGUINEO' => 'rh',
            'VEHICULO PLACA' => 'vehiculo',
            'VEHÍCULO PLACA' => 'vehiculo',
            'PLACA' => 'vehiculo',
            'VEHICULO' => 'vehiculo',
            'VEHÍCULO' => 'vehiculo',
            'NUMERO INTERNO' => 'numero_interno',
            'NÚMERO INTERNO' => 'numero_interno',
            'NUMERO' => 'numero_interno',
            'CELULAR' => 'celular',
            'TELÉFONO' => 'celular',
            'TELEFONO' => 'celular',
            'CORREO' => 'correo',
            'EMAIL' => 'correo',
            'E-MAIL' => 'correo',
            'FECHA DE NACIMIENTO' => 'fecha_nacimiento',
            'FECHA NACIMIENTO' => 'fecha_nacimiento',
            'FECHA NAC.' => 'fecha_nacimiento',
            '¿SABE OTRA PROFESIÓN A PARTE DE SER CONDUCTOR?' => 'otra_profesion',
            'OTRA PROFESIÓN' => 'otra_profesion',
            'OTRA PROFESION' => 'otra_profesion',
            'CARGUE SU FOTO PARA CARNET' => 'foto',
            'FOTO' => 'foto',
            'IMAGEN' => 'foto',
            'NIVEL DE ESTUDIOS' => 'nivel_estudios',
            'ESTUDIOS' => 'nivel_estudios',
        ];
    }

    /**
     * Crea un mapeo de campos a índices de columnas basado en los headers del archivo
     *
     * Compara los headers del archivo con el mapeo de columnas estándar y crea un índice
     * que relaciona cada campo con su posición en el array de datos de la fila.
     *
     * @param  array<int, string>  $headers  Headers del archivo (array indexado)
     * @param  array<string, string>  $columnMapping  Mapeo de columnas estándar (retornado por getColumnMapping)
     * @return array<string, int> Mapeo de campos a índices (ej: ['nombres' => 0, 'cedula' => 2])
     */
    public function createIndexMap(array $headers, array $columnMapping): array
    {
        $indexMap = [];
        $headersUpper = array_map('strtoupper', $headers);

        foreach ($columnMapping as $header => $field) {
            $index = array_search(strtoupper($header), $headersUpper);
            if ($index !== false) {
                $indexMap[$field] = $index;
            }
        }

        return $indexMap;
    }

    /**
     * Valida que las columnas requeridas estén presentes en el archivo
     *
     * Verifica que las columnas esenciales (nombres, apellidos, cedula) estén presentes
     * en el archivo. Retorna información detallada sobre las columnas faltantes si las hay.
     *
     * @param  array<string, int>  $indexMap  Mapeo de campos a índices (retornado por createIndexMap)
     * @param  array<int, string>  $headers  Headers del archivo (opcional, solo para mensajes de error)
     * @return array{
     *     valid: bool,
     *     missing?: array<int, string>,
     *     found?: string
     * }
     */
    public function validateRequiredFields(array $indexMap, array $headers = []): array
    {
        $requiredFields = ['nombres', 'apellidos', 'cedula'];
        $columnasFaltantes = [];

        foreach ($requiredFields as $field) {
            if (! isset($indexMap[$field])) {
                $columnasFaltantes[] = $field;
            }
        }

        if (! empty($columnasFaltantes)) {
            $columnasEncontradas = implode(', ', $headers);

            return [
                'valid' => false,
                'missing' => $columnasFaltantes,
                'found' => $columnasEncontradas,
            ];
        }

        return ['valid' => true];
    }
}
