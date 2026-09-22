<?php

use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Cajero\CajaController;
use App\Http\Controllers\Cajero\CobroController;
use App\Http\Controllers\SuperAdmin\UsuarioController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ReimpresionController as AdminReimpresionController;
use App\Http\Controllers\Cajero\ReimpresionController as CajeroReimpresionController;

use App\Http\Controllers\Admin\DevolucionController as AdminDevolucionController;
use App\Http\Controllers\Cajero\DevolucionController as CajeroDevolucionController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'iniciarSesion'])->name('login.intentar');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'cerrarSesion'])->name('logout');
});

Route::prefix('superadmin')->middleware(['auth', 'rol:super_admin'])->name('superadmin.')->group(function () {
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
});

Route::prefix('admin')->middleware(['auth', 'rol:admin'])->name('admin.')->group(function () {
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/crear', [ItemController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::get('/items/{item}/editar', [ItemController::class, 'edit'])->name('items.edit');
    Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');

    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

    Route::get('/reimpresiones',[AdminReimpresionController::class, 'index']) ->name('reimpresiones.index');
    
    Route::post('/reimpresiones/{solicitud}/aprobar',[AdminReimpresionController::class, 'aprobar'])->name('reimpresiones.aprobar');
    Route::post('/reimpresiones/{solicitud}/rechazar',[AdminReimpresionController::class, 'rechazar'])->name('reimpresiones.rechazar');

    Route::get('/devoluciones',[AdminDevolucionController::class, 'index'])->name('devoluciones.index');
    Route::post('/devoluciones/{solicitud}/aprobar',[AdminDevolucionController::class, 'aprobar'])->name('devoluciones.aprobar');
    Route::post('/devoluciones/{solicitud}/rechazar',[AdminDevolucionController::class, 'rechazar'])->name('devoluciones.rechazar');
});

Route::prefix('cajero')->middleware(['auth', 'rol:cajero'])->name('cajero.')->group(function () {
    Route::get('/caja/apertura', [CajaController::class, 'apertura'])->name('caja.apertura');
    Route::post('/caja/apertura', [CajaController::class, 'abrir'])->name('caja.abrir');

    Route::middleware('caja.abierta')->group(function () {
        Route::get('/cobros/crear', [CobroController::class, 'create'])->name('cobros.create');
        Route::post('/cobros', [CobroController::class, 'store'])->name('cobros.store');
        Route::get('/cobros/{cobro}/comprobante', [CobroController::class, 'comprobante'])->name('cobros.comprobante');

        Route::get('/cobros/{cobro}/reimpresion/solicitar',
        [CajeroReimpresionController::class, 'formSolicitar'])
        ->name('cobros.reimpresion.form');
    
        Route::post('/cobros/{cobro}/reimpresion/solicitar',[CajeroReimpresionController::class, 'solicitar'])->name('cobros.reimpresion.solicitar');
        Route::post('/reimpresion/{solicitud}/ejecutar',[CajeroReimpresionController::class, 'reimprimir'])->name('reimpresion.ejecutar');
        Route::get('/reimpresiones',[CajeroReimpresionController::class, 'misSolicitudes'])->name('reimpresion.mis_solicitudes');

        Route::get('/caja/cierre', [CajaController::class, 'cerrar'])->name('caja.cerrar');
        Route::post('/caja/cierre', [CajaController::class, 'confirmarCierre'])->name('caja.confirmar_cierre');

        Route::get('/cobros/{cobro}/devolucion/solicitar',[CajeroDevolucionController::class, 'formSolicitar'])->name('cobros.devolucion.form');
        Route::post('/cobros/{cobro}/devolucion/solicitar',[CajeroDevolucionController::class, 'solicitar'])->name('cobros.devolucion.solicitar');
        Route::get('/devoluciones',[CajeroDevolucionController::class, 'misSolicitudes'])->name('devolucion.mis_solicitudes');

    });
});
