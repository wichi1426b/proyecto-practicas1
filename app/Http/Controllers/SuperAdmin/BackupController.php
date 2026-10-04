<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\RespaldoBaseDatos;

class BackupController extends Controller
{
    public function index()
    {
        $backups = RespaldoBaseDatos::listar();

        return view('superadmin.backups.index', compact('backups'));
    }

    public function generar()
    {
        try {
            $nombre = RespaldoBaseDatos::generar();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['error' => 'No se pudo generar la copia de seguridad: ' . $e->getMessage()]);
        }

        return back()->with('mensaje', "Copia de seguridad generada: {$nombre}");
    }

    public function descargar(string $archivo)
    {
        $ruta = RespaldoBaseDatos::ruta($archivo) ?? abort(404);

        return response()->download($ruta);
    }

    public function eliminar(string $archivo)
    {
        $ruta = RespaldoBaseDatos::ruta($archivo) ?? abort(404);
        unlink($ruta);

        return back()->with('mensaje', "Copia {$archivo} eliminada.");
    }
}
