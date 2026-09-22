<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use App\Models\Cobro;
use App\Models\DetallePago;
use App\Models\Estudiante;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CobroController extends Controller
{
    public function create(Request $request)
    {
        $items = Item::activos()->orderBy('nombre')->get();
        $estudiantes = Estudiante::with(['persona', 'carrera'])->where('estado', 'activo')->get();
        $arqueo = $request->user()->arqueoAbierto();

        $estudiantesJson = $estudiantes->map(function ($estudiante) {
            $apellidos = trim("{$estudiante->persona->ap_paterno} {$estudiante->persona->ap_materno}");

            return [
                'id' => $estudiante->id,
                'ci' => $estudiante->persona_ci,
                'nombre' => $estudiante->persona->nombre,
                'apellidos' => $apellidos,
                'carrera' => $estudiante->carrera->nombre,
                'etiqueta' => "{$estudiante->persona->nombre} {$apellidos} — CI {$estudiante->persona_ci}",
            ];
        });

        return view('cajero.cobros.create', compact('items', 'estudiantesJson', 'arqueo'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'estudiante_id' => ['required', 'exists:estudiantes,id'],
            'tipo_pago' => ['required', 'in:efectivo,tarjeta,transferencia'],
            'item_id' => ['required', 'exists:items,id'],
        ]);

        $arqueo = $request->user()->arqueoAbierto();

        if (! $arqueo) {
            return redirect()->route('cajero.caja.apertura');
        }

        $cobro = DB::transaction(function () use ($datos, $request, $arqueo) {
            $item = Item::findOrFail($datos['item_id']);
            $subtotal = $item->monto;

            $cobro = Cobro::create([
                'usuario_id' => $request->user()->id,
                'estudiante_id' => $datos['estudiante_id'],
                'arqueo_caja_id' => $arqueo->id,
                'monto_total' => $subtotal,
                'tipo_pago' => $datos['tipo_pago'],
                'fecha_pago' => now(),
                'estado' => 'pagado',
            ]);

            DetallePago::create([
                'cobro_id' => $cobro->id,
                'item_id' => $item->id,
                'cantidad' => 1,
                'subtotal' => $subtotal,
            ]);

            Comprobante::create([
                'cobro_id' => $cobro->id,
                'numero_comprobante' => 'C-' . str_pad($cobro->id, 8, '0', STR_PAD_LEFT),
                'fecha_emision' => now(),
                'es_reimpresion' => false,
                'anulado' => false,
            ]);

            return $cobro;
        });

        return redirect()->route('cajero.cobros.comprobante', $cobro);
    }

    public function comprobante(Request $request, Cobro $cobro)
    {
        if ($cobro->usuario_id !== $request->user()->id) {
            abort(403);
        }

        $cobro->load(['estudiante.persona', 'usuario.persona', 'detallePagos.item', 'comprobante']);

        return view('cajero.cobros.comprobante', compact('cobro'));
    }
}
