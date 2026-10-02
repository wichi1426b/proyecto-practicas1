@extends('layouts.app')

@section('titulo', 'Reimpresión de comprobante')

@section('contenido')
<div class="row justify-content-center no-imprimir">
    <div class="col-md-6">
        <div class="alert alert-info text-center mt-3">
            <h4 class="mb-1">Reimpresión autorizada</h4>
            <p class="mb-0">Comprobante original N° {{ $cobro->comprobante->numero_comprobante }}</p>
        </div>

        <div class="d-flex gap-2 justify-content-center mb-4">
            <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir</button>
            <a href="{{ route('cajero.cobros.create') }}" class="btn btn-outline-secondary">Registrar otro cobro</a>
        </div>
    </div>
</div>

<div id="comprobante-imprimir" class="comprobante-ticket">
    <div class="text-center">
        <div class="comprobante-titulo">UPDS</div>
        <div class="comprobante-subtitulo">COMPROBANTE DE PAGO</div>
        <div style="font-weight:bold; font-size:0.85rem; border: 1px solid #000; padding: 2px 6px; margin-top: 4px;">
            *** REIMPRESIÓN — NO VÁLIDO COMO ORIGINAL ***
        </div>
    </div>
    <hr>
    <div class="comprobante-fila"><span>N° Comprobante</span><span>{{ $comprobante->numero_comprobante }}</span></div>
    <div class="comprobante-fila"><span>Fecha reimpresión</span><span>{{ now()->format('d/m/Y H:i') }}</span></div>
    <div class="comprobante-fila"><span>Fecha pago original</span><span>{{ $cobro->fecha_pago->format('d/m/Y') }}</span></div>
    <div class="comprobante-fila"><span>Cajero</span><span>{{ $cobro->usuario->persona->nombreCompleto() }}</span></div>
    <div class="comprobante-fila"><span>C.I.</span><span>{{ $cobro->estudiante->persona_ci }}</span></div>
    <div class="comprobante-fila"><span>Nombre</span><span>{{ $cobro->estudiante->persona->nombreCompleto() }}</span></div>
    <hr>
    <div class="comprobante-fila comprobante-encabezado"><span>Detalle</span><span>Monto</span></div>
    @foreach($cobro->detallePagos as $detalle)
        <div class="comprobante-fila">
            <span>{{ $detalle->item->nombre }}</span>
            <span>{{ number_format($detalle->subtotal, 2) }}</span>
        </div>
    @endforeach
    <hr>
    <div class="comprobante-fila comprobante-total">
        <span>TOTAL</span>
        <span>{{ number_format($cobro->monto_total, 2) }}</span>
    </div>
    <hr>
    <div class="text-center comprobante-firma">FIRMA</div>
</div>
@endsection

@section('scripts')
<style>
    .comprobante-ticket {
        max-width: 320px;
        margin: 0 auto;
        padding: 16px;
        background: #fff;
        border: 1px solid #ddd;
        font-family: 'Courier New', monospace;
    }
    .comprobante-titulo { font-size: 1.5rem; font-weight: bold; }
    .comprobante-subtitulo { font-size: 0.85rem; }
    .comprobante-fila { display: flex; justify-content: space-between; gap: 8px; }
    .comprobante-encabezado { font-weight: bold; }
    .comprobante-total { font-weight: bold; font-size: 1.1rem; }
    .comprobante-firma { margin-top: 40px; }

    @media print {
        body * { visibility: hidden; }
        #comprobante-imprimir, #comprobante-imprimir * { visibility: visible; }
        #comprobante-imprimir {
            position: absolute;
            top: 0; left: 0;
            width: 80mm;
            border: none;
        }
        .no-imprimir { display: none !important; }
    }
</style>
@endsection