@extends('layouts.app')

@section('titulo', 'Nuevo usuario')
@section('contenido')
<h3 class="mb-3">Nuevo usuario</h3>
<div class="card bg-white">
    <div class="card-body">
        <form method="POST" action="{{ route('superadmin.usuarios.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">CI</label>
                    <input type="text" name="ci" class="form-control" value="{{ old('ci') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Apellido paterno</label>
                    <input type="text" name="ap_paterno" class="form-control" value="{{ old('ap_paterno') }}" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Apellido materno</label>
                    <input type="text" name="ap_materno" class="form-control" value="{{ old('ap_materno') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Rol</label>
                    <select name="rol_id" class="form-select" required>
                        <option value="">Selecciona un rol</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->id }}" @selected(old('rol_id') == $rol->id)>{{ $rol->tipo }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('superadmin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </form>
    </div>
</div>
@endsection
