@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')
<style>
    body {
        background-color: #000000;
    }
</style>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">

                <h2 class="card-title mb-4 text-center">UPDS</h2>
                <img src="{{ asset('images/descarga.png') }}" class="mx-auto d-block mb-4" alt="Mi logotipo">
                <h4 class="card-title mb-4 text-center">Cobros</h4>
                <form method="POST" action="{{ route('login.intentar') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required autofocus>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Iniciar sesion</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection