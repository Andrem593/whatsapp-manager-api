<?php

namespace App\Http\Controllers;

use App\Models\Plantilla;
use Illuminate\Http\Request;

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

        return response()->json([
            'message' => 'Plantilla creada correctamente',
            'data' => $plantilla,
        ], 201);
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
}
