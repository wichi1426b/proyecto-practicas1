@extends('layouts.app')

@section('titulo', 'Mis solicitudes de reimpresión')

@section('contenido')
<h4 class="mb-3">Mis solicitudes de reimpresión</h4>

@if($solicitudes->isEmpty())
    <div class="alert alert-secondary">No tienes solicitudes de reimpresión registradas.</div>
@else
    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>Comprobante</th>
                    <th>Fecha solicitud</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Fecha autorización</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($solicitudes as $solicitud)
                    <tr>
                        <td>{{ $solicitud->comprobante->numero_comprobante }}</td>
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
                        <td>{{ $solicitud->fecha_autorizacion?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            @if($solicitud->estado === 'aprobada')
                                <form method="POST" action="{{ route('cajero.reimpresion.ejecutar', $solicitud) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        Reimprimir ahora
                                    </button>
                                </form>
                            @elseif($solicitud->estado === 'pendiente')
                                <span class="text-muted small">En espera</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection