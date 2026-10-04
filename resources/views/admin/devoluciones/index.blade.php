@extends('layouts.app')

@section('titulo', 'Solicitudes de devolución')

@section('contenido')
<h4 class="mb-3">Solicitudes de devolución</h4>

{{-- Filtro por estado --}}
<form method="GET" action="{{ route('admin.devoluciones.index') }}" class="row g-2 align-items-end mb-4">
    <div class="col-auto">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select">
            <option value="">Todos</option>
            <option value="pendiente"  @selected($estado === 'pendiente')>Pendiente</option>
            <option value="aprobada"   @selected($estado === 'aprobada')>Aprobada</option>
            <option value="rechazada"  @selected($estado === 'rechazada')>Rechazada</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

@if($solicitudes->isEmpty())
    <div class="alert alert-secondary">No hay solicitudes para mostrar.</div>
@else
    <div class="table-responsive">
        <table class="table table-bordered bg-white align-middle">
            <thead>
                <tr>
                    <th>Comprobante</th>
                    <th>Cajero</th>
                    <th>Estudiante</th>
                    <th>Ítem(s)</th>
                    <th>Monto</th>
                    <th>Devuelto</th>
                    <th>Fecha cobro</th>
                    <th>Fecha solicitud</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Resuelto por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($solicitudes as $solicitud)
                    <tr>
                        <td>{{ $solicitud->cobro->comprobante?->numero_comprobante ?? '—' }}</td>
                        <td>{{ $solicitud->cajeroSolicitante->persona->nombreCompleto() }}</td>
                        <td>{{ $solicitud->cobro->estudiante->persona->nombreCompleto() }}</td>
                        <td>{{ $solicitud->cobro->detallePagos->pluck('item.nombre')->implode(', ') }}</td>
                        <td>Bs. {{ number_format($solicitud->cobro->monto_total, 2) }}</td>
                        <td>
                            @if($solicitud->estado === 'aprobada')
                                Bs. {{ number_format($solicitud->monto_devuelto, 2) }}<br>
                                @if($solicitud->fueEntregada())
                                    <a href="{{ route('admin.devoluciones.comprobante', $solicitud) }}" class="small">{{ $solicitud->numero_comprobante }}</a>
                                @else
                                    <span class="badge bg-warning text-dark">Por entregar</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $solicitud->cobro->fecha_pago->format('d/m/Y H:i') }}</td>
                        <td>{{ $solicitud->fecha_solicitud->format('d/m/Y H:i') }}</td>
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
                        <td>
                            @if($solicitud->adminAutoriza)
                                {{ $solicitud->adminAutoriza->persona->nombreCompleto() }}<br>
                                <small class="text-muted">{{ $solicitud->fecha_resolucion?->format('d/m/Y H:i') }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($solicitud->estado === 'pendiente')
                                <form method="POST"
                                      action="{{ route('admin.devoluciones.aprobar', $solicitud) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('¿Aprobar esta devolución? El cobro quedará ANULADO.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success mb-1">Aprobar</button>
                                </form>
                                <form method="POST"
                                      action="{{ route('admin.devoluciones.rechazar', $solicitud) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('¿Rechazar esta solicitud de devolución?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger">Rechazar</button>
                                </form>
                            @else
                                <span class="text-muted small">Procesada</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="alert alert-secondary mt-3">
        <small>Al aprobar, el cobro y su comprobante quedan anulados. El dinero se descuenta de la caja del cajero cuando este registra la entrega al estudiante, generando el comprobante de devolución.</small>
    </div>
@endif
@endsection