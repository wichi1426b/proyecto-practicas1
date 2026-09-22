@extends('layouts.app')

@section('titulo', 'Cerrar caja')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card bg-white mt-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Cerrar caja</h4>

                <table class="table table-sm">
                    <tr>
                        <td>Monto de apertura</td>
                        <td class="text-end">Bs. {{ number_format($arqueo->monto_apertura, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Recaudado en el turno</td>
                        <td class="text-end">Bs {{ number_format($arqueo->totalRecaudado(), 2) }}</td>
                    </tr>
                    <tr class="fw-bold">
                        <td>Total que debería haber en caja</td>
                        <td class="text-end">Bs. {{ number_format($montoSistema, 2) }}</td>
                    </tr>
                </table>
                <p class="text-muted">Cuenta tu dinero físico e ingresa el monto exacto que tienes en caja. Deberia coincidir con el total del sistema para poder cerrar la caja</p>
                <form method="POST" action="{{ route('cajero.caja.confirmar_cierre') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Monto contado físicamente (Bs)</label>
                        <input type="number" step="0.01" min="0" name="monto_cierre_fisico" class="form-control" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-danger w-100">Confirmar cierre de caja</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
