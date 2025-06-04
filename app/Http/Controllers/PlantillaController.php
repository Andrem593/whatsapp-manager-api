<?php

namespace App\Http\Controllers;

use App\Models\Plantilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PlantillaController extends Controller
{
    // GET /api/plantillas
    public function index()
    {
        return Plantilla::latest()->paginate(10);
    }

    // GET /api/plantillas/{id}
    public function show(Plantilla $plantilla)
    {
        return $plantilla;
    }

    // POST /api/plantillas
    public function store(Request $request)
    {
        $data = $request->validate([
            'whatsapp_id'      => 'nullable|string',
            'name'             => 'required|string',
            'language'         => 'required|string',
            'category'         => 'required|string',
            'status'           => 'required|in:PENDING,APPROVED,REJECTED',
            'parameter_format' => 'required|in:POSITIONAL,STRUCTURED',
            'components'       => 'required|array',
        ]);

        $plantilla = Plantilla::create($data);

        // 2. Enviar plantilla a Facebook (WhatsApp Business API)
        $response = Http::withToken(env('FACEBOOK_TOKEN')) // Token de Meta
            ->post('https://graph.facebook.com/v23.0/' . env('WHATSAPP_BUSINESS_ID') . '/message_templates', [
                'name'              => $data['name'],
                'language'          => ['code' => $data['language']],
                'category'          => $data['category'],
                'components'        => $data['components'],
                'namespace'         => env('WHATSAPP_NAMESPACE'), // opcional si lo manejas
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Plantilla guardada internamente pero falló al enviarse a Facebook',
                'errors' => $response->json(),
            ], 422);
        }

        return response()->json([
            'message' => 'Plantilla creada y enviada correctamente a Facebook',
            'data' => [
                'plantilla' => $plantilla,
                'facebook_response' => $response->json(),
            ],
        ], 201);
    }

    public function enviarAFacebook($id)
    {
        $plantilla = Plantilla::findOrFail($id);

        $whatsappBusinessAccountId = config('services.facebook.whatsapp_business_id');
        $accessToken = config('services.facebook.token');

        // 1. Si ya tiene un ID, sincronizar estado desde Facebook
        if ($plantilla->whatsapp_id) {
            $endpoint = "https://graph.facebook.com/v23.0/{$whatsappBusinessAccountId}/message_templates?name={$plantilla->name}";

            $response = Http::withToken($accessToken)->get($endpoint);

            if ($response->successful()) {
                $templates = $response->json('data');

                $template = collect($templates)->firstWhere('name', $plantilla->name);

                if ($template) {
                    $plantilla->status = strtoupper($template['status']);
                    $plantilla->save();

                    return response()->json([
                        'message' => 'Estado sincronizado con éxito desde Facebook.',
                        'status' => $plantilla->status,
                    ]);
                } else {
                    return response()->json([
                        'error' => 'La plantilla no fue encontrada en Facebook.',
                    ], 404);
                }
            }

            return response()->json([
                'error' => 'No se pudo sincronizar el estado con Facebook.',
                'details' => $response->json()
            ], $response->status());
        }

        // 2. Si no tiene whatsapp_id, enviar para aprobación
        if ($plantilla->status !== 'PENDING') {
            return response()->json([
                'error' => 'Solo se pueden enviar plantillas en estado PENDING'
            ], 400);
        }

        $endpoint = "https://graph.facebook.com/v23.0/{$whatsappBusinessAccountId}/message_templates";

        $payload = [
            'name' => $plantilla->name,
            'language' => ['code' => $plantilla->language],
            'category' => $plantilla->category,
            'components' => $plantilla->components,
        ];

        $response = Http::withToken($accessToken)
            ->post($endpoint, $payload);

        if ($response->successful()) {
            $plantilla->status = 'PENDING';
            $plantilla->whatsapp_id = $response->json('id') ?? null;
            $plantilla->save();

            return response()->json([
                'message' => 'Plantilla enviada a Facebook para aprobación',
                'status' => $plantilla->status
            ]);
        } else {
            return response()->json([
                'error' => 'Error al enviar plantilla a Facebook',
                'details' => $response->json()
            ], $response->status());
        }
    }

    // PUT /api/plantillas/{id}
    public function update(Request $request, Plantilla $plantilla)
    {
        $data = $request->validate([
            'whatsapp_id'      => 'nullable|string',
            'name'             => 'sometimes|string',
            'language'         => 'sometimes|string',
            'category'         => 'sometimes|string',
            'status'           => 'sometimes|in:PENDING,APPROVED,REJECTED',
            'parameter_format' => 'sometimes|in:POSITIONAL,STRUCTURED',
            'components'       => 'sometimes|array',
        ]);

        $plantilla->update($data);

        return response()->json([
            'message' => 'Plantilla actualizada',
            'data' => $plantilla,
        ]);
    }

    // DELETE /api/plantillas/{id}
    public function destroy(Plantilla $plantilla)
    {
        $plantilla->delete();

        return response()->json(['message' => 'Plantilla eliminada']);
    }

    public function sincronizarDesdeFacebook()
    {
        $token = config('services.facebook.token');
        $whatsappBusinessAccountId = config('services.facebook.whatsapp_business_id');

        $response = Http::withToken($token)
            ->get("https://graph.facebook.com/v23.0/{$whatsappBusinessAccountId}/message_templates");

        if ($response->failed()) {
            return response()->json(['message' => 'Error al obtener plantillas de Facebook'], 500);
        }

        $data = $response->json();
        $sincronizadas = 0;

        foreach ($data['data'] as $fbTemplate) {
            $plantilla = Plantilla::updateOrCreate(
                ['whatsapp_id' => $fbTemplate['id']],
                [
                    'name'             => $fbTemplate['name'],
                    'language'         => $fbTemplate['language'],
                    'category'         => $fbTemplate['category'],
                    'status'           => $fbTemplate['status'],
                    'parameter_format' => $fbTemplate['parameter_format'] ?? 'POSITIONAL',
                    'components'       => $fbTemplate['components'] ?? [],
                ]
            );
            $sincronizadas++;
        }

        return response()->json([
            'message' => "Sincronización completada",
            'total'   => $sincronizadas,
        ]);
    }
}
