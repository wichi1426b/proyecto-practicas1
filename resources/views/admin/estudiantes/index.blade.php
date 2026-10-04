@extends('layouts.app')

@section('titulo', 'Estudiantes')

@section('contenido')
<h3 class="mb-3">Estudiantes</h3>

@if(session('erroresImportacion'))
    <div class="alert alert-warning">
        <strong>Filas no importadas:</strong>
        <ul class="mb-0 small">
            @foreach(session('erroresImportacion') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card bg-white mb-4">
    <div class="card-header fw-semibold">Cargar estudiantes desde Excel</div>
    <div class="card-body">
        <p class="text-muted small mb-2">
            La primera fila debe contener los encabezados:
            <strong>CI, Nombre 1, Nombre 2, Apellido paterno, Apellido materno, Carrera</strong>.
            Si el CI ya existe se actualizan sus datos; las carreras que no existan se crean automáticamente.
        </p>
        <form method="POST" action="{{ route('admin.estudiantes.importar') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-6">
                <input type="file" name="archivo" class="form-control" accept=".xlsx,.csv" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Importar</button>
            </div>
            <div class="col-md-3">
                <a href="{{ route('admin.estudiantes.plantilla') }}" class="btn btn-outline-secondary w-100">Descargar plantilla</a>
            </div>
        </form>
    </div>
</div>

<form method="GET" action="{{ route('admin.estudiantes.index') }}" class="row g-2 mb-3">
    <div class="col-md-6">
        <input type="text" name="buscar" class="form-control" placeholder="Buscar por CI, nombre o apellido" value="{{ $buscar }}">
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-primary">Buscar</button>
    </div>
</form>

<table class="table table-bordered bg-white">
    <thead>
        <tr>
            <th>CI</th>
            <th>Nombre(s)</th>
            <th>Apellido paterno</th>
            <th>Apellido materno</th>
            <th>Carrera</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($estudiantes as $estudiante)
            <tr>
                <td>{{ $estudiante->persona_ci }}</td>
                <td>{{ $estudiante->persona->nombre }}</td>
                <td>{{ $estudiante->persona->ap_paterno }}</td>
                <td>{{ $estudiante->persona->ap_materno }}</td>
                <td>{{ $estudiante->carrera->nombre }}</td>
                <td><span class="badge {{ $estudiante->estado === 'activo' ? 'bg-success' : 'bg-secondary' }}">{{ $estudiante->estado }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center">No hay estudiantes registrados</td></tr>
        @endforelse
    </tbody>
</table>

{{ $estudiantes->links('pagination::bootstrap-5') }}
@endsection
