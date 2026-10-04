<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\Abono;
use App\Models\Comprobante;
use App\Models\Cobro;
use App\Models\DetallePago;
use App\Models\Estudiante;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CobroController extends Controller
{
    public function create(Request $request)
    {
        $items = Item::activos()->with('carreras:id')->orderBy('nombre')->get();
        $estudiantes = Estudiante::with(['persona', 'carrera'])->where('estado', 'activo')->get();
        $arqueo = $request->user()->arqueoAbierto();

        $deudas = Cobro::with(['comprobante', 'detallePagos.item'])
            ->where('estado', 'pendiente')
            ->where('saldo_pendiente', '>', 0)
            ->get()
            ->groupBy('estudiante_id');

        $estudiantesJson = $estudiantes->map(function ($estudiante) use ($deudas) {
            $apellidos = trim("{$estudiante->persona->ap_paterno} {$estudiante->persona->ap_materno}");

            return [
                'id' => $estudiante->id,
                'ci' => $estudiante->persona_ci,
                'nombre' => $estudiante->persona->nombre,
                'apellidos' => $apellidos,
                'carrera_id' => $estudiante->carrera_id,
                'carrera' => $estudiante->carrera->nombre,
                'etiqueta' => "{$estudiante->persona->nombre} {$apellidos} — CI {$estudiante->persona_ci}",
                'deudas' => ($deudas[$estudiante->id] ?? collect())->map(fn ($cobro) => [
                    'comprobante' => $cobro->comprobante?->numero_comprobante,
                    'detalle' => $cobro->detallePagos->map(fn ($d) => "{$d->cantidad} x {$d->item->nombre}")->implode(', '),
                    'total' => (float) $cobro->monto_total,
                    'pagado' => (float) $cobro->monto_pagado,
                    'saldo' => (float) $cobro->saldo_pendiente,
                    'url' => route('cajero.cobros.saldo.form', $cobro),
                ])->values(),
            ];
        });

        $itemsJson = $items->map(fn ($item) => [
            'id' => $item->id,
            'nombre' => $item->nombre,
            'monto' => (float) $item->monto,
            'carreras' => $item->carreras->pluck('id'),
        ]);

        $resumenTurno = $arqueo ? [
            'recaudado' => $arqueo->totalRecaudado(),
            'devuelto' => $arqueo->totalDevuelto(),
            'en_caja' => $arqueo->montoSistemaCalculado(),
        ] : null;

        return view('cajero.cobros.create', compact('estudiantesJson', 'itemsJson', 'arqueo', 'resumenTurno'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'estudiante_id' => ['required', 'exists:estudiantes,id'],
            'tipo_pago' => ['required', 'in:efectivo,tarjeta,transferencia'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
            'monto_recibido' => ['required', 'numeric', 'min:0.01'],
        ], [
            'items.required' => 'Agrega al menos un ítem al cobro.',
            'monto_recibido.required' => 'Ingresa el monto que entregó el estudiante.',
        ]);

        $arqueo = $request->user()->arqueoAbierto();

        if (! $arqueo) {
            return redirect()->route('cajero.caja.apertura');
        }

        $estudiante = Estudiante::findOrFail($datos['estudiante_id']);

        $cantidades = [];
        foreach ($datos['items'] as $linea) {
            $cantidades[$linea['item_id']] = ($cantidades[$linea['item_id']] ?? 0) + (int) $linea['cantidad'];
        }

        $items = Item::activos()
            ->paraCarrera($estudiante->carrera_id)
            ->whereIn('id', array_keys($cantidades))
            ->get()
            ->keyBy('id');

        if ($items->count() !== count($cantidades)) {
            throw ValidationException::withMessages([
                'items' => 'Uno de los ítems no está disponible o no corresponde a la carrera del estudiante.',
            ]);
        }

        $total = round($items->sum(fn ($item) => $item->monto * $cantidades[$item->id]), 2);
        $recibido = round((float) $datos['monto_recibido'], 2);
        $aplicado = min($recibido, $total);
        $cambio = round(max(0, $recibido - $total), 2);
        $saldo = round($total - $aplicado, 2);

        $cobro = DB::transaction(function () use ($datos, $request, $arqueo, $items, $cantidades, $total, $recibido, $aplicado, $cambio, $saldo) {
            $cobro = Cobro::create([
                'usuario_id' => $request->user()->id,
                'estudiante_id' => $datos['estudiante_id'],
                'arqueo_caja_id' => $arqueo->id,
                'monto_total' => $total,
                'monto_pagado' => $aplicado,
                'saldo_pendiente' => $saldo,
                'tipo_pago' => $datos['tipo_pago'],
                'fecha_pago' => now(),
                'estado' => $saldo > 0 ? 'pendiente' : 'pagado',
            ]);

            foreach ($items as $item) {
                DetallePago::create([
                    'cobro_id' => $cobro->id,
                    'item_id' => $item->id,
                    'cantidad' => $cantidades[$item->id],
                    'subtotal' => round($item->monto * $cantidades[$item->id], 2),
                ]);
            }

            Abono::create([
                'cobro_id' => $cobro->id,
                'arqueo_caja_id' => $arqueo->id,
                'usuario_id' => $request->user()->id,
                'monto' => $aplicado,
                'monto_recibido' => $recibido,
                'cambio' => $cambio,
                'fecha' => now(),
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

    public function formSaldo(Cobro $cobro)
    {
        if (! $cobro->tieneSaldo()) {
            return redirect()->route('cajero.cobros.create')->withErrors(['error' => 'Este cobro no tiene saldo pendiente.']);
        }

        $cobro->load(['estudiante.persona', 'estudiante.carrera', 'detallePagos.item', 'comprobante', 'abonos']);

        return view('cajero.cobros.saldo', compact('cobro'));
    }

    public function pagarSaldo(Request $request, Cobro $cobro)
    {
        $datos = $request->validate([
            'monto_recibido' => ['required', 'numeric', 'min:0.01'],
        ]);

        $arqueo = $request->user()->arqueoAbierto();

        DB::transaction(function () use ($cobro, $datos, $request, $arqueo) {
            $cobro = Cobro::lockForUpdate()->findOrFail($cobro->id);

            if (! $cobro->tieneSaldo()) {
                throw ValidationException::withMessages(['monto_recibido' => 'Este cobro ya no tiene saldo pendiente.']);
            }

            $recibido = round((float) $datos['monto_recibido'], 2);
            $saldoActual = (float) $cobro->saldo_pendiente;
            $aplicado = min($recibido, $saldoActual);
            $nuevoSaldo = round($saldoActual - $aplicado, 2);

            Abono::create([
                'cobro_id' => $cobro->id,
                'arqueo_caja_id' => $arqueo->id,
                'usuario_id' => $request->user()->id,
                'monto' => $aplicado,
                'monto_recibido' => $recibido,
                'cambio' => round(max(0, $recibido - $saldoActual), 2),
                'fecha' => now(),
            ]);

            $cobro->update([
                'monto_pagado' => round($cobro->monto_pagado + $aplicado, 2),
                'saldo_pendiente' => $nuevoSaldo,
                'estado' => $nuevoSaldo > 0 ? 'pendiente' : 'pagado',
            ]);
        });

        return redirect()->route('cajero.cobros.comprobante', $cobro)
            ->with('mensaje', 'Pago registrado correctamente.');
    }

    public function comprobante(Request $request, Cobro $cobro)
    {
        $participo = $cobro->usuario_id === $request->user()->id
            || $cobro->abonos()->where('usuario_id', $request->user()->id)->exists();

        if (! $participo) {
            abort(403);
        }

        $cobro->load(['estudiante.persona', 'usuario.persona', 'detallePagos.item', 'comprobante', 'abonos']);

        return view('cajero.cobros.comprobante', compact('cobro'));
    }
}
