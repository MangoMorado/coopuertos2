<?php

namespace App\Services\ConductorImport;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * Validador de archivos para importación de conductores
 *
 * Valida que los archivos subidos cumplan con los requisitos de formato (CSV, XLS, XLSX),
 * tamaño máximo y tipos MIME permitidos antes de procesarlos.
 */
class ConductorImportFileValidator
{
    /**
     * Valida un archivo subido para importación de conductores
     *
     * Verifica el formato (CSV, XLS, XLSX), tamaño máximo (10MB) y tipo MIME del archivo.
     * Para archivos CSV es más flexible con los tipos MIME debido a las variaciones entre sistemas.
     *
     * @param  UploadedFile  $file  Archivo subido a validar
     * @return array{
     *     valid: bool,
     *     extension?: string,
     *     errors?: \Illuminate\Support\MessageBag
     * }
     */
    public function validate(UploadedFile $file): array
    {
        $validator = Validator::make(['archivo' => $file], [
            'archivo' => [
                'required',
                'file',
                'max:10240', // Max 10MB
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension());
                    $mimeType = $value->getMimeType();

                    // Extensiones permitidas
                    $allowedExtensions = ['xlsx', 'xls', 'csv'];

                    // MIME types permitidos
                    $allowedMimeTypes = [
                        // Excel
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                        'application/vnd.ms-excel', // .xls
                        // CSV - múltiples variantes
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'text/x-csv',
                        'application/x-csv',
                        'text/comma-separated-values',
                        'text/x-comma-separated-values',
                        'application/vnd.ms-excel', // Excel también puede leer CSV
                    ];

                    // Validar extensión
                    if (! in_array($extension, $allowedExtensions)) {
                        $fail('El archivo debe ser de tipo: '.implode(', ', $allowedExtensions).". Extensión recibida: {$extension}");

                        return;
                    }

                    // Para CSV, ser más flexible con MIME types
                    if ($extension === 'csv') {
                        // Aceptar cualquier MIME type para CSV ya que varían mucho
                        return;
                    }

                    // Para Excel, validar MIME type
                    if (! in_array($mimeType, $allowedMimeTypes)) {
                        $fail("El archivo tiene un tipo MIME no válido: {$mimeType}");

                        return;
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors(),
            ];
        }

        return [
            'valid' => true,
            'extension' => strtolower($file->getClientOriginalExtension()),
        ];
    }
}
