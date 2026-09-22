@extends('layouts.app')

@section('titulo', 'Cobro registrado')

@section('contenido')
<div class="row justify-content-center no-imprimir">
    <div class="col-md-6">
        <div class="alert alert-success text-center mt-3">
            <h4 class="mb-1">Cobro registrado correctamente</h4>
            <p class="mb-0">Comprobante N° {{ $cobro->comprobante->numero_comprobante }}</p>
        </div>

        <div class="d-flex gap-2 justify-content-center mb-4 flex-wrap">
            <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir comprobante</button>
            <a href="{{ route('cajero.cobros.create') }}" class="btn btn-outline-secondary">Registrar otro cobro</a>

            @php
                $solicitudReimpPendiente = $cobro->comprobante->solicitudesReimpresion()
                    ->where('estado', 'pendiente')->exists();

                $solicitudDevPendiente = $cobro->solicitudesDevolucion()
                    ->where('estado', 'pendiente')->exists();

                $solicitudDevAprobada = $cobro->solicitudesDevolucion()
                    ->where('estado', 'aprobada')->exists();
            @endphp

            {{-- Botón reimpresión --}}
            @if($cobro->estado === 'pagado' && !$cobro->comprobante->anulado && !$solicitudReimpPendiente)
                <a href="{{ route('cajero.cobros.reimpresion.form', $cobro) }}"
                   class="btn btn-outline-warning">
                    Solicitar reimpresión
                </a>
            @elseif($solicitudReimpPendiente)
                <span class="btn btn-outline-secondary disabled">Reimpresión en espera de autorización</span>
            @endif

            {{-- Botón devolución --}}
            @if($cobro->estado === 'anulado' || $solicitudDevAprobada)
                <span class="btn btn-outline-danger disabled">Cobro anulado</span>
            @elseif($solicitudDevPendiente)
                <span class="btn btn-outline-secondary disabled">Devolución en espera de autorización</span>
            @elseif($cobro->estado === 'pagado')
                <a href="{{ route('cajero.cobros.devolucion.form', $cobro) }}"
                   class="btn btn-outline-danger">
                    Solicitar devolución
                </a>
            @endif
        </div>

        {{-- ① ALERTAS DE ESTADO — AGREGADO --}}
        @if(session('mensaje'))
            <div class="alert alert-info text-center">{{ session('mensaje') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div id="comprobante-imprimir" class="comprobante-ticket">
    <div class="text-center">
        <div class="comprobante-titulo">UPDS</div>
        <div class="comprobante-subtitulo">COMPROBANTE DE PAGO</div>

        {{-- ② LEYENDA ANULADO en el ticket — AGREGADO --}}
        @if($cobro->comprobante->anulado)
            <div style="font-weight:bold; border:1px solid #000; padding:2px 4px; margin-top:4px;">
                *** ANULADO — NO VÁLIDO ***
            </div>
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