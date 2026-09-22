@extends('layouts.app')

@section('titulo', 'Mis solicitudes de devolución')

@section('contenido')
<h4 class="mb-3">Mis solicitudes de devolución</h4>

@if($solicitudes->isEmpty())
    <div class="alert alert-secondary">No tienes solicitudes de devolución registradas.</div>
@else
    <div class="table-responsive">
        <table class="table table-bordered bg-white align-middle">
            <thead>
                <tr>
                    <th>Comprobante</th>
                    <th>Estudiante</th>
                    <th>Ítem(s)</th>
                    <th>Monto</th>
                    <th>Fecha cobro</th>
                    <th>Fecha solicitud</th>
                    <th>Estado</th>
                    <th>Fecha resolución</th>
                </tr>
            </thead>
            <tbody>
                @foreach($solicitudes as $solicitud)
                    <tr>
                        <td>{{ $solicitud->cobro->comprobante?->numero_comprobante ?? '—' }}</td>
                        <td>{{ $solicitud->cobro->estudiante->persona->nombreCompleto() }}</td>
                        <td>{{ $solicitud->cobro->detallePagos->pluck('item.nombre')->implode(', ') }}</td>
                        <td>Bs. {{ number_format($solicitud->cobro->monto_total, 2) }}</td>
                        <td>{{ $solicitud->cobro->fecha_pago->format('d/m/Y H:i') }}</td>
                        <td>{{ $solicitud->fecha_solicitud->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($solicitud->estado === 'pendiente')
                                <span class="badge bg-warning text-dark">En espera</span>
                            @elseif($solicitud->estado === 'aprobada')
                                <span class="badge bg-success">Aprobada — cobro anulado</span>
                            @else
                                <span class="badge bg-danger">Rechazada</span>
                            @endif
                        </td>
                        <td>{{ $solicitud->fecha_resolucion?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection