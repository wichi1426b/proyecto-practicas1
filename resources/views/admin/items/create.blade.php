@extends('layouts.app')

@section('titulo', 'Nuevo ítem')

@section('contenido')
<h3 class="mb-3">Nuevo ítem</h3>

<div class="card bg-white">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.items.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control">{{ old('descripcion') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Monto (Bs.)</label>
                <input type="number" step="0.01" min="0.01" name="monto" class="form-control" value="{{ old('monto') }}" required>
            </div>

            @include('admin.items._carreras')

            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </form>
    </div>
</div>
@endsection
