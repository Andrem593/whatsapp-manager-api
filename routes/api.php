<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EnvioController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\PlantillaController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('plantillas', PlantillaController::class);
    Route::post('/plantillas/sincronizar', [PlantillaController::class, 'sincronizarDesdeFacebook']);
    Route::post('/plantillas/{id}/enviar-a-facebook', [PlantillaController::class, 'enviarAFacebook']);
    Route::get('contactos', [ContactoController::class, 'index']);
    Route::post('/plantillas/enviar', [ContactoController::class, 'enviar']);
    Route::get('contactos/sincronizar', [ContactoController::class, 'sincronizarDesdeChatwoot']);
    Route::post('contactos/excel', [ContactoController::class, 'importarExcel']);

    Route::post('envios', [EnvioController::class, 'store']);
    Route::get('envios/{id}', [EnvioController::class, 'show']);
});

Route::post('/login', [UserController::class, 'login']);

Route::post('/register', [UserController::class, 'register']);
