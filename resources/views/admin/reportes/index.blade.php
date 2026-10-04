@extends('layouts.app')

@section('titulo', 'Reporte')

@section('contenido')
<h3 class="mb-3">Reporte</h3>

<form method="GET" action="{{ route('admin.reportes.index') }}" class="row g-2 align-items-end mb-4">

    {{-- Tipo de reporte --}}
    <div class="col-auto">
        <label class="form-label">Tipo de reporte</label>
        <select id="selectTipoReporte" name="tipo" class="form-select">
            <option value="general"       @selected($tipo === 'general')>General</option>
            <option value="por_item"      @selected($tipo === 'por_item')>Por Item</option>
            <option value="arqueo"        @selected($tipo === 'arqueo')>Por arqueo de caja</option>
            <option value="reimpresiones" @selected($tipo === 'reimpresiones')>Reimpresiones</option>
            <option value="devoluciones"  @selected($tipo === 'devoluciones')>Devoluciones</option>
            <option value="saldos"        @selected($tipo === 'saldos')>Saldos pendientes</option>
        </select>
    </div>

    {{-- Desde --}}
    <div class="col-auto">
        <label class="form-label">Desde</label>
        <input type="date" name="desde" class="form-control" value="{{ $desde }}">
    </div>

    {{-- Hasta --}}
    <div class="col-auto">
        <label class="form-label">Hasta</label>
        <input type="date" name="hasta" class="form-control" value="{{ $hasta }}">
    </div>

    {{-- Filtro ítem (solo para por_item) --}}
    <div class="col-auto" id="filtroItem"
         style="{{ $tipo === 'por_item' ? '' : 'display:none;' }}">
        <label class="form-label">Item</label>
        <select name="item_id" class="form-select">
            <option value="">Todos los Items</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" @selected($itemId == $item->id)>{{ $item->nombre }}</option>
            @endforeach
        </select>
    </div>

    {{-- Filtro estado solicitud (solo para reimpresiones y devoluciones) --}}
    <div class="col-auto" id="filtroEstado"
         style="{{ in_array($tipo, ['reimpresiones','devoluciones']) ? '' : 'display:none;' }}">
        <label class="form-label">Estado</label>
        <select name="estado_solicitud" class="form-select">
            <option value="">Todos</option>
            <option value="pendiente"  @selected($estadoSolicitud === 'pendiente')>Pendiente</option>
            <option value="aprobada"   @selected($estadoSolicitud === 'aprobada')>Aprobada</option>
            <option value="rechazada"  @selected($estadoSolicitud === 'rechazada')>Rechazada</option>
        </select>
    </div>

    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>

    @php $filtros = ['tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta, 'item_id' => $itemId, 'estado_solicitud' => $estadoSolicitud]; @endphp
    <div class="col-auto ms-auto">
        <a href="{{ route('admin.reportes.exportar', ['formato' => 'pdf'] + array_filter($filtros)) }}" class="btn btn-outline-danger">Descargar PDF</a>
        <a href="{{ route('admin.reportes.exportar', ['formato' => 'excel'] + array_filter($filtros)) }}" class="btn btn-outline-success">Descargar Excel</a>
    </div>
</form>

@if($tipo === 'general')
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card bg-white text-center h-100"><div class="card-body">
                <h6 class="text-muted">Cobros registrados</h6>
                <h3>{{ $cantidadCobros }}</h3>
            </div></div>
        </div>
        <div class="col-md-2">
            <div class="card bg-white text-center h-100"><div class="card-body">
                <h6 class="text-muted">Cobros anulados</h6>
                <h3 class="text-danger">{{ $cantidadAnulados }}</h3>
            </div></div>
        </div>
        <div class="col-md-2">
            <div class="card bg-white text-center h-100"><div class="card-body">
                <h6 class="text-muted">Recaudado</h6>
                <h4>Bs. {{ number_format($totalRecaudado, 2) }}</h4>
            </div></div>
        </div>
        <div class="col-md-2">
            <div class="card bg-white text-center h-100"><div class="card-body">
                <h6 class="text-muted">Devuelto</h6>
                <h4 class="text-danger">Bs. {{ number_format($totalDevuelto, 2) }}</h4>
            </div></div>
        </div>
        <div class="col-md-2">
            <div class="card bg-white text-center h-100"><div class="card-body">
                <h6 class="text-muted">Neto en caja</h6>
                <h4 class="text-success">Bs. {{ number_format($totalNeto, 2) }}</h4>
            </div></div>
        </div>
        <div class="col-md-2">
            <div class="card bg-white text-center h-100"><div class="card-body">
                <h6 class="text-muted">Saldos pendientes</h6>
                <h4 class="text-warning">Bs. {{ number_format($totalPendiente, 2) }}</h4>
            </div></div>
        </div>
    </div>

    <h5>Por tipo de pago</h5>
    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>Tipo de pago</th>
                <th>Cantidad</th>
                <th>Total (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($porTipoPago as $fila)
                <tr>
                    <td>{{ $fila->tipo_pago }}</td>
                    <td>{{ $fila->cantidad }}</td>
                    <td>{{ number_format($fila->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center">No hay cobros en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h5>Detalle de cobros</h5>
    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Comprobante</th>
                    <th>Estudiante</th>
                    <th>Ítem(s)</th>
                    <th>Cajero</th>
                    <th>Tipo de pago</th>
                    <th>Total (Bs.)</th>
                    <th>Pagado (Bs.)</th>
                    <th>Falta (Bs.)</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detalleCobros as $cobro)
                    <tr class="{{ $cobro->estado === 'anulado' ? 'table-danger' : '' }}">
                        <td>{{ $cobro->fecha_pago->format('d/m/Y H:i') }}</td>
                        <td>{{ $cobro->comprobante?->numero_comprobante ?? '—' }}</td>
                        <td>{{ $cobro->estudiante->persona->nombreCompleto() }}</td>
                        <td>{{ $cobro->detallePagos->map(fn($d) => $d->cantidad . ' x ' . $d->item->nombre)->implode(', ') }}</td>
                        <td>{{ $cobro->usuario->persona->nombreCompleto() }}</td>
                        <td>{{ $cobro->tipo_pago }}</td>
                        <td>{{ number_format($cobro->monto_total, 2) }}</td>
                        <td>{{ number_format($cobro->monto_pagado, 2) }}</td>
                        <td>{{ number_format($cobro->saldo_pendiente, 2) }}</td>
                        <td>
                            @if($cobro->estado === 'anulado')
                                <span class="badge bg-danger">Anulado</span>
                            @elseif($cobro->estado === 'pendiente')
                                <span class="badge bg-warning text-dark">Pago parcial</span>
                            @else
                                <span class="badge bg-success">Pagado</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">No hay cobros en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@if($tipo === 'por_item')
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Cobros registrados</h6>
                    <h3>{{ $cantidadCobros }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total recaudado</h6>
                    <h3>Bs. {{ number_format($totalRecaudado, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <h5>Por ítem</h5>
    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>Ítem</th>
                <th>Cantidad vendida</th>
                <th>Total (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($porItem as $item)
                <tr>
                    <td>{{ $item->nombre }}</td>
                    <td>{{ $item->cantidad_vendida }}</td>
                    <td>{{ number_format($item->total_recaudado, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center">No hay cobros en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h5>Detalle de ventas</h5>
    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Ítem</th>
                    <th>Estudiante</th>
                    <th>Cajero</th>
                    <th>Cantidad</th>
                    <th>Subtotal (Bs.)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detalleVentas as $detalle)
                    <tr>
                        <td>{{ $detalle->cobro->fecha_pago->format('d/m/Y H:i') }}</td>
                        <td>{{ $detalle->item->nombre }}</td>
                        <td>{{ $detalle->cobro->estudiante->persona->nombreCompleto() }}</td>
                        <td>{{ $detalle->cobro->usuario->persona->nombreCompleto() }}</td>
                        <td>{{ $detalle->cantidad }}</td>
                        <td>{{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No hay ventas en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@if($tipo === 'arqueo')
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total recaudado</h6>
                    <h3>Bs. {{ number_format($totalRecaudado, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Devoluciones entregadas</h6>
                    <h3 class="text-danger">Bs. {{ number_format($totalDevuelto, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Diferencia acumulada</h6>
                    <h3 class="{{ $totalDiferencia < 0 ? 'text-danger' : ($totalDiferencia > 0 ? 'text-warning' : '') }}">
                        Bs. {{ number_format($totalDiferencia, 2) }}
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <h5>Por arqueo de caja</h5>
    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>Cajero</th>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th>Monto apertura</th>
                    <th>Recaudado</th>
                    <th>Devuelto</th>
                    <th>Sistema</th>
                    <th>Físico</th>
                    <th>Diferencia</th>
                    <th>Pagados</th>
                    <th>Anulados</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($arqueos as $arqueo)
                    <tr>
                        <td>{{ $arqueo->usuario->persona->nombreCompleto() }}</td>
                        <td>{{ $arqueo->fecha_apertura->format('d/m/Y H:i') }}</td>
                        <td>{{ $arqueo->fecha_cierre?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ number_format($arqueo->monto_apertura, 2) }}</td>
                        <td>{{ number_format($arqueo->recaudado ?? 0, 2) }}</td>
                        <td class="{{ $arqueo->devuelto > 0 ? 'text-danger' : '' }}">{{ number_format($arqueo->devuelto ?? 0, 2) }}</td>
                        <td>{{ $arqueo->monto_cierre_sistema !== null ? number_format($arqueo->monto_cierre_sistema, 2) : '—' }}</td>
                        <td>{{ $arqueo->monto_cierre_fisico !== null ? number_format($arqueo->monto_cierre_fisico, 2) : '—' }}</td>
                        <td>{{ $arqueo->diferencia !== null ? number_format($arqueo->diferencia, 2) : '—' }}</td>
                        <td>{{ $arqueo->cobros_pagados }}</td>
                        <td class="{{ $arqueo->cobros_anulados > 0 ? 'text-danger fw-bold' : '' }}">
                            {{ $arqueo->cobros_anulados }}
                        </td>
                        <td>
                            <span class="badge {{ $arqueo->estado === 'abierto' ? 'bg-warning text-dark' : 'bg-secondary' }}">
                                {{ $arqueo->estado }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-center">No hay arqueos en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@if($tipo === 'reimpresiones')
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total solicitudes</h6>
                    <h3>{{ $solicitudes->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Aprobadas</h6>
                    <h3 class="text-success">{{ $totalAprobadas }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Rechazadas</h6>
                    <h3 class="text-danger">{{ $totalRechazadas }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Pendientes</h6>
                    <h3 class="text-warning">{{ $totalPendientes }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered bg-white align-middle">
            <thead>
                <tr>
                    <th>Fecha solicitud</th>
                    <th>Comprobante</th>
                    <th>Estudiante</th>
                    <th>Ítem(s)</th>
                    <th>Monto</th>
                    <th>Cajero</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Fecha autorización</th>
                    <th>Autorizado por</th>
                </tr>
            </thead>
            <tbody>
                @forelse($solicitudes as $solicitud)
                    <tr>
                        <td>{{ $solicitud->fecha_solicitud->format('d/m/Y H:i') }}</td>
                        <td>{{ $solicitud->comprobante->numero_comprobante }}</td>
                        <td>{{ $solicitud->comprobante->cobro->estudiante->persona->nombreCompleto() }}</td>
                        <td>{{ $solicitud->comprobante->cobro->detallePagos->pluck('item.nombre')->implode(', ') }}</td>
                        <td>Bs. {{ number_format($solicitud->comprobante->cobro->monto_total, 2) }}</td>
                        <td>{{ $solicitud->cajeroSolicitante->persona->nombreCompleto() }}</td>
                        <td>{{ Str::limit($solicitud->motivo, 60) }}</td>
                        <td>
                            @if($solicitud->estado === 'pendiente')
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            @elseif($solicitud->estado === 'aprobada')
                                <span class="badge bg-success">Aprobada</span>
                            @else
                                <span class="badge bg-danger">Rechazada</span>
                            @endif
                        </td>
                        <td>{{ $solicitud->fecha_autorizacion?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $solicitud->adminAutoriza?->persona->nombreCompleto() ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">No hay solicitudes en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@if($tipo === 'devoluciones')
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total solicitudes</h6>
                    <h3>{{ $solicitudes->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Aprobadas</h6>
                    <h3 class="text-success">{{ $totalAprobadas }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Rechazadas</h6>
                    <h3 class="text-danger">{{ $totalRechazadas }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <h6 class="text-muted">Monto total devuelto</h6>
                    <h3 class="text-danger">Bs. {{ number_format($montoTotalDevuelto, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered bg-white align-middle">
            <thead>
                <tr>
                    <th>Fecha solicitud</th>
                    <th>Comprobante</th>
                    <th>Estudiante</th>
                    <th>Ítem(s)</th>
                    <th>Monto cobro</th>
                    <th>Monto devuelto</th>
                    <th>Comp. devolución</th>
                    <th>Cajero</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Fecha resolución</th>
                    <th>Resuelto por</th>
                </tr>
            </thead>
            <tbody>
                @forelse($solicitudes as $solicitud)
                    <tr>
                        <td>{{ $solicitud->fecha_solicitud->format('d/m/Y H:i') }}</td>
                        <td>{{ $solicitud->cobro->comprobante?->numero_comprobante ?? '—' }}</td>
                        <td>{{ $solicitud->cobro->estudiante->persona->nombreCompleto() }}</td>
                        <td>{{ $solicitud->cobro->detallePagos->pluck('item.nombre')->implode(', ') }}</td>
                        <td>Bs. {{ number_format($solicitud->cobro->monto_total, 2) }}</td>
                        <td>{{ $solicitud->monto_devuelto ? 'Bs. ' . number_format($solicitud->monto_devuelto, 2) : '—' }}</td>
                        <td>
                            @if($solicitud->numero_comprobante)
                                <a href="{{ route('admin.devoluciones.comprobante', $solicitud) }}">{{ $solicitud->numero_comprobante }}</a>
                            @elseif($solicitud->estado === 'aprobada')
                                <span class="badge bg-warning text-dark">Por entregar</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $solicitud->cajeroSolicitante->persona->nombreCompleto() }}</td>
                        <td>{{ Str::limit($solicitud->motivo, 60) }}</td>
                        <td>
                            @if($solicitud->estado === 'pendiente')
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            @elseif($solicitud->estado === 'aprobada')
                                <span class="badge bg-success">Aprobada</span>
                            @else
                                <span class="badge bg-danger">Rechazada</span>
                            @endif
                        </td>
                        <td>{{ $solicitud->fecha_resolucion?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $solicitud->adminAutoriza?->persona->nombreCompleto() ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-center">No hay solicitudes en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endif

@if($tipo === 'saldos')
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-white text-center"><div class="card-body">
                <h6 class="text-muted">Cobros con saldo</h6>
                <h3>{{ $cobrosPendientes->count() }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center"><div class="card-body">
                <h6 class="text-muted">Total a pagar</h6>
                <h3>Bs. {{ number_format($totalCobrado, 2) }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center"><div class="card-body">
                <h6 class="text-muted">Pagado</h6>
                <h3 class="text-success">Bs. {{ number_format($totalPagado, 2) }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center"><div class="card-body">
                <h6 class="text-muted">Falta</h6>
                <h3 class="text-danger">Bs. {{ number_format($totalPendiente, 2) }}</h3>
            </div></div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered bg-white align-middle">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Comprobante</th>
                    <th>CI</th>
                    <th>Estudiante</th>
                    <th>Carrera</th>
                    <th>Ítem(s)</th>
                    <th>Total</th>
                    <th>Pagado</th>
                    <th>Falta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cobrosPendientes as $cobro)
                    <tr>
                        <td>{{ $cobro->fecha_pago->format('d/m/Y H:i') }}</td>
                        <td>{{ $cobro->comprobante?->numero_comprobante ?? '—' }}</td>
                        <td>{{ $cobro->estudiante->persona_ci }}</td>
                        <td>{{ $cobro->estudiante->persona->nombreCompleto() }}</td>
                        <td>{{ $cobro->estudiante->carrera->nombre }}</td>
                        <td>{{ $cobro->detallePagos->map(fn($d) => $d->cantidad . ' x ' . $d->item->nombre)->implode(', ') }}</td>
                        <td>Bs. {{ number_format($cobro->monto_total, 2) }}</td>
                        <td>Bs. {{ number_format($cobro->monto_pagado, 2) }}</td>
                        <td class="fw-bold text-danger">Bs. {{ number_format($cobro->saldo_pendiente, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center">No hay saldos pendientes en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@endsection

@section('scripts')
<script>
    document.getElementById('selectTipoReporte').addEventListener('change', function () {
        const tipo = this.value;
        document.getElementById('filtroItem').style.display   = tipo === 'por_item' ? '' : 'none';
        document.getElementById('filtroEstado').style.display = ['reimpresiones','devoluciones'].includes(tipo) ? '' : 'none';
    });
</script>
@endsection