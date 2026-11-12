<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeskController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\SupervisorController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect(auth()->user()->rutaInicio()) : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/acceso-rapido/{rol}', [AuthController::class, 'accesoRapido'])->name('acceso.rapido');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/primer-ingreso/password', [AuthController::class, 'passwordForm'])->name('password.form');
    Route::post('/primer-ingreso/password', [AuthController::class, 'passwordGuardar'])->name('password.guardar');
    Route::get('/primer-ingreso/consentimiento', [AuthController::class, 'consentimientoForm'])->name('consentimiento.form');
    Route::post('/primer-ingreso/consentimiento', [AuthController::class, 'consentimientoGuardar'])->name('consentimiento.guardar');

    Route::middleware('primer.ingreso')->group(function () {
        Route::get('/portal', [PortalController::class, 'index'])->middleware('rol:Alumno,Docente')->name('portal');
        Route::get('/ventanilla', [DeskController::class, 'index'])->middleware('rol:Administrador,Supervisor')->name('ventanilla');
        Route::middleware('rol:Alumno,Docente')->group(function () {
            Route::post('/portal/qr', [PortalController::class, 'qr'])->name('portal.qr');
            Route::post('/portal/reservar', [PortalController::class, 'reservar'])->name('portal.reservar');
        });
        Route::middleware('rol:Administrador,Supervisor')->group(function () {
            Route::post('/ventanilla/entregar', [DeskController::class, 'entregar'])->name('ventanilla.entregar');
            Route::post('/ventanilla/devolver', [DeskController::class, 'devolver'])->name('ventanilla.devolver');
            Route::get('/ventanilla/alertas', [DeskController::class, 'alertas'])->name('ventanilla.alertas');
            Route::get('/ventanilla/padron', [DeskController::class, 'padron'])->name('ventanilla.padron');
        });
        Route::get('/supervisor', [SupervisorController::class, 'index'])->middleware('rol:Supervisor,Superadministrador')->name('supervisor');
        Route::middleware('rol:Supervisor,Superadministrador')->group(function () {
            Route::post('/supervisor/tickets/{id}/reparar', [SupervisorController::class, 'reparar'])->name('sup.reparar');
            Route::post('/supervisor/sanciones/{id}/levantar', [SupervisorController::class, 'levantar'])->name('sup.levantar');
            Route::post('/supervisor/importar', [SupervisorController::class, 'importar'])->name('sup.importar');
        });
    });
});
