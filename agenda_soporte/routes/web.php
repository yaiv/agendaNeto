<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EngineerController;
use App\Http\Controllers\Admin\CompanyController;           
use App\Http\Controllers\Admin\CompanyRegionController;    

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
| Rutas Protegidas (Usuarios Autenticados)
|--------------------------------------------------------------------------
*/
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'active'
])->group(function () {

    Route::resource('engineers', EngineerController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);
    /*
    |--------------------------------------------------
    | Dashboard Central y deteccion de roles
    |--------------------------------------------------
    */
Route::get('/dashboard', function () {

    $user = auth()->user();

    // Nivel 1: Gerente / Supervisor → Admin Dashboard
    if (in_array($user->global_role, ['gerente', 'supervisor'])) {
        return redirect()->route('admin.dashboard');
    }

    // Nivel 2 y 3 → Dashboard normal
    return Inertia::render('Dashboard/Index');

    })->name('dashboard');

    Route::get('/regions', [App\Http\Controllers\RegionController::class, 'index'])
    ->name('regions.index');

    // Gestión de Sucursales (CRUD)
Route::resource('branches', App\Http\Controllers\BranchController::class)
    ->only(['create', 'store', 'edit', 'update', 'destroy']);


    /*
    |--------------------------------------------------
    | Zona Admin Global (Nivel 1 - Gerente)
    |--------------------------------------------------
    | Por ahora se protege con validación directa.
    | Más adelante se recomienda mover esto a Middleware.
    */
    Route::get('/admin', function () {
        if (!auth()->user()->isGlobalAdmin()) {
            abort(403, 'Acceso restringido a Nivel 1.');
        }

        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');

// Gestión de Compañías (Teams)
        Route::resource('companies', CompanyController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);

        // Gestión de Regiones (anidadas a compañía)
        //Route::resource('companies.regions', CompanyRegionController::class)
        //    ->shallow();

        Route::prefix('admin')      // Agrega /admin a la URL
         ->name('admin.')       // Agrega admin. al nombre de la ruta
         ->group(function () {

            // Dashboard Administrativo
            Route::get('/', function () {
                // Validación de seguridad manual (o puedes crear un middleware 'can:manage-structure')
                if (!auth()->user()->is_global_admin && !in_array(auth()->user()->global_role, ['gerente', 'supervisor'])) {
                    abort(403, 'Acceso restringido a Nivel 1.');
                }
                return Inertia::render('Admin/Dashboard');
            })->name('dashboard'); // Se convierte en 'admin.dashboard'

            // Gestión de Compañías
            Route::resource('companies', CompanyController::class);

            // Gestión de Regiones (Prepara el terreno para el siguiente paso)
            // Shallow permite rutas más cortas para los hijos
         //   Route::resource('companies.regions', CompanyRegionController::class)->shallow();

    }); // Fin del grupo Admin

});
