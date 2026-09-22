<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\ArqueoCaja;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function apertura(Request $request)
    {
        if ($request->user()->arqueoAbierto()) {
            return redirect()->route('cajero.cobros.create');
        }

        return view('cajero.caja.apertura');
    }

    public function abrir(Request $request)
    {
        if ($request->user()->arqueoAbierto()) {
            return redirect()->route('cajero.cobros.create');
        }

        $datos = $request->validate([
            'monto_apertura' => ['required', 'numeric', 'min:0'],
        ]);

        ArqueoCaja::create([
            'usuario_id' => $request->user()->id,
            'monto_apertura' => $datos['monto_apertura'],
            'fecha_apertura' => now(),
            'estado' => 'abierto',
        ]);

        return redirect()->route('cajero.cobros.create')->with('mensaje', 'Caja aperturada correctamente.');
    }

    public function cerrar(Request $request)
    {
        $arqueo = $request->user()->arqueoAbierto();

        if (! $arqueo) {
            return redirect()->route('cajero.caja.apertura');
        }

        $montoSistema = $arqueo->montoSistemaCalculado();

        return view('cajero.caja.cerrar', compact('arqueo', 'montoSistema'));
    }

    public function confirmarCierre(Request $request)
    {
        $arqueo = $request->user()->arqueoAbierto();

        if (! $arqueo) {
            return redirect()->route('cajero.caja.apertura');
        }

        $datos = $request->validate([
            'monto_cierre_fisico' => ['required', 'numeric', 'min:0'],
        ]);

        $montoSistema = $arqueo->montoSistemaCalculado();
        $diferencia = round($datos['monto_cierre_fisico'] - $montoSistema, 2);

        if ($diferencia !== 0.0) {
            $mensaje = $diferencia > 0
                ? 'Hay un sobrante de Bs. ' . number_format($diferencia, 2) . '. La caja debe cuadrar exactamente para poder cerrarla, vuelve a contar el dinero.'
                : 'Hay un faltante de Bs. ' . number_format(abs($diferencia), 2) . '. La caja debe cuadrar exactamente para poder cerrarla, vuelve a contar el dinero.';

            return back()->withErrors(['monto_cierre_fisico' => $mensaje]);
        }

        $arqueo->update([
            'monto_cierre_sistema' => $montoSistema,
            'monto_cierre_fisico' => $datos['monto_cierre_fisico'],
            'diferencia' => $diferencia,
            'estado' => 'cerrado',
            'fecha_cierre' => now(),
        ]);

        return view('cajero.caja.resultado', compact('arqueo'));
    }
}
