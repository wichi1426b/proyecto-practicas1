<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SolicitudDevolucion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevolucionController extends Controller
{
    public function index(Request $request)
    {
        $estado = in_array($request->input('estado'), ['pendiente', 'aprobada', 'rechazada'])
            ? $request->input('estado')
            : null;

        $solicitudes = SolicitudDevolucion::with([
                'cobro.estudiante.persona',
                'cobro.detallePagos.item',
                'cobro.comprobante',
                'cobro.arqueoCaja',
                'cajeroSolicitante.persona',
                'adminAutoriza.persona',
            ])
            ->when($estado, fn($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha_solicitud')
            ->get();

        return view('admin.devoluciones.index', compact('solicitudes', 'estado'));
    }

    public function aprobar(Request $request, SolicitudDevolucion $solicitud)
    {
        if ($solicitud->estado !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta solicitud ya fue procesada.']);
        }

        DB::transaction(function () use ($solicitud, $request) {
            $cobro = $solicitud->cobro;
            if ($cobro->estado !== 'pagado') {
                throw new \Exception('El cobro ya fue anulado por otra operación.');
            }

            $cobro->update(['estado' => 'anulado']);

            $cobro->comprobante()->update(['anulado' => true]);

            $solicitud->update([
                'estado'            => 'aprobada',
                'admin_autoriza_id' => $request->user()->id,
                'monto_devuelto'    => $cobro->monto_total,
                'fecha_resolucion'  => now(),
            ]);
        });

        return back()->with('mensaje', 'Devolución aprobada. El cobro ha sido anulado.');
    }

    public function rechazar(Request $request, SolicitudDevolucion $solicitud)
    {
        if ($solicitud->estado !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta solicitud ya fue procesada.']);
        }

        $solicitud->update([
            'estado'            => 'rechazada',
            'admin_autoriza_id' => $request->user()->id,
            'fecha_resolucion'  => now(),
        ]);

        return back()->with('mensaje', 'Solicitud de devolución rechazada.');
    }
}