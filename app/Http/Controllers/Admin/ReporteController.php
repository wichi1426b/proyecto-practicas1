<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abono;
use App\Models\ArqueoCaja;
use App\Models\Cobro;
use App\Models\DetallePago;
use App\Models\Item;
use App\Models\SolicitudReimpresion;
use App\Models\SolicitudDevolucion;
use App\Support\PdfTabla;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReporteController extends Controller
{
    private const TITULOS = [
        'general'       => 'Reporte general de cobros',
        'por_item'      => 'Reporte de cobros por ítem',
        'arqueo'        => 'Reporte de arqueos de caja',
        'reimpresiones' => 'Reporte de reimpresiones',
        'devoluciones'  => 'Reporte de devoluciones',
        'saldos'        => 'Reporte de saldos pendientes',
    ];

    public function index(Request $request)
    {
        [$tipo, $desde, $hasta, $itemId, $estadoSolicitud, $datos] = $this->construir($request);

        return view('admin.reportes.index', $datos + [
            'desde'           => $desde->format('Y-m-d'),
            'hasta'           => $hasta->format('Y-m-d'),
            'tipo'            => $tipo,
            'itemId'          => $itemId,
            'estadoSolicitud' => $estadoSolicitud,
            'items'           => Item::orderBy('nombre')->get(),
        ]);
    }

    public function exportar(Request $request, string $formato)
    {
        abort_unless(in_array($formato, ['pdf', 'excel']), 404);

        [$tipo, $desde, $hasta, , , $datos] = $this->construir($request);
        $tabla = $this->tablaExportable($tipo, $datos);

        $titulo = self::TITULOS[$tipo];
        $periodo = 'Periodo: ' . $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y');
        $nombre = 'reporte_' . $tipo . '_' . $desde->format('Ymd') . '_' . $hasta->format('Ymd');

        if ($formato === 'pdf') {
            $pdf = (new PdfTabla($titulo, [$periodo]))->generar($tabla['columnas'], $tabla['filas'], $tabla['resumen']);

            return response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $nombre . '.pdf"',
            ]);
        }

        $encabezado = [$titulo, $periodo];
        foreach ($tabla['resumen'] as $etiqueta => $valor) {
            $encabezado[] = "{$etiqueta}: {$valor}";
        }

        $filas = array_map(fn ($fila) => array_map(fn ($v) => $this->valorExcel($v), $fila), $tabla['filas']);

        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
        Xlsx::escribir($ruta, 'Reporte', array_column($tabla['columnas'], 'titulo'), $filas, $encabezado);

        return response()->download($ruta, $nombre . '.xlsx')->deleteFileAfterSend();
    }

    private function construir(Request $request): array
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $tipo            = array_key_exists($request->input('tipo'), self::TITULOS) ? $request->input('tipo') : 'general';
        $itemId          = $request->input('item_id');
        $estadoSolicitud = in_array($request->input('estado_solicitud'), ['pendiente', 'aprobada', 'rechazada'])
                           ? $request->input('estado_solicitud')
                           : null;

        $datos = match ($tipo) {
            'por_item'      => $this->reportePorItem($desde, $hasta, $itemId),
            'arqueo'        => $this->reporteArqueos($desde, $hasta),
            'reimpresiones' => $this->reporteReimpresiones($desde, $hasta, $estadoSolicitud),
            'devoluciones'  => $this->reporteDevoluciones($desde, $hasta, $estadoSolicitud),
            'saldos'        => $this->reporteSaldos($desde, $hasta),
            default         => $this->reporteGeneral($desde, $hasta),
        };

        return [$tipo, $desde, $hasta, $itemId, $estadoSolicitud, $datos];
    }

    private function reporteGeneral(Carbon $desde, Carbon $hasta): array
    {
        $cobrosVigentes = Cobro::whereBetween('fecha_pago', [$desde, $hasta])->vigentes();

        $porTipoPago = (clone $cobrosVigentes)
            ->selectRaw('tipo_pago, COUNT(*) as cantidad, SUM(monto_pagado) as total')
            ->groupBy('tipo_pago')
            ->get();

        $detalleCobros = Cobro::whereBetween('fecha_pago', [$desde, $hasta])
            ->with(['estudiante.persona', 'usuario.persona', 'detallePagos.item', 'comprobante'])
            ->orderByDesc('fecha_pago')
            ->get();

        $totalRecaudado = (float) Abono::whereBetween('fecha', [$desde, $hasta])->sum('monto');
        $totalDevuelto  = (float) SolicitudDevolucion::whereBetween('fecha_entrega', [$desde, $hasta])->sum('monto_devuelto');

        return [
            'cantidadCobros'   => (clone $cobrosVigentes)->count(),
            'cantidadAnulados' => Cobro::whereBetween('fecha_pago', [$desde, $hasta])
                                       ->where('estado', 'anulado')->count(),
            'totalRecaudado'   => $totalRecaudado,
            'totalDevuelto'    => $totalDevuelto,
            'totalNeto'        => $totalRecaudado - $totalDevuelto,
            'totalPendiente'   => (clone $cobrosVigentes)->sum('saldo_pendiente'),
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
            ->whereIn('cobros.estado', ['pagado', 'pendiente'])
            ->when($itemId, fn($q) => $q->where('detalle_pagos.item_id', $itemId));

        $porItem = (clone $detalle)
            ->selectRaw('items.id, items.nombre, SUM(detalle_pagos.cantidad) as cantidad_vendida, SUM(detalle_pagos.subtotal) as total_recaudado')
            ->groupBy('items.id', 'items.nombre')
            ->orderByDesc('total_recaudado')
            ->get();

        $detalleVentas = DetallePago::with(['item', 'cobro.estudiante.persona', 'cobro.usuario.persona'])
            ->whereHas('cobro', function($q) use ($desde, $hasta) {
                $q->whereBetween('fecha_pago', [$desde, $hasta])->vigentes();
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
                'cobros as cobros_pagados'  => fn($q) => $q->whereIn('estado', ['pagado', 'pendiente']),
                'cobros as cobros_anulados' => fn($q) => $q->where('estado', 'anulado'),
            ])
            ->withSum('abonos as recaudado', 'monto')
            ->withSum(['devoluciones as devuelto' => fn($q) => $q->whereNotNull('fecha_entrega')], 'monto_devuelto')
            ->whereBetween('fecha_apertura', [$desde, $hasta])
            ->orderByDesc('fecha_apertura')
            ->get();

        return [
            'arqueos'         => $arqueos,
            'totalRecaudado'  => $arqueos->sum('recaudado'),
            'totalDevuelto'   => $arqueos->sum('devuelto'),
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
            'montoTotalDevuelto' => $solicitudes->where('estado', 'aprobada')->whereNotNull('fecha_entrega')->sum('monto_devuelto'),
        ];
    }

    private function reporteSaldos(Carbon $desde, Carbon $hasta): array
    {
        $cobros = Cobro::with(['estudiante.persona', 'estudiante.carrera', 'detallePagos.item', 'comprobante'])
            ->where('estado', 'pendiente')
            ->where('saldo_pendiente', '>', 0)
            ->whereBetween('fecha_pago', [$desde, $hasta])
            ->orderBy('fecha_pago')
            ->get();

        return [
            'cobrosPendientes' => $cobros,
            'totalCobrado'     => $cobros->sum('monto_total'),
            'totalPagado'      => $cobros->sum('monto_pagado'),
            'totalPendiente'   => $cobros->sum('saldo_pendiente'),
        ];
    }

    private function tablaExportable(string $tipo, array $d): array
    {
        $bs = fn ($monto) => number_format((float) $monto, 2);
        $items = fn ($cobro) => $cobro->detallePagos->map(fn ($x) => $x->cantidad . ' x ' . $x->item->nombre)->implode(', ');
        $col = fn ($titulo, $peso = 1, $alinear = 'L') => compact('titulo', 'peso', 'alinear');

        return match ($tipo) {
            'por_item' => [
                'resumen'  => ['Cobros registrados' => $d['cantidadCobros'] ?? 0, 'Total vendido (Bs.)' => $bs($d['totalRecaudado'])],
                'columnas' => [$col('Fecha', 1.3), $col('Ítem', 2), $col('Estudiante', 2.5), $col('Cajero', 2), $col('Cantidad', 0.8, 'R'), $col('Subtotal (Bs.)', 1, 'R')],
                'filas'    => $d['detalleVentas']->map(fn ($x) => [
                    $x->cobro->fecha_pago->format('d/m/Y H:i'), $x->item->nombre, $x->cobro->estudiante->persona->nombreCompleto(),
                    $x->cobro->usuario->persona->nombreCompleto(), $x->cantidad, $bs($x->subtotal),
                ])->values()->all(),
            ],
            'arqueo' => [
                'resumen'  => ['Total recaudado (Bs.)' => $bs($d['totalRecaudado']), 'Total devuelto (Bs.)' => $bs($d['totalDevuelto']), 'Diferencia acumulada (Bs.)' => $bs($d['totalDiferencia'])],
                'columnas' => [$col('Cajero', 2), $col('Apertura', 1.3), $col('Cierre', 1.3), $col('Fondo', 1, 'R'), $col('Recaudado', 1, 'R'), $col('Devuelto', 1, 'R'),
                               $col('Sistema', 1, 'R'), $col('Físico', 1, 'R'), $col('Diferencia', 1, 'R'), $col('Estado', 0.8)],
                'filas'    => $d['arqueos']->map(fn ($a) => [
                    $a->usuario->persona->nombreCompleto(), $a->fecha_apertura->format('d/m/Y H:i'), $a->fecha_cierre?->format('d/m/Y H:i') ?? '—',
                    $bs($a->monto_apertura), $bs($a->recaudado), $bs($a->devuelto),
                    $a->monto_cierre_sistema !== null ? $bs($a->monto_cierre_sistema) : '—',
                    $a->monto_cierre_fisico !== null ? $bs($a->monto_cierre_fisico) : '—',
                    $a->diferencia !== null ? $bs($a->diferencia) : '—', $a->estado,
                ])->values()->all(),
            ],
            'reimpresiones' => [
                'resumen'  => ['Total' => $d['solicitudes']->count(), 'Aprobadas' => $d['totalAprobadas'], 'Rechazadas' => $d['totalRechazadas'], 'Pendientes' => $d['totalPendientes']],
                'columnas' => [$col('Fecha', 1.3), $col('Comprobante', 1.2), $col('Estudiante', 2.2), $col('Ítem(s)', 2), $col('Monto', 0.9, 'R'),
                               $col('Cajero', 1.8), $col('Motivo', 2.2), $col('Estado', 0.9), $col('Autorizó', 1.8)],
                'filas'    => $d['solicitudes']->map(fn ($s) => [
                    $s->fecha_solicitud->format('d/m/Y H:i'), $s->comprobante->numero_comprobante, $s->comprobante->cobro->estudiante->persona->nombreCompleto(),
                    $items($s->comprobante->cobro), $bs($s->comprobante->cobro->monto_total), $s->cajeroSolicitante->persona->nombreCompleto(),
                    $s->motivo, $s->estado, $s->adminAutoriza?->persona->nombreCompleto() ?? '—',
                ])->values()->all(),
            ],
            'devoluciones' => [
                'resumen'  => ['Total' => $d['solicitudes']->count(), 'Aprobadas' => $d['totalAprobadas'], 'Rechazadas' => $d['totalRechazadas'], 'Monto devuelto (Bs.)' => $bs($d['montoTotalDevuelto'])],
                'columnas' => [$col('Fecha', 1.3), $col('Comprobante', 1.2), $col('Estudiante', 2.2), $col('Ítem(s)', 2), $col('Cobro', 0.9, 'R'),
                               $col('Devuelto', 0.9, 'R'), $col('Comp. devolución', 1.3), $col('Cajero', 1.8), $col('Estado', 0.9), $col('Resolvió', 1.8)],
                'filas'    => $d['solicitudes']->map(fn ($s) => [
                    $s->fecha_solicitud->format('d/m/Y H:i'), $s->cobro->comprobante?->numero_comprobante ?? '—', $s->cobro->estudiante->persona->nombreCompleto(),
                    $items($s->cobro), $bs($s->cobro->monto_total), $s->monto_devuelto !== null ? $bs($s->monto_devuelto) : '—',
                    $s->numero_comprobante ?? '—', $s->cajeroSolicitante->persona->nombreCompleto(), $s->estado,
                    $s->adminAutoriza?->persona->nombreCompleto() ?? '—',
                ])->values()->all(),
            ],
            'saldos' => [
                'resumen'  => ['Cobros con saldo' => $d['cobrosPendientes']->count(), 'Total (Bs.)' => $bs($d['totalCobrado']), 'Pagado (Bs.)' => $bs($d['totalPagado']), 'Falta (Bs.)' => $bs($d['totalPendiente'])],
                'columnas' => [$col('Fecha', 1.3), $col('Comprobante', 1.2), $col('CI', 1), $col('Estudiante', 2.2), $col('Carrera', 1.8),
                               $col('Ítem(s)', 2.2), $col('Total', 1, 'R'), $col('Pagado', 1, 'R'), $col('Falta', 1, 'R')],
                'filas'    => $d['cobrosPendientes']->map(fn ($c) => [
                    $c->fecha_pago->format('d/m/Y H:i'), $c->comprobante?->numero_comprobante ?? '—', $c->estudiante->persona_ci,
                    $c->estudiante->persona->nombreCompleto(), $c->estudiante->carrera->nombre, $items($c),
                    $bs($c->monto_total), $bs($c->monto_pagado), $bs($c->saldo_pendiente),
                ])->values()->all(),
            ],
            default => [
                'resumen'  => ['Cobros registrados' => $d['cantidadCobros'], 'Anulados' => $d['cantidadAnulados'], 'Recaudado (Bs.)' => $bs($d['totalRecaudado']),
                               'Devuelto (Bs.)' => $bs($d['totalDevuelto']), 'Neto (Bs.)' => $bs($d['totalNeto']), 'Saldos pendientes (Bs.)' => $bs($d['totalPendiente'])],
                'columnas' => [$col('Fecha', 1.3), $col('Comprobante', 1.2), $col('Estudiante', 2.3), $col('Ítem(s)', 2.5), $col('Cajero', 1.8),
                               $col('Total', 0.9, 'R'), $col('Pagado', 0.9, 'R'), $col('Falta', 0.9, 'R'), $col('Estado', 0.9)],
                'filas'    => $d['detalleCobros']->map(fn ($c) => [
                    $c->fecha_pago->format('d/m/Y H:i'), $c->comprobante?->numero_comprobante ?? '—', $c->estudiante->persona->nombreCompleto(),
                    $items($c), $c->usuario->persona->nombreCompleto(), $bs($c->monto_total), $bs($c->monto_pagado), $bs($c->saldo_pendiente), $c->estado,
                ])->values()->all(),
            ],
        };
    }

    private function valorExcel($valor)
    {
        if (is_string($valor) && preg_match('/^-?[\d,]+\.\d{2}$/', $valor)) {
            return (float) str_replace(',', '', $valor);
        }

        return $valor;
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
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        return [$desde, $hasta];
    }
}
