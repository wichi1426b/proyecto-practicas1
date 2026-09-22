@extends('layouts.app')

@section('titulo', 'Apertura de caja')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card bg-white mt-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Apertura de caja</h4>
                <p class="text-muted">Antes de empezar seleccione cuanto es su fondo de apertura</p>
                <form method="POST" action="{{ route('cajero.caja.abrir') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Monto de apertura (Bs)</label>
                        <input type="number" step="0.01" min="0" name="monto_apertura" class="form-control" value="{{ old('monto_apertura') }}" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Aperturar caja</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
