@extends('layouts.app')

@section('titulo', 'Solicitar devolución')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-6">
        <h4 class="mb-3">Solicitar devolución de cobro</h4>

        <div class="card bg-white mb-3">
            <div class="card-body">
                <h6 class="text-muted mb-2">Datos del cobro</h6>
                <div class="d-flex justify-content-between">
                    <span>N° Comprobante</span>
                    <strong>{{ $cobro->comprobante->numero_comprobante }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Fecha de pago</span>
                    <strong>{{ $cobro->fecha_pago->format('d/m/Y H:i') }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Estudiante</span>
                    <strong>{{ $cobro->estudiante->persona->nombreCompleto() }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Ítem(s)</span>
                    <strong>{{ $cobro->detallePagos->pluck('item.nombre')->implode(', ') }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Tipo de pago</span>
                    <strong>{{ ucfirst($cobro->tipo_pago) }}</strong>
                </div>
                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                    <span class="fw-bold">Monto a devolver</span>
                    <strong class="text-danger fs-5">Bs. {{ number_format($cobro->monto_total, 2) }}</strong>
                </div>
            </div>
        </div>
        <div class="card bg-white">
            <div class="card-body">
                <form method="POST" action="{{ route('cajero.cobros.devolucion.solicitar', $cobro) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Motivo de la devolución <span class="text-danger">*</span></label>
                        <textarea
                            name="motivo"
                            class="form-control @error('motivo') is-invalid @enderror"
                            rows="4"
                            minlength="10"
                            maxlength="500"
                            placeholder="Explica por qué se debe devolver este cobro..."
                            required
                        >{{ old('motivo') }}</textarea>
                        @error('motivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Mínimo 10 caracteres, máximo 500.</div>
                    </div>

                    <div class="alert alert-danger py-2">
                        <small><strong>Atención:</strong> Si el administrador aprueba esta solicitud, el cobro quedará <strong>anulado</strong> y el comprobante dejará de ser válido.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('¿Seguro que deseas solicitar la devolución de este cobro?')">
                            Enviar solicitud
                        </button>
                        <a href="{{ route('cajero.cobros.comprobante', $cobro) }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection