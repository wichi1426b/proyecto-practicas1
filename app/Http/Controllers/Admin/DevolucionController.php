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
            if (! in_array($cobro->estado, ['pagado', 'pendiente'])) {
                throw new \Exception('El cobro ya fue anulado por otra operación.');
            }

            $cobro->update(['estado' => 'anulado', 'saldo_pendiente' => 0]);

            $cobro->comprobante()->update(['anulado' => true]);

            $solicitud->update([
                'estado'            => 'aprobada',
                'admin_autoriza_id' => $request->user()->id,
                'monto_devuelto'    => $cobro->monto_pagado,
                'fecha_resolucion'  => now(),
            ]);
        });

        return back()->with('mensaje', 'Devolución aprobada. El cobro ha sido anulado y el cajero puede entregar el dinero.');
    }

    public function comprobante(SolicitudDevolucion $solicitud)
    {
        if (! $solicitud->fueEntregada()) {
            return back()->withErrors(['error' => 'La devolución aún no fue entregada por el cajero.']);
        }

        $solicitud->load(['cobro.estudiante.persona', 'cobro.detallePagos.item', 'cobro.comprobante', 'cajeroSolicitante.persona', 'adminAutoriza.persona']);

        return view('cajero.devolucion.comprobante', ['solicitud' => $solicitud, 'volver' => route('admin.devoluciones.index')]);
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