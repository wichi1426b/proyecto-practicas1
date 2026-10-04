@extends('layouts.app')

@section('titulo', 'Pagar saldo pendiente')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card bg-white">
            <div class="card-body">
                <h4 class="card-title mb-3">Pagar saldo pendiente</h4>

                <table class="table table-sm">
                    <tr><td>Comprobante</td><td class="text-end">{{ $cobro->comprobante?->numero_comprobante }}</td></tr>
                    <tr><td>Estudiante</td><td class="text-end">{{ $cobro->estudiante->persona->nombreCompleto() }} — CI {{ $cobro->estudiante->persona_ci }}</td></tr>
                    <tr><td>Carrera</td><td class="text-end">{{ $cobro->estudiante->carrera->nombre }}</td></tr>
                    <tr><td>Detalle</td><td class="text-end">{{ $cobro->detallePagos->map(fn($d) => $d->cantidad . ' x ' . $d->item->nombre)->implode(', ') }}</td></tr>
                </table>

                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between"><span>Total a pagar</span><strong>Bs. {{ number_format($cobro->monto_total, 2) }}</strong></div>
                        <div class="d-flex justify-content-between"><span>Pagado</span><strong>Bs. {{ number_format($cobro->monto_pagado, 2) }}</strong></div>
                        <div class="d-flex justify-content-between fs-5 text-danger"><span>Falta</span><strong id="saldoActual" data-saldo="{{ $cobro->saldo_pendiente }}">Bs. {{ number_format($cobro->saldo_pendiente, 2) }}</strong></div>
                    </div>
                </div>

                <h6>Pagos anteriores</h6>
                <table class="table table-sm table-bordered mb-4">
                    <thead class="table-light"><tr><th>Fecha</th><th class="text-end">Monto</th></tr></thead>
                    <tbody>
                        @foreach($cobro->abonos as $abono)
                            <tr><td>{{ $abono->fecha->format('d/m/Y H:i') }}</td><td class="text-end">Bs. {{ number_format($abono->monto, 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>

                <form method="POST" action="{{ route('cajero.cobros.saldo.pagar', $cobro) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Monto entregado por el estudiante</label>
                        <input type="number" step="0.01" min="0.01" name="monto_recibido" id="inputMontoPagado" class="form-control" required autofocus>
                    </div>
                    <div class="d-flex justify-content-between fs-5 mb-3">
                        <span id="etiquetaResultado">Cambio</span>
                        <strong id="resultado">Bs. 0.00</strong>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Registrar pago</button>
                    <a href="{{ route('cajero.cobros.create') }}" class="btn btn-outline-secondary w-100 mt-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        var saldo = parseFloat(document.getElementById('saldoActual').dataset.saldo);
        var input = document.getElementById('inputMontoPagado');
        var etiqueta = document.getElementById('etiquetaResultado');
        var resultado = document.getElementById('resultado');

        input.addEventListener('input', function () {
            var diferencia = Math.round(((parseFloat(input.value) || 0) - saldo) * 100) / 100;
            etiqueta.textContent = diferencia < 0 ? 'Seguirá faltando' : 'Cambio a entregar';
            resultado.textContent = 'Bs. ' + Math.abs(diferencia).toFixed(2);
            resultado.className = diferencia < 0 ? 'text-danger' : 'text-success';
        });
    })();
</script>
@endsection
