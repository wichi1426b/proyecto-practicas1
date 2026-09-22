@extends('layouts.app')

@section('titulo', 'Editar ítem')

@section('contenido')
<h3 class="mb-3">Editar Item</h3>
<div class="card bg-white">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.items.update', $item) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $item->nombre) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control">{{ old('descripcion', $item->descripcion) }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Monto (Bs.)</label>
                    <input type="number" step="0.01" min="0.01" name="monto" class="form-control" value="{{ old('monto', $item->monto) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select" required>
                        <option value="activo" @selected(old('estado', $item->estado) === 'activo')>activo</option>
                        <option value="inactivo" @selected(old('estado', $item->estado) === 'inactivo')>inactivo</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </form>
    </div>
</div>
@endsection
