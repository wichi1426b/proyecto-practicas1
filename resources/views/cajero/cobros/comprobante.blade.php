@extends('layouts.app')

@section('titulo', 'Cobro registrado')

@section('contenido')
<div class="row justify-content-center no-imprimir">
    <div class="col-md-6">
        @php $ultimoAbono = $cobro->abonos->sortBy('id')->last(); @endphp
        <div class="alert {{ $cobro->tieneSaldo() ? 'alert-warning' : 'alert-success' }} text-center mt-3">
            <h4 class="mb-1">{{ $cobro->tieneSaldo() ? 'Pago parcial registrado' : 'Cobro registrado correctamente' }}</h4>
            <p class="mb-0">Comprobante N° {{ $cobro->comprobante->numero_comprobante }}</p>
            @if($ultimoAbono && $ultimoAbono->cambio > 0)
                <p class="fs-4 fw-bold mb-0 mt-2">Cambio a entregar: Bs. {{ number_format($ultimoAbono->cambio, 2) }}</p>
            @endif
            @if($cobro->tieneSaldo())
                <p class="fs-5 fw-bold mb-0 mt-2 text-danger">Falta pagar: Bs. {{ number_format($cobro->saldo_pendiente, 2) }}</p>
            @endif
        </div>

        <div class="d-flex gap-2 justify-content-center mb-4">
            <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir comprobante</button>
            <a href="{{ route('cajero.cobros.create') }}" class="btn btn-outline-secondary">Registrar otro cobro</a>
        </div>
    </div>
</div>

<div id="comprobante-imprimir" class="comprobante-ticket">
    <div class="text-center">
        <div class="comprobante-titulo">UPDS</div>
        <div class="comprobante-subtitulo">COMPROBANTE DE PAGO</div>
        @if($cobro->tieneSaldo())
            <div class="comprobante-subtitulo"><strong>PAGO PARCIAL</strong></div>
        @endif
    </div>
    <hr>
    <div class="comprobante-fila"><span>N°</span><span>{{ $cobro->comprobante->numero_comprobante }}</span></div>
    <div class="comprobante-fila"><span>Fecha</span><span>{{ $cobro->fecha_pago->format('d/m/Y') }}</span></div>
    <div class="comprobante-fila"><span>Cajero</span><span>{{ $cobro->usuario->persona->nombreCompleto() }}</span></div>
    <div class="comprobante-fila"><span>C.I.</span><span>{{ $cobro->estudiante->persona_ci }}</span></div>
    <div class="comprobante-fila"><span>Nombre</span><span>{{ $cobro->estudiante->persona->nombreCompleto() }}</span></div>
    <hr>
    <div class="comprobante-fila comprobante-encabezado"><span>Detalle</span><span>Monto</span></div>
    @foreach($cobro->detallePagos as $detalle)
        <div class="comprobante-fila"><span>{{ $detalle->cantidad }} x {{ $detalle->item->nombre }}</span><span>{{ number_format($detalle->subtotal, 2) }}</span></div>
    @endforeach
    <hr>
    <div class="comprobante-fila comprobante-total"><span>TOTAL</span><span>{{ number_format($cobro->monto_total, 2) }}</span></div>
    <div class="comprobante-fila"><span>Pagado</span><span>{{ number_format($cobro->monto_pagado, 2) }}</span></div>
    @if($cobro->tieneSaldo())
        <div class="comprobante-fila comprobante-total"><span>FALTA</span><span>{{ number_format($cobro->saldo_pendiente, 2) }}</span></div>
    @endif
    @if($ultimoAbono)
        <hr>
        <div class="comprobante-fila"><span>Recibido</span><span>{{ number_format($ultimoAbono->monto_recibido, 2) }}</span></div>
        <div class="comprobante-fila"><span>Cambio</span><span>{{ number_format($ultimoAbono->cambio, 2) }}</span></div>
    @endif
    @if($cobro->abonos->count() > 1)
        <hr>
        <div class="comprobante-fila comprobante-encabezado"><span>Pagos</span><span></span></div>
        @foreach($cobro->abonos->sortBy('id') as $abono)
            <div class="comprobante-fila"><span>{{ $abono->fecha->format('d/m/Y H:i') }}</span><span>{{ number_format($abono->monto, 2) }}</span></div>
        @endforeach
    @endif
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
    .comprobante-titulo {
        font-size: 1.5rem;
        font-weight: bold;
    }
    .comprobante-subtitulo {
        font-size: 0.85rem;
    }
    .comprobante-fila {
        display: flex;
        justify-content: space-between;
        gap: 8px;
    }
    .comprobante-encabezado {
        font-weight: bold;
    }
    .comprobante-total {
        font-weight: bold;
        font-size: 1.1rem;
    }
    .comprobante-firma {
        margin-top: 40px;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #comprobante-imprimir, #comprobante-imprimir * {
            visibility: visible;
        }
        #comprobante-imprimir {
            position: absolute;
            top: 0;
            left: 0;
            width: 80mm;
            border: none;
        }
        .no-imprimir {
            display: none !important;
        }
    }
</style>
@endsection
