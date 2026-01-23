<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EngineerController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\RegionController;
use App\Models\Team;   // 👈 Agregado para el Dashboard
use App\Models\Region; // 👈 Agregado para el Dashboard
use App\Models\User;   // 👈 Agregado para el Dashboard

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

    // 1. Dashboard Routing Inteligente
    Route::get('/dashboard', function () {
        $user = auth()->user();
        // Si es Nivel 1, lo mandamos a su área exclusiva
        if ($user->global_role && in_array($user->global_role, ['gerente', 'supervisor'])) {
            return redirect()->route('admin.dashboard');
        }
        // Nivel 2 y 3 ven el Dashboard Operativo
        return Inertia::render('Dashboard/Index');
    })->name('dashboard');

    // 2. Operación Diaria (Ingenieros y Coordinadores)
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

    // 👇 ESTO ERA LO QUE TE FALTABA
    // Sin esto, no funcionan los botones de Crear, Editar ni Eliminar Región
    Route::resource('regions', RegionController::class);

    // 3. Gestión de Sucursales (Nivel 1 y 2)
    Route::resource('branches', BranchController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Zona Estructural / Global (Nivel 1 - Gerente/Supervisor)
    |--------------------------------------------------------------------------
    | Aquí se define la ESTRUCTURA del sistema (Compañías, Configuración Global)
    */
    Route::prefix('admin')
        ->name('admin.') // admin.dashboard, admin.companies.index
        ->group(function () {

            // Dashboard Administrativo
            Route::get('/', function () {
                // Doble check de seguridad
                if (!in_array(auth()->user()->global_role, ['gerente', 'supervisor'])) {
                    abort(403, 'Acceso restringido a Estructura Global.');
                }

                return Inertia::render('Admin/Dashboard', [
                    'stats' => [
                        'companies' => Team::count(),
                        'regions'   => Region::count(),
                        'engineers' => User::whereNotIn('global_role', ['admin', 'gerente', 'supervisor', 'coordinador'])
                       ->orWhereNull('global_role')
                       ->count(),
                    ]
                ]);
            })->name('dashboard');

            // Gestión de Compañías
            Route::resource('companies', CompanyController::class);
    });
});