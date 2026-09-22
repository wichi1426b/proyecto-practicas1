<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use App\Models\Comprobante;
use App\Models\SolicitudReimpresion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReimpresionController extends Controller
{
    public function formSolicitar(Request $request, Cobro $cobro)
    {
        if ($cobro->usuario_id !== $request->user()->id) {
            abort(403);
        }
        if ($cobro->estado !== 'pagado') {
            return back()->withErrors(['error' => 'Este cobro no está en estado pagado.']);
        }

        $comprobante = $cobro->comprobante;
        if ($comprobante->anulado) {
            return back()->withErrors(['error' => 'El comprobante de este cobro está anulado.']);
        }

        $pendiente = SolicitudReimpresion::where('comprobante_id', $comprobante->id)
            ->where('estado', 'pendiente')
            ->exists();

        if ($pendiente) {
            return back()->withErrors(['error' => 'Ya tienes una solicitud de reimpresión pendiente para este comprobante.']);
        }

        $cobro->load(['estudiante.persona', 'detallePagos.item']);

        return view('cajero.reimpresion.solicitar', compact('cobro', 'comprobante'));
    }

    public function solicitar(Request $request, Cobro $cobro)
    {
        if ($cobro->usuario_id !== $request->user()->id) {
            abort(403);
        }

        if ($cobro->estado !== 'pagado') {
            return back()->withErrors(['error' => 'Este cobro no está en estado pagado.']);
        }

        $comprobante = $cobro->comprobante;

        if ($comprobante->anulado) {
            return back()->withErrors(['error' => 'El comprobante de este cobro está anulado.']);
        }

        $pendiente = SolicitudReimpresion::where('comprobante_id', $comprobante->id)
            ->where('estado', 'pendiente')
            ->exists();

        if ($pendiente) {
            return back()->withErrors(['error' => 'Ya tienes una solicitud de reimpresión pendiente para este comprobante.']);
        }

        $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        SolicitudReimpresion::create([
            'comprobante_id'        => $comprobante->id,
            'cajero_solicitante_id' => $request->user()->id,
            'admin_autoriza_id'     => null,
            'motivo'                => $request->motivo,
            'estado'                => 'pendiente',
            'fecha_solicitud'       => now(),
        ]);

        return redirect()
            ->route('cajero.cobros.comprobante', $cobro)
            ->with('mensaje', 'Solicitud de reimpresión enviada. Espera la autorización del administrador.');
    }

    public function reimprimir(Request $request, SolicitudReimpresion $solicitud)
    {
        if ($solicitud->cajero_solicitante_id !== $request->user()->id) {
            abort(403);
        }

        if ($solicitud->estado !== 'aprobada') {
            return back()->withErrors(['error' => 'Esta solicitud no ha sido aprobada aún.']);
        }

        $comprobante = $solicitud->comprobante;
        $cobro       = $comprobante->cobro;

        if ($comprobante->anulado) {
            return back()->withErrors(['error' => 'El comprobante original está anulado, no se puede reimprimir.']);
        }

        $yaEjecutada = Comprobante::where('cobro_id', $cobro->id)
            ->where('es_reimpresion', true)
            ->where('numero_comprobante', 'like', 'R-' . str_pad($cobro->id, 8, '0', STR_PAD_LEFT) . '%')
            ->exists();

        if ($yaEjecutada) {
            return back()->withErrors(['error' => 'Esta reimpresión ya fue ejecutada.']);
        }

        $nuevoComprobante = DB::transaction(function () use ($cobro, $solicitud) {
            $nuevo = Comprobante::create([
                'cobro_id'            => $cobro->id,
                'numero_comprobante'  => 'R-' . str_pad($cobro->id, 8, '0', STR_PAD_LEFT) . '-' . now()->format('YmdHis'),
                'fecha_emision'       => now(),
                'es_reimpresion'      => true,
                'anulado'             => false,
            ]);

            return $nuevo;
        });

        $cobro->load(['estudiante.persona', 'usuario.persona', 'detallePagos.item']);

        return view('cajero.reimpresion.comprobante_reimpresion', compact('cobro', 'nuevoComprobante', 'solicitud'));
    }

    public function misSolicitudes(Request $request)
    {
        $solicitudes = SolicitudReimpresion::with([
                'comprobante.cobro.estudiante.persona',
                'comprobante.cobro.detallePagos.item',
                'adminAutoriza.persona',
            ])
            ->where('cajero_solicitante_id', $request->user()->id)
            ->orderByDesc('fecha_solicitud')
            ->get();

        return view('cajero.reimpresion.mis_solicitudes', compact('solicitudes'));
    }
}

