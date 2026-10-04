@extends('layouts.app')

@section('titulo', 'Registrar cobro')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-lg-8">
        @if($arqueo)
            <div class="alert alert-secondary">
                <div>
                    <strong>Turno abierto desde:</strong> {{ $arqueo->fecha_apertura->format('d/m/Y H:i') }}
                    — <strong>Fondo de apertura:</strong> Bs. {{ number_format($arqueo->monto_apertura, 2) }}
                </div>
                <div>
                    <strong>Recaudado en el turno:</strong> Bs. {{ number_format($resumenTurno['recaudado'], 2) }}
                    @if($resumenTurno['devuelto'] > 0)
                        — <strong>Devoluciones:</strong> Bs. {{ number_format($resumenTurno['devuelto'], 2) }}
                    @endif
                    — <strong>En caja:</strong> Bs. {{ number_format($resumenTurno['en_caja'], 2) }}
                </div>
            </div>
        @endif

        <div class="card bg-white">
            <div class="card-body">
                <h4 class="card-title mb-3">Registrar cobro</h4>

                <form method="POST" action="{{ route('cajero.cobros.store') }}" id="formCobro">
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

                    <div id="deudasEstudiante" class="alert alert-warning d-none">
                        <strong>El estudiante tiene saldos pendientes:</strong>
                        <table class="table table-sm mb-0 mt-2 bg-white">
                            <thead>
                                <tr><th>Comprobante</th><th>Detalle</th><th class="text-end">Total</th><th class="text-end">Pagado</th><th class="text-end">Falta</th><th></th></tr>
                            </thead>
                            <tbody id="tablaDeudas"></tbody>
                        </table>
                    </div>

                    <input type="hidden" name="estudiante_id" id="inputEstudianteId">

                    <fieldset id="camposEstudiante" disabled>
                        <div class="mb-3">
                            <label class="form-label">Tipo de pago</label>
                            <select name="tipo_pago" class="form-select" required>
                                <option value="efectivo">Efectivo</option>
                            </select>
                        </div>

                        <label class="form-label">Agregar ítem</label>
                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <select id="selectItem" class="form-select">
                                    <option value="">Selecciona un ítem</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" id="inputCantidad" class="form-control" min="1" max="999" value="1" title="Cantidad">
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="btnAgregarItem" class="btn btn-outline-primary w-100">Agregar</button>
                            </div>
                        </div>

                        <table class="table table-sm table-bordered bg-white align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Ítem</th>
                                    <th class="text-end" style="width: 110px;">Precio</th>
                                    <th style="width: 110px;">Cantidad</th>
                                    <th class="text-end" style="width: 120px;">Subtotal</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tablaItems">
                                <tr id="filaSinItems"><td colspan="5" class="text-muted text-center">Aún no agregaste ítems</td></tr>
                            </tbody>
                        </table>

                        <fieldset id="camposPago" disabled>
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between fs-5">
                                        <span>Total a pagar</span>
                                        <strong id="totalCobro">Bs. 0.00</strong>
                                    </div>

                                    <div class="mb-3 mt-3">
                                        <label class="form-label">Monto entregado por el estudiante</label>
                                        <input type="number" step="0.01" min="0.01" name="monto_recibido" id="inputMontoPagado" class="form-control" required>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <span>Pagado (queda en caja)</span>
                                        <strong id="pagadoCobro">Bs. 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between fs-4" id="filaCambio">
                                        <span>Cambio a entregar</span>
                                        <strong id="cambioCobro" class="text-success">Bs. 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between fs-4 d-none" id="filaFalta">
                                        <span>Falta (saldo pendiente)</span>
                                        <strong id="faltaCobro" class="text-danger">Bs. 0.00</strong>
                                    </div>
                                    <div id="avisoParcial" class="alert alert-warning py-2 mt-2 mb-0 d-none small">
                                        Se registrará como <strong>pago parcial</strong>. El saldo quedará pendiente hasta completar el pago.
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Registrar cobro</button>
                        </fieldset>
                    </fieldset>
                </form>

                <script id="datosEstudiantes" type="application/json">@json($estudiantesJson)</script>
                <script id="datosItems" type="application/json">@json($itemsJson)</script>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/cobro.js') }}"></script>
@endsection
