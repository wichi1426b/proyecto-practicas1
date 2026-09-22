<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArqueoCaja;
use App\Models\Cobro;
use App\Models\DetallePago;
use App\Models\Item;
use App\Models\SolicitudReimpresion;
use App\Models\SolicitudDevolucion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $tipo            = $request->input('tipo', 'general');
        $itemId          = $request->input('item_id');
        $estadoSolicitud = in_array($request->input('estado_solicitud'), ['pendiente', 'aprobada', 'rechazada'])
                           ? $request->input('estado_solicitud')
                           : null;

        $datos = match ($tipo) {
            'por_item'      => $this->reportePorItem($desde, $hasta, $itemId),
            'arqueo'        => $this->reporteArqueos($desde, $hasta),
            'reimpresiones' => $this->reporteReimpresiones($desde, $hasta, $estadoSolicitud),
            'devoluciones'  => $this->reporteDevoluciones($desde, $hasta, $estadoSolicitud),
            default         => $this->reporteGeneral($desde, $hasta),
        };

        return view('admin.reportes.index', $datos + [
            'desde'           => $desde->format('Y-m-d'),
            'hasta'           => $hasta->format('Y-m-d'),
            'tipo'            => $tipo,
            'itemId'          => $itemId,
            'estadoSolicitud' => $estadoSolicitud,
            'items'           => Item::orderBy('nombre')->get(),
        ]);
    }

    private function reporteGeneral(Carbon $desde, Carbon $hasta): array
    {
        $cobrosPagados = Cobro::whereBetween('fecha_pago', [$desde, $hasta])
            ->where('estado', 'pagado');

        $porTipoPago = (clone $cobrosPagados)
            ->selectRaw('tipo_pago, COUNT(*) as cantidad, SUM(monto_total) as total')
            ->groupBy('tipo_pago')
            ->get();

        $detalleCobros = Cobro::whereBetween('fecha_pago', [$desde, $hasta])
            ->with(['estudiante.persona', 'usuario.persona', 'detallePagos.item', 'comprobante'])
            ->orderByDesc('fecha_pago')
            ->get();

        return [
            'cantidadCobros'   => (clone $cobrosPagados)->count(),
            'cantidadAnulados' => Cobro::whereBetween('fecha_pago', [$desde, $hasta])
                                       ->where('estado', 'anulado')->count(),
            'totalRecaudado'   => (clone $cobrosPagados)->sum('monto_total'),
            'porTipoPago'      => $porTipoPago,
            'detalleCobros'    => $detalleCobros,
        ];
    }

    private function reportePorItem(Carbon $desde, Carbon $hasta, ?string $itemId): array
    {
        $detalle = DetallePago::query()
            ->join('cobros', 'cobros.id', '=', 'detalle_pagos.cobro_id')
            ->join('items', 'items.id', '=', 'detalle_pagos.item_id')
            ->whereBetween('cobros.fecha_pago', [$desde, $hasta])
            ->where('cobros.estado', 'pagado')
            ->when($itemId, fn($q) => $q->where('detalle_pagos.item_id', $itemId));

        $porItem = (clone $detalle)
            ->selectRaw('items.id, items.nombre, SUM(detalle_pagos.cantidad) as cantidad_vendida, SUM(detalle_pagos.subtotal) as total_recaudado')
            ->groupBy('items.id', 'items.nombre')
            ->orderByDesc('total_recaudado')
            ->get();

        $detalleVentas = DetallePago::with(['item', 'cobro.estudiante.persona', 'cobro.usuario.persona'])
            ->whereHas('cobro', function($q) use ($desde, $hasta) {
                $q->whereBetween('fecha_pago', [$desde, $hasta])->where('estado', 'pagado');
            })
            ->when($itemId, fn($q) => $q->where('item_id', $itemId))
            ->get()
            ->sortByDesc(fn($d) => $d->cobro->fecha_pago);

        return [
            'porItem'        => $porItem,
            'cantidadCobros' => (clone $detalle)->selectRaw('COUNT(DISTINCT cobros.id) as total')->value('total'),
            'totalRecaudado' => $porItem->sum('total_recaudado'),
            'detalleVentas'  => $detalleVentas,
        ];
    }

    private function reporteArqueos(Carbon $desde, Carbon $hasta): array
    {
        $arqueos = ArqueoCaja::with('usuario.persona')
            ->withCount([
                'cobros as cobros_pagados'  => fn($q) => $q->where('estado', 'pagado'),
                'cobros as cobros_anulados' => fn($q) => $q->where('estado', 'anulado'),
            ])
            ->whereBetween('fecha_apertura', [$desde, $hasta])
            ->orderByDesc('fecha_apertura')
            ->get();

        $totalRecaudado = Cobro::whereIn('arqueo_caja_id', $arqueos->pluck('id'))
            ->where('estado', 'pagado')
            ->sum('monto_total');

        return [
            'arqueos'         => $arqueos,
            'totalRecaudado'  => $totalRecaudado,
            'totalDiferencia' => $arqueos->sum('diferencia'),
        ];
    }

    private function reporteReimpresiones(Carbon $desde, Carbon $hasta, ?string $estado): array
    {
        $solicitudes = SolicitudReimpresion::with([
                'comprobante.cobro.estudiante.persona',
                'comprobante.cobro.detallePagos.item',
                'cajeroSolicitante.persona',
                'adminAutoriza.persona',
            ])
            ->whereBetween('fecha_solicitud', [$desde, $hasta])
            ->when($estado, fn($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha_solicitud')
            ->get();

        return [
            'solicitudes'     => $solicitudes,
            'totalPendientes' => $solicitudes->where('estado', 'pendiente')->count(),
            'totalAprobadas'  => $solicitudes->where('estado', 'aprobada')->count(),
            'totalRechazadas' => $solicitudes->where('estado', 'rechazada')->count(),
        ];
    }

    private function reporteDevoluciones(Carbon $desde, Carbon $hasta, ?string $estado): array
    {
        $solicitudes = SolicitudDevolucion::with([
                'cobro.estudiante.persona',
                'cobro.detallePagos.item',
                'cobro.comprobante',
                'cobro.arqueoCaja',
                'cajeroSolicitante.persona',
                'adminAutoriza.persona',
            ])
            ->whereBetween('fecha_solicitud', [$desde, $hasta])
            ->when($estado, fn($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha_solicitud')
            ->get();

        return [
            'solicitudes'        => $solicitudes,
            'totalPendientes'    => $solicitudes->where('estado', 'pendiente')->count(),
            'totalAprobadas'     => $solicitudes->where('estado', 'aprobada')->count(),
            'totalRechazadas'    => $solicitudes->where('estado', 'rechazada')->count(),
            'montoTotalDevuelto' => $solicitudes->where('estado', 'aprobada')->sum('monto_devuelto'),
        ];
    }

    private function rangoFechas(Request $request): array
    {
        $desde = $request->filled('desde')
            ? Carbon::parse($request->input('desde'))->startOfDay()
            : now()->startOfDay();

        $hasta = $request->filled('hasta')
            ? Carbon::parse($request->input('hasta'))->endOfDay()
            : now()->endOfDay();

        if ($hasta < $desde) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }
}