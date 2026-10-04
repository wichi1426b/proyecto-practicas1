<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use App\Models\SolicitudDevolucion;
use Illuminate\Http\Request;

class DevolucionController extends Controller
{
    public function buscar(Request $request)
    {
        $cobros  = collect();
        $carnet  = null;
        $buscado = false;

        if ($request->isMethod('post') || $request->filled('carnet')) {
            $request->validate([
                'carnet' => ['required', 'string', 'max:20'],
            ]);

            $carnet  = trim($request->carnet);
            $buscado = true;

            $persona = \App\Models\Persona::where('ci', $carnet)->first();

            if ($persona) {
                $estudiante = \App\Models\Estudiante::where('persona_ci', $persona->ci)->first();

                if ($estudiante) {
                    $cobros = Cobro::with([
                            'comprobante',
                            'detallePagos.item',
                            'solicitudesDevolucion',
                        ])
                        ->where('estudiante_id', $estudiante->id)
                        ->where('usuario_id', $request->user()->id)
                        ->vigentes()
                        ->orderByDesc('fecha_pago')
                        ->get();
                }
            }
        }
        $solicitudes = SolicitudDevolucion::with([
                'cobro.estudiante.persona',
                'cobro.detallePagos.item',
                'cobro.comprobante',
            ])
            ->where('cajero_solicitante_id', $request->user()->id)
            ->orderByDesc('fecha_solicitud')
            ->get();

        return view('cajero.devolucion.buscar', compact('cobros', 'carnet', 'buscado', 'solicitudes'));
    }

    public function formSolicitar(Request $request, Cobro $cobro)
    {
        if ($cobro->usuario_id !== $request->user()->id) {
            abort(403);
        }

        if (! in_array($cobro->estado, ['pagado', 'pendiente'])) {
            return back()->withErrors(['error' => 'Este cobro está anulado.']);
        }

        $solicitudAprobada = SolicitudDevolucion::where('cobro_id', $cobro->id)
            ->where('estado', 'aprobada')->exists();

        if ($solicitudAprobada) {
            return back()->withErrors(['error' => 'Este cobro ya fue devuelto anteriormente.']);
        }

        $solicitudPendiente = SolicitudDevolucion::where('cobro_id', $cobro->id)
            ->where('estado', 'pendiente')->exists();

        if ($solicitudPendiente) {
            return back()->withErrors(['error' => 'Ya tienes una solicitud de devolución pendiente para este cobro.']);
        }

        $cobro->load(['estudiante.persona', 'detallePagos.item', 'comprobante']);

        return view('cajero.devolucion.solicitar', compact('cobro'));
    }

    public function solicitar(Request $request, Cobro $cobro)
    {
        if ($cobro->usuario_id !== $request->user()->id) {
            abort(403);
        }

        if (! in_array($cobro->estado, ['pagado', 'pendiente'])) {
            return back()->withErrors(['error' => 'Este cobro está anulado.']);
        }

        $solicitudAprobada = SolicitudDevolucion::where('cobro_id', $cobro->id)
            ->where('estado', 'aprobada')->exists();

        if ($solicitudAprobada) {
            return back()->withErrors(['error' => 'Este cobro ya fue devuelto anteriormente.']);
        }

        $solicitudPendiente = SolicitudDevolucion::where('cobro_id', $cobro->id)
            ->where('estado', 'pendiente')->exists();

        if ($solicitudPendiente) {
            return back()->withErrors(['error' => 'Ya tienes una solicitud de devolución pendiente para este cobro.']);
        }

        $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        SolicitudDevolucion::create([
            'cobro_id'              => $cobro->id,
            'cajero_solicitante_id' => $request->user()->id,
            'admin_autoriza_id'     => null,
            'motivo'                => $request->motivo,
            'estado'                => 'pendiente',
            'fecha_solicitud'       => now(),
            'monto_devuelto'        => null,
        ]);

        return redirect()
            ->route('cajero.devolucion.buscar')
            ->with('mensaje', 'Solicitud de devolución enviada. Espera la autorización del administrador.');
    }
    public function entregar(Request $request, SolicitudDevolucion $solicitud)
    {
        if ($solicitud->cajero_solicitante_id !== $request->user()->id) {
            abort(403);
        }

        if ($solicitud->estado !== 'aprobada') {
            return back()->withErrors(['error' => 'La devolución no está aprobada.']);
        }

        if (! $solicitud->fueEntregada()) {
            $arqueo = $request->user()->arqueoAbierto();

            if ($arqueo->montoSistemaCalculado() < (float) $solicitud->monto_devuelto) {
                return back()->withErrors(['error' => 'No hay suficiente dinero en caja para entregar esta devolución.']);
            }

            $solicitud->update([
                'arqueo_caja_id'     => $arqueo->id,
                'fecha_entrega'      => now(),
                'numero_comprobante' => 'D-' . str_pad($solicitud->id, 8, '0', STR_PAD_LEFT),
            ]);
        }

        return redirect()->route('cajero.devolucion.comprobante', $solicitud);
    }

    public function comprobante(Request $request, SolicitudDevolucion $solicitud)
    {
        if ($solicitud->cajero_solicitante_id !== $request->user()->id) {
            abort(403);
        }

        if (! $solicitud->fueEntregada()) {
            return redirect()->route('cajero.devolucion.buscar')->withErrors(['error' => 'La devolución aún no fue entregada.']);
        }

        $solicitud->load(['cobro.estudiante.persona', 'cobro.detallePagos.item', 'cobro.comprobante', 'cajeroSolicitante.persona', 'adminAutoriza.persona']);

        return view('cajero.devolucion.comprobante', ['solicitud' => $solicitud, 'volver' => route('cajero.devolucion.buscar')]);
    }

    public function misSolicitudes(Request $request)
    {
        return redirect()->route('cajero.devolucion.buscar');
    }
}