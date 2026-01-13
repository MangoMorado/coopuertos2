<?php

namespace App\Services\ConductorImport;

use PhpOffice\PhpSpreadsheet\Shared\Date;

class ConductorImportDataTransformer
{
    public function __construct(
        private GoogleDriveImageDownloader $imageDownloader
    ) {}

    /**
     * Transformar fila de datos a array de datos de conductor
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
     * Obtener mapeo de columnas estándar
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
     * Crear mapeo de índices desde headers
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
     * Validar columnas requeridas
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
