@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
<h3 class="mb-3">Editar usuario</h3>

<div class="card bg-white">
    <div class="card-body">
        <form method="POST" action="{{ route('superadmin.usuarios.update', $usuario) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">CI</label>
                    <input type="text" class="form-control" value="{{ $usuario->persona_ci }}" disabled>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $usuario->persona->nombre) }}" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Apellido paterno</label>
                    <input type="text" name="ap_paterno" class="form-control" value="{{ old('ap_paterno', $usuario->persona->ap_paterno) }}" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Apellido materno</label>
                    <input type="text" name="ap_materno" class="form-control" value="{{ old('ap_materno', $usuario->persona->ap_materno) }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="username" class="form-control" value="{{ old('username', $usuario->username) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="Dejar en blanco para no cambiar">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Rol</label>
                    <select name="rol_id" class="form-select" required>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->id }}" @selected(old('rol_id', $usuario->rol_id) == $rol->id)>{{ $rol->tipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select" required>
                        <option value="activo" @selected(old('estado', $usuario->estado) === 'activo')>activo</option>
                        <option value="inactivo" @selected(old('estado', $usuario->estado) === 'inactivo')>inactivo</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="{{ route('superadmin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </form>
    </div>
</div>
@endsection
