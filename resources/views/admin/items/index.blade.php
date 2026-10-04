@extends('layouts.app')

@section('titulo', 'Ítems')

@section('contenido')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Items</h3>
    <a href="{{ route('admin.items.create') }}" class="btn btn-primary">Nuevo Item</a>
</div>
<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Monto (Bs.)</th>
            <th>Carreras</th>
            <th>Estado</th>
            <th>Creado por</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item->nombre }}</td>
                <td>{{ $item->descripcion }}</td>
                <td>{{ number_format($item->monto, 2) }}</td>
                <td>{{ $item->carreras->isEmpty() ? 'Todas' : $item->carreras->pluck('nombre')->implode(', ') }}</td>
                <td>
                    <span class="badge {{ $item->estado === 'activo' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $item->estado }}
                    </span>
                </td>
                <td>{{ $item->creador->persona->nombreCompleto() }}</td>
                <td>
                    <a href="{{ route('admin.items.edit', $item) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center">No existe registro</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
