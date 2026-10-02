@extends('layouts.app')

@section('titulo', 'Devoluciones')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-10">

        <h4 class="mb-4">Solicitar devolución de cobro</h4>

        {{-- Mensajes --}}
        @if(session('mensaje'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('mensaje') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div class="card mb-4">
            <div class="card-header fw-semibold">Buscar cobro por carnet</div>
            <div class="card-body">
                <form method="POST" action="{{ route('cajero.devolucion.buscar.post') }}"
                      class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <label for="carnet" class="form-label">Carnet del estudiante</label>
                        <input type="text"
                               id="carnet"
                               name="carnet"
                               class="form-control @error('carnet') is-invalid @enderror"
                               placeholder="Ej: 12345678"
                               value="{{ old('carnet', $carnet) }}"
                               autofocus
                               autocomplete="off">
                        @error('carnet')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Buscar</button>
                    </div>
                </form>
            </div>
        </div>
        @if($buscado)
            @if($cobros->isEmpty())
                <div class="alert alert-warning">
                    No se encontraron cobros para el carnet <strong>{{ $carnet }}</strong>.
                    Verifica que sea correcto y que el cobro haya sido registrado por tu usuario.
                </div>
            @else
                @php $nombreEstudiante = $cobros->first()->estudiante->persona->nombreCompleto(); @endphp
                <div class="alert alert-secondary py-2 mb-3">
                    Resultados para: <strong>{{ $nombreEstudiante }}</strong>
                    — Carnet: <strong>{{ $carnet }}</strong>
                    — {{ $cobros->count() }} cobro(s)
                </div>

                <div class="table-responsive mb-5">
                    <table class="table table-bordered bg-white align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Comprobante</th>
                                <th>Ítem(s)</th>
                                <th>Monto</th>
                                <th>Fecha pago</th>
                                <th>Estado solicitud</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cobros as $cobro)
                                @php
                                    $solicitudAprobada  = $cobro->solicitudesDevolucion->where('estado', 'aprobada')->isNotEmpty();
                                    $solicitudPendiente = $cobro->solicitudesDevolucion->where('estado', 'pendiente')->isNotEmpty();
                                    $puedesSolicitar    = !$solicitudAprobada && !$solicitudPendiente;
                                @endphp
                                <tr>
                                    <td>{{ $cobro->comprobante?->numero_comprobante ?? '—' }}</td>
                                    <td>{{ $cobro->detallePagos->pluck('item.nombre')->implode(', ') }}</td>
                                    <td>Bs. {{ number_format($cobro->monto_total, 2) }}</td>
                                    <td>{{ $cobro->fecha_pago->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($solicitudAprobada)
                                            <span class="badge bg-success">Devolución aprobada</span>
                                        @elseif($solicitudPendiente)
                                            <span class="badge bg-warning text-dark">Pendiente de admin</span>
                                        @else
                                            <span class="badge bg-secondary">Sin solicitud</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($puedesSolicitar)
                                            <a href="{{ route('cajero.cobros.devolucion.form', $cobro) }}"
                                               class="btn btn-outline-danger btn-sm">
                                                Solicitar devolución
                                            </a>
                                        @elseif($solicitudPendiente)
                                            <span class="text-muted small">En espera</span>
                                        @else
                                            <span class="text-muted small">Ya devuelto</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
        <div class="card">
            <div class="card-header fw-semibold">Mis solicitudes de devolución</div>
            <div class="card-body p-0">
                @if($solicitudes->isEmpty())
                    <div class="p-3 text-muted">No tienes solicitudes de devolución registradas.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 bg-white align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Comprobante</th>
                                    <th>Estudiante</th>
                                    <th>Monto</th>
                                    <th>Fecha cobro</th>
                                    <th>Fecha solicitud</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                    <th>Fecha resolución</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($solicitudes as $solicitud)
                                    <tr>
                                        <td>{{ $solicitud->cobro->comprobante?->numero_comprobante ?? '—' }}</td>
                                        <td>{{ $solicitud->cobro->estudiante->persona->nombreCompleto() }}</td>
                                        <td>Bs. {{ number_format($solicitud->cobro->monto_total, 2) }}</td>
                                        <td>{{ $solicitud->cobro->fecha_pago->format('d/m/Y H:i') }}</td>
                                        <td>{{ $solicitud->fecha_solicitud->format('d/m/Y H:i') }}</td>
                                        <td>{{ Str::limit($solicitud->motivo, 50) }}</td>
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
            </div>
        </div>

    </div>
</div>
@endsection