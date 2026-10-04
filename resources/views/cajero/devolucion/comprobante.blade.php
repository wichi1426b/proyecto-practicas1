@extends('layouts.app')

@section('titulo', 'Comprobante de devolución')

@section('contenido')
<div class="row justify-content-center no-imprimir">
    <div class="col-md-6">
        <div class="alert alert-info text-center mt-3">
            <h4 class="mb-1">Devolución registrada</h4>
            <p class="mb-0">Comprobante N° {{ $solicitud->numero_comprobante }}</p>
            <p class="fs-4 fw-bold mb-0 mt-2">Monto devuelto: Bs. {{ number_format($solicitud->monto_devuelto, 2) }}</p>
        </div>

        <div class="d-flex gap-2 justify-content-center mb-4">
            <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir comprobante</button>
            <a href="{{ $volver }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>
</div>

<div id="comprobante-imprimir" class="comprobante-ticket">
    <div class="text-center">
        <div class="comprobante-titulo">UPDS</div>
        <div class="comprobante-subtitulo">COMPROBANTE DE DEVOLUCIÓN</div>
    </div>
    <hr>
    <div class="comprobante-fila"><span>N°</span><span>{{ $solicitud->numero_comprobante }}</span></div>
    <div class="comprobante-fila"><span>Fecha</span><span>{{ $solicitud->fecha_entrega->format('d/m/Y H:i') }}</span></div>
    <div class="comprobante-fila"><span>Cajero</span><span>{{ $solicitud->cajeroSolicitante->persona->nombreCompleto() }}</span></div>
    <div class="comprobante-fila"><span>Autorizó</span><span>{{ $solicitud->adminAutoriza?->persona->nombreCompleto() }}</span></div>
    <div class="comprobante-fila"><span>C.I.</span><span>{{ $solicitud->cobro->estudiante->persona_ci }}</span></div>
    <div class="comprobante-fila"><span>Nombre</span><span>{{ $solicitud->cobro->estudiante->persona->nombreCompleto() }}</span></div>
    <hr>
    <div class="comprobante-fila"><span>Comprobante anulado</span><span>{{ $solicitud->cobro->comprobante?->numero_comprobante }}</span></div>
    <div class="comprobante-fila"><span>Fecha del cobro</span><span>{{ $solicitud->cobro->fecha_pago->format('d/m/Y') }}</span></div>
    <hr>
    <div class="comprobante-fila comprobante-encabezado"><span>Detalle</span><span>Monto</span></div>
    @foreach($solicitud->cobro->detallePagos as $detalle)
        <div class="comprobante-fila"><span>{{ $detalle->cantidad }} x {{ $detalle->item->nombre }}</span><span>{{ number_format($detalle->subtotal, 2) }}</span></div>
    @endforeach
    <hr>
    <div class="comprobante-fila"><span>Total del cobro</span><span>{{ number_format($solicitud->cobro->monto_total, 2) }}</span></div>
    <div class="comprobante-fila comprobante-total"><span>DEVUELTO</span><span>{{ number_format($solicitud->monto_devuelto, 2) }}</span></div>
    <hr>
    <div class="small">Motivo: {{ $solicitud->motivo }}</div>
    <div class="d-flex justify-content-between comprobante-firma">
        <span>Entregué conforme</span>
        <span>Recibí conforme</span>
    </div>
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
        font-size: 0.8rem;
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
