@extends('layouts.app')

@section('titulo', 'Registrar cobro')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-7">
        @if($arqueo)
            <div class="alert alert-secondary">
                <strong>Turno abierto desde:</strong> {{ $arqueo->fecha_apertura->format('d/m/Y H:i') }}
                — <strong>Fondo de apertura:</strong> Bs. {{ number_format($arqueo->monto_apertura, 2) }}
            </div>
        @endif

        <div class="card bg-white">
            <div class="card-body">
                <h4 class="card-title mb-3">Registrar cobro</h4>

                <form method="POST" action="{{ route('cajero.cobros.store') }}">
                    @csrf

                    <div class="mb-3 position-relative">
                        <label class="form-label">Buscar estudiante por CI</label>
                        <input type="text" id="inputBuscarEstudiante" class="form-control" placeholder="Escriba el CI del estudiante" autofocus autocomplete="off">
                        <div id="sugerenciasEstudiante" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1000;"></div>
                    </div>

                    <div id="datosEstudiante" class="card bg-light mb-3 d-none">
                        <div class="card-body row">
                            <div class="col-md-4">
                                <div class="text-muted small">Nombre</div>
                                <div id="estudianteNombre" class="fw-bold"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Apellidos</div>
                                <div id="estudianteApellidos" class="fw-bold"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Carrera</div>
                                <div id="estudianteCarrera" class="fw-bold"></div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="estudiante_id" id="inputEstudianteId">

                    <fieldset id="camposEstudiante" disabled>
                        <div class="mb-3">
                            <label class="form-label">Tipo de pago</label>
                            <select name="tipo_pago" class="form-select" required>
                                <option value="efectivo">Efectivo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ítem</label>
                            <select id="selectItem" name="item_id" class="form-select" required>
                                <option value="">Selecciona un Item</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-monto="{{ $item->monto }}">
                                        {{ $item->nombre }} — Bs. {{ number_format($item->monto, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <fieldset id="camposPago" disabled>
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between fs-5">
                                        <span>Total a cobrar</span>
                                        <strong id="totalCobro">Bs 0.00</strong>
                                    </div>

                                    <div class="mb-3 mt-3">
                                        <label class="form-label">Pago del estudiante?</label>
                                        <input type="number" step="0.01" min="0" id="inputMontoPagado" class="form-control">
                                    </div>

                                    <div class="d-flex justify-content-between fs-4">
                                        <span>Cambio</span>
                                        <strong id="cambioCobro">Bs 0.00</strong>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Registrar cobro</button>
                        </fieldset>
                    </fieldset>
                </form>

                <script id="datosEstudiantes" type="application/json">@json($estudiantesJson)</script>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/cobro.js') }}"></script>
@endsection
