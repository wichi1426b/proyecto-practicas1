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
                            'solicitudesReimpresion',
                        ])
                        ->where('estudiante_id', $estudiante->id)
                        ->where('usuario_id', $request->user()->id)
                        ->vigentes()
                        ->orderByDesc('fecha_pago')
                        ->get();
                }
            }
        }
        $solicitudes = SolicitudReimpresion::with(['comprobante'])
            ->where('cajero_solicitante_id', $request->user()->id)
            ->orderByDesc('fecha_solicitud')
            ->get();

        return view('cajero.reimpresion.buscar', compact('cobros', 'carnet', 'buscado', 'solicitudes'));
    }
    public function formSolicitar(Request $request, Cobro $cobro)
    {
        if ($cobro->usuario_id !== $request->user()->id) {
            abort(403);
        }
        if (! in_array($cobro->estado, ['pagado', 'pendiente'])) {
            return back()->withErrors(['error' => 'Este cobro está anulado.']);
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

        if (! in_array($cobro->estado, ['pagado', 'pendiente'])) {
            return back()->withErrors(['error' => 'Este cobro está anulado.']);
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
            ->route('cajero.reimpresion.buscar')
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
        if (!$solicitud->reimpresion_ejecutada) {
            $solicitud->reimpresion_ejecutada = true;
            $solicitud->save();
        }

        $cobro->load(['estudiante.persona', 'usuario.persona', 'detallePagos.item']);

        return view('cajero.reimpresion.comprobante_reimpresion', [
            'cobro'       => $cobro,
            'comprobante' => $comprobante,
            'solicitud'   => $solicitud,
        ]);
    }
    public function misSolicitudes(Request $request)
    {
        return redirect()->route('cajero.reimpresion.buscar');
    }
}