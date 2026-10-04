@extends('layouts.app')

@section('titulo', 'Copias de seguridad')

@section('contenido')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Copias de seguridad</h3>
    <form method="POST" action="{{ route('superadmin.backups.generar') }}">
        @csrf
        <button type="submit" class="btn btn-primary">Generar copia ahora</button>
    </form>
</div>

<table class="table table-bordered bg-white align-middle">
    <thead>
        <tr>
            <th>Archivo</th>
            <th>Fecha</th>
            <th class="text-end">Tamaño</th>
            <th style="width: 200px;"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($backups as $backup)
            <tr>
                <td>{{ $backup['nombre'] }}</td>
                <td>{{ $backup['fecha']->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</td>
                <td class="text-end">{{ number_format($backup['tamano'] / 1024, 1) }} KB</td>
                <td>
                    <a href="{{ route('superadmin.backups.descargar', $backup['nombre']) }}" class="btn btn-sm btn-outline-primary">Descargar</a>
                    <form method="POST" action="{{ route('superadmin.backups.eliminar', $backup['nombre']) }}" class="d-inline"
                          onsubmit="return confirm('¿Eliminar esta copia de seguridad?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted">Aún no se generaron copias de seguridad</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
