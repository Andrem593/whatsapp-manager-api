<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use Illuminate\Http\Request;
use App\Imports\ContactosImport;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;

class ContactoController extends Controller
{
    public function index()
    {
        return Contacto::latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'nullable|string',
            'telefono' => 'required|string|unique:contactos,telefono',
        ]);

        $contacto = Contacto::create($data);

        return response()->json(['data' => $contacto], 201);
    }

    public function destroy(Contacto $contacto)
    {
        $contacto->delete();

        return response()->json(['message' => 'Contacto eliminado']);
    }

    /**
     * Sincronizar contactos desde Evolution API o CRM
     */
    public function sincronizarDesdeApi(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'token' => 'required|string',
        ]);

        $response = Http::withToken($request->token)
            ->get($request->endpoint);

        if (!$response->successful()) {
            return response()->json(['message' => 'Error al sincronizar contactos'], 500);
        }

        $contactos = $response->json();

        foreach ($contactos as $c) {
            Contacto::updateOrCreate(
                ['telefono' => $c['telefono']],
                [
                    'nombre' => $c['nombre'] ?? null,
                ]
            );
        }

        return response()->json(['message' => 'Contactos sincronizados']);
    }

    public function importarExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new ContactosImport, $request->file('archivo'));
            return response()->json(['message' => 'Contactos importados exitosamente']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error al importar: ' . $e->getMessage()], 500);
        }
    }
}
