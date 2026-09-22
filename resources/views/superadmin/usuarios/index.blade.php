@extends('layouts.app')

@section('titulo', 'Gestión de usuarios')

@section('contenido')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Gestion de usuario</h3>
    <a href="{{ route('superadmin.usuarios.create') }}" class="btn btn-primary">Agregar usuario</a>
</div>

<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>CI</th>
            <th>Nombre</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Estado</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse($usuarios as $usuario)
            <tr>
                <td>{{ $usuario->persona_ci }}</td>
                <td>{{ $usuario->persona->nombreCompleto() }}</td>
                <td>{{ $usuario->username }}</td>
                <td>{{ $usuario->rol->tipo }}</td>
                <td>
                    <span class="badge {{ $usuario->estado === 'activo' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $usuario->estado }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('superadmin.usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center">Hubo algun error</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
