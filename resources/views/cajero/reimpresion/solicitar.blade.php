@extends('layouts.app')

@section('titulo', 'Solicitar reimpresión')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-6">
        <h4 class="mb-3">Solicitar reimpresión de comprobante</h4>

        {{-- Datos del comprobante --}}
        <div class="card bg-white mb-3">
            <div class="card-body">
                <h6 class="text-muted mb-2">Datos del comprobante</h6>
                <div class="d-flex justify-content-between">
                    <span>N° Comprobante</span>
                    <strong>{{ $comprobante->numero_comprobante }}</strong>
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
                    <span>Monto total</span>
                    <strong>Bs. {{ number_format($cobro->monto_total, 2) }}</strong>
                </div>
            </div>
        </div>
        <div class="card bg-white">
            <div class="card-body">
                <form method="POST" action="{{ route('cajero.cobros.reimpresion.solicitar', $cobro) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Motivo de la reimpresión <span class="text-danger">*</span></label>
                        <textarea
                            name="motivo"
                            class="form-control @error('motivo') is-invalid @enderror"
                            rows="4"
                            minlength="10"
                            maxlength="500"
                            placeholder="Explica por qué necesitas reimprimir este comprobante..."
                            required
                        >{{ old('motivo') }}</textarea>
                        @error('motivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Mínimo 10 caracteres, máximo 500.</div>
                    </div>

                    <div class="alert alert-warning py-2">
                        <small>La reimpresión requiere autorización del administrador. No podrás reimprimir hasta que sea aprobada.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Enviar solicitud</button>
                        <a href="{{ route('cajero.cobros.comprobante', $cobro) }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection