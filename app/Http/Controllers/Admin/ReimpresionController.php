<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SolicitudReimpresion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReimpresionController extends Controller
{
    public function index(Request $request)
    {
        $estado = in_array($request->input('estado'), ['pendiente', 'aprobada', 'rechazada'])
            ? $request->input('estado')
            : null;

        $solicitudes = SolicitudReimpresion::with([
                'comprobante.cobro.estudiante.persona',
                'comprobante.cobro.detallePagos.item',
                'cajeroSolicitante.persona',
                'adminAutoriza.persona',
            ])
            ->when($estado, fn($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha_solicitud')
            ->get();

        return view('admin.reimpresiones.index', compact('solicitudes', 'estado'));
    }

    public function aprobar(Request $request, SolicitudReimpresion $solicitud)
    {
        if ($solicitud->estado !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta solicitud ya fue procesada.']);
        }

        DB::transaction(function () use ($solicitud, $request) {
            $solicitud->update([
                'estado'             => 'aprobada',
                'admin_autoriza_id'  => $request->user()->id,
                'fecha_autorizacion' => now(),
            ]);
        });

        return back()->with('mensaje', 'Solicitud de reimpresión aprobada.');
    }

    public function rechazar(Request $request, SolicitudReimpresion $solicitud)
    {
        if ($solicitud->estado !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta solicitud ya fue procesada.']);
        }

        DB::transaction(function () use ($solicitud, $request) {
            $solicitud->update([
                'estado'             => 'rechazada',
                'admin_autoriza_id'  => $request->user()->id,
                'fecha_autorizacion' => now(),
            ]);
        });

        return back()->with('mensaje', 'Solicitud de reimpresión rechazada.');
    }
}