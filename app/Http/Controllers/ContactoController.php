<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use App\Models\Plantilla;
use Illuminate\Http\Request;
use App\Imports\ContactosImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;

class ContactoController extends Controller
{
    public function index(Request $request)
    {
        $query = Contacto::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('nombre', 'like', "%$search%")
                ->orWhere('telefono', 'like', "%$search%");
        }

        return $query->orderBy('nombre')->paginate(10);
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
    public function sincronizarDesdeChatwoot()
    {
        $apiUrl = env('CHATWOOT_URL') . '/api/v1/accounts/' . env('CHATWOOT_ACCOUNT_ID') . '/contacts';
        $apiKey = env('CHATWOOT_API_KEY');

        $pagina = 1;
        $contactosSincronizados = [];

        try {
            do {
                $response = Http::withHeaders([
                    'api_access_token' => $apiKey,
                ])->get($apiUrl, [
                    'page' => $pagina,
                ]);

                if (!$response->successful()) {
                    return response()->json(['message' => 'Error obteniendo contactos de Chatwoot'], 500);
                }

                $payload = $response->json()['payload'] ?? [];

                foreach ($payload as $cwContacto) {
                    $nombre = $cwContacto['name'] ?? 'Sin nombre';
                    $telefono = $cwContacto['phone_number'] ?? null;

                    if (!$telefono) {
                        continue;
                    }

                    $contacto = Contacto::updateOrCreate(
                        ['telefono' => $telefono],
                        ['nombre' => $nombre]
                    );

                    $contactosSincronizados[] = $contacto;
                }

                $pagina++;
            } while (count($payload) > 0);

            return response()->json([
                'message' => 'Sincronización completada',
                'total_sincronizados' => count($contactosSincronizados),
                'contactos' => $contactosSincronizados,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al sincronizar',
                'error' => $e->getMessage()
            ], 500);
        }
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

    public function enviar(Request $request)
    {
        $request->validate([
            'plantilla_id' => 'required|exists:plantillas,id',
            'contactos' => 'required|array',
            'contactos.*.telefono' => 'required|string',
            'contactos.*.nombre' => 'nullable|string',
        ]);

        $plantilla = Plantilla::findOrFail($request->plantilla_id);

        foreach ($request->contactos as $contacto) {
            $telefono = $contacto['telefono'];
            $nombre = $contacto['nombre'] ?? '';

            // Aquí defines los parámetros para el body de la plantilla,
            // por ejemplo: un parámetro de texto con el nombre
            $parametros = [
                [
                    "type" => "text",
                    "text" => $nombre
                ]
                // Puedes agregar más parámetros siguiendo la estructura si la plantilla los requiere
            ];

            $response = $this->enviarWhatsApp($telefono, $plantilla, $parametros);
        }

        return response()->json(['message' => 'Mensajes enviados.', 'response' => $response], 200);
    }


    private function enviarWhatsApp($telefono, $plantilla, $parametros = [])
    {
        $evolutionUrl = env('EVOLUTION_API_URL'); // ej: https://tu-dominio-evolution.com/api/messages
        $evolutionToken = env('EVOLUTION_API_KEY');

        // Armamos los componentes para la plantilla
        $components = [];

        foreach ($plantilla->components as $componente) {
            $type = strtolower($componente['type']); // ej: BODY -> body

            if (
                isset($componente['text']) &&
                preg_match_all('/{{\d+}}/', $componente['text'], $matches)
            ) {
                $params = [];
                foreach ($matches[0] as $index => $placeholder) {
                    $valor = $parametros[$index]['text'] ?? "valor_" . ($index + 1);
                    $params[] = [
                        "type" => "text",
                        "text" => $valor
                    ];
                }

                $components[] = [
                    "type" => $type,
                    "parameters" => $params
                ];
            } else {
                $components[] = [
                    "type" => $type
                ];
            }
        }

        // Payload según Evolution API (ajústalo si tu proveedor usa otro formato)
        $payload = [
            "number" => $telefono,
            "name" => $plantilla->name,
            "language" => $plantilla->language ?? 'es_MX',
            "components" => $components
        ];
        // Llamada a Evolution API
        $response = Http::withHeaders([
            'apikey' => "$evolutionToken",
            'Content-Type' => 'application/json'
        ])->post($evolutionUrl, $payload);

        if (!$response->successful()) {
            Log::error("Error enviando WhatsApp a $telefono vía Evolution: " . $response->body());
        }

        return $response->body();
    }
}
