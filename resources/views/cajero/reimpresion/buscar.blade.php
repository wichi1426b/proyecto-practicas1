@extends('layouts.app')

@section('titulo', 'Reimpresión de comprobantes')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-10">

        <h4 class="mb-4">Reimpresión de comprobantes</h4>
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
                <form method="POST" action="{{ route('cajero.reimpresion.buscar.post') }}"
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
                                    $comprobante       = $cobro->comprobante;
                                    $solicitudPend     = $cobro->solicitudesReimpresion->where('estado', 'pendiente')->isNotEmpty();
                                    $solicitudAprobada = $cobro->solicitudesReimpresion
                                                            ->where('estado', 'aprobada')
                                                            ->where('reimpresion_ejecutada', false)
                                                            ->first();
                                    $puedesSolicitar   = $comprobante && !$comprobante->anulado && !$solicitudPend && !$solicitudAprobada;
                                @endphp
                                <tr>
                                    <td>{{ $comprobante?->numero_comprobante ?? '—' }}</td>
                                    <td>{{ $cobro->detallePagos->pluck('item.nombre')->implode(', ') }}</td>
                                    <td>Bs. {{ number_format($cobro->monto_total, 2) }}</td>
                                    <td>{{ $cobro->fecha_pago->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($comprobante?->anulado)
                                            <span class="badge bg-danger">Comprobante anulado</span>
                                        @elseif($solicitudAprobada)
                                            <span class="badge bg-success">Aprobada — lista para imprimir</span>
                                        @elseif($solicitudPend)
                                            <span class="badge bg-warning text-dark">Pendiente de admin</span>
                                        @else
                                            <span class="badge bg-secondary">Sin solicitud</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($solicitudAprobada)
                                            <form method="POST"
                                                  action="{{ route('cajero.reimpresion.ejecutar', $solicitudAprobada) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    Imprimir ahora
                                                </button>
                                            </form>
                                        @elseif($puedesSolicitar)
                                            <a href="{{ route('cajero.cobros.reimpresion.form', $cobro) }}"
                                               class="btn btn-outline-primary btn-sm">
                                                Solicitar reimpresión
                                            </a>
                                        @elseif($solicitudPend)
                                            <span class="text-muted small">En espera</span>
                                        @else
                                            <span class="text-muted small">No disponible</span>
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
            <div class="card-header fw-semibold">Mis solicitudes de reimpresión</div>
            <div class="card-body p-0">
                @if($solicitudes->isEmpty())
                    <div class="p-3 text-muted">No tienes solicitudes de reimpresión registradas.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 bg-white align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Comprobante</th>
                                    <th>Fecha solicitud</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                    <th>Fecha autorización</th>
                                    <th class="text-center">Acción</th>
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
                                        <td class="text-center">
                                            @if($solicitud->estado === 'aprobada' && !$solicitud->reimpresion_ejecutada)
                                                <form method="POST"
                                                      action="{{ route('cajero.reimpresion.ejecutar', $solicitud) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        Imprimir ahora
                                                    </button>
                                                </form>
                                            @elseif($solicitud->estado === 'aprobada' && $solicitud->reimpresion_ejecutada)
                                                <span class="text-muted small">Ya impresa</span>
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
            </div>
        </div>

    </div>
</div>
@endsection