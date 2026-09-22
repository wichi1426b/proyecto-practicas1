<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use App\Models\SolicitudDevolucion;
use Illuminate\Http\Request;

class DevolucionController extends Controller
{
    public function formSolicitar(Request $request, Cobro $cobro)
    {
        if ($cobro->usuario_id !== $request->user()->id) {
            abort(403);
        }

        if ($cobro->estado !== 'pagado') {
            return back()->withErrors(['error' => 'Este cobro ya está anulado, no se puede solicitar devolución.']);
        }

        $pendiente = SolicitudDevolucion::where('cobro_id', $cobro->id)
            ->where('estado', 'pendiente')
            ->exists();

        if ($pendiente) {
            return back()->withErrors(['error' => 'Ya tienes una solicitud de devolución pendiente para este cobro.']);
        }

        $aprobada = SolicitudDevolucion::where('cobro_id', $cobro->id)
            ->where('estado', 'aprobada')
            ->exists();

        if ($aprobada) {
            return back()->withErrors(['error' => 'Este cobro ya fue devuelto anteriormente.']);
        }

        $cobro->load(['estudiante.persona', 'detallePagos.item', 'comprobante']);

        return view('cajero.devolucion.solicitar', compact('cobro'));
    }

    public function solicitar(Request $request, Cobro $cobro)
    {

        if ($cobro->usuario_id !== $request->user()->id) {
            abort(403);
        }

        if ($cobro->estado !== 'pagado') {
            return back()->withErrors(['error' => 'Este cobro ya está anulado.']);
        }

        $pendiente = SolicitudDevolucion::where('cobro_id', $cobro->id)
            ->where('estado', 'pendiente')
            ->exists();

        if ($pendiente) {
            return back()->withErrors(['error' => 'Ya tienes una solicitud de devolución pendiente para este cobro.']);
        }

        $aprobada = SolicitudDevolucion::where('cobro_id', $cobro->id)
            ->where('estado', 'aprobada')
            ->exists();

        if ($aprobada) {
            return back()->withErrors(['error' => 'Este cobro ya fue devuelto anteriormente.']);
        }

        $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        SolicitudDevolucion::create([
            'cobro_id'              => $cobro->id,
            'cajero_solicitante_id' => $request->user()->id,
            'admin_autoriza_id'     => null,
            'motivo'                => $request->motivo,
            'monto_devuelto'        => null,
            'estado'                => 'pendiente',
            'fecha_solicitud'       => now(),
        ]);

        return redirect()
            ->route('cajero.cobros.comprobante', $cobro)
            ->with('mensaje', 'Solicitud de devolución enviada. Espera la autorización del administrador.');
    }

    public function misSolicitudes(Request $request)
    {
        $solicitudes = SolicitudDevolucion::with([
                'cobro.estudiante.persona',
                'cobro.detallePagos.item',
                'cobro.comprobante',
                'adminAutoriza.persona',
            ])
            ->where('cajero_solicitante_id', $request->user()->id)
            ->orderByDesc('fecha_solicitud')
            ->get();

        return view('cajero.devolucion.mis_solicitudes', compact('solicitudes'));
    }
}