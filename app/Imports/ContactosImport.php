<?php

namespace App\Imports;

use App\Models\Contacto;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactosImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Asume que el archivo tiene columnas: nombre, telefono, email
        return new Contacto([
            'nombre' => $row['nombre'] ?? null,
            'telefono' => $row['telefono'],
        ]);
    }
}
