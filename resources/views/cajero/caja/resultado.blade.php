@extends('layouts.app')

@section('titulo', 'Resultado del cierre')

@section('contenido')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card bg-white mt-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Caja cerrada</h4>
                <table class="table table-sm">
                    <tr>
                        <td>Monto de apertura</td>
                        <td class="text-end">Bs. {{ number_format($arqueo->monto_apertura, 2) }}</td>
                    </tr>
                    <tr>
                        <td>(+) Recaudado en el turno</td>
                        <td class="text-end">Bs. {{ number_format($arqueo->totalRecaudado(), 2) }}</td>
                    </tr>
                    <tr>
                        <td>(-) Devoluciones entregadas</td>
                        <td class="text-end">Bs. {{ number_format($arqueo->totalDevuelto(), 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total según sistema</td>
                        <td class="text-end">Bs. {{ number_format($arqueo->monto_cierre_sistema, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total contado físicamente</td>
                        <td class="text-end">Bs. {{ number_format($arqueo->monto_cierre_fisico, 2) }}</td>
                    </tr>
                </table>

                <div class="alert alert-success">La caja cuadró correctamente. Sin diferencias.</div>

                <p class="text-muted">Retira el dinero físico y depositalo en el lugar correspondiente</p>

                <div class="d-flex gap-2">
                   <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">Cerrar sesion</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
