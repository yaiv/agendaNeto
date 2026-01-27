<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EngineerController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Rutas Protegidas y Operativas (Niveles 2 y 3)
|--------------------------------------------------------------------------
*/
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'active',
])->group(function () {

    // ✅ Dashboard Principal - Enrutamiento Inteligente
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ✅ Operación Diaria (Ingenieros y Coordinadores)
    Route::resource('engineers', EngineerController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);

    /*
    |--------------------------------------------------------------------------
    | Gestión de Regiones (Nivel 1 y 2)
    |--------------------------------------------------------------------------
    */
    
    // Ruta auxiliar: Ver sucursales de una región específica (Debe ir ANTES del resource)
    Route::get('/regions/{region}/branches', [BranchController::class, 'index'])
        ->name('regions.branches.index');

    // Resource completo de regiones
    Route::resource('regions', RegionController::class);

    // ✅ Gestión de Sucursales/Tiendas (Nivel 1 y 2)
    Route::resource('branches', BranchController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Zona Administrativa - Estructura Global (Nivel 1)
    |--------------------------------------------------------------------------
    | Gerentes y Supervisores: Configuración del sistema completo
    */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('global.admin') // 👈 OPCIONAL: puedes crear un middleware específico
        ->group(function () {

            // ✅ Dashboard Administrativo
            Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');

            // ✅ Gestión de Compañías
            Route::resource('companies', CompanyController::class);
    });
});