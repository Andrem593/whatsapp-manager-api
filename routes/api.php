<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EnvioController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\PlantillaController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('plantillas', PlantillaController::class);
    Route::get('contactos', [ContactoController::class, 'index']);
    Route::post('contactos/sync', [ContactoController::class, 'sincronizarDesdeApia']);
    Route::post('contactos/excel', [ContactoController::class, 'importarExcel']);
    
    Route::post('envios', [EnvioController::class, 'store']);
    Route::get('envios/{id}', [EnvioController::class, 'show']);
});

Route::post('/login', [UserController::class, 'login']);

Route::post('/register', [UserController::class, 'register']);


