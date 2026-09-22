<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'UPDS Cobros')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    @auth
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
            <div class="container">
                <span class="navbar-brand">UPDS Cobros</span>
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav me-auto">
                        @if(auth()->user()->esSuperAdmin())
                            <li class="nav-item"><a class="nav-link" href="{{ route('superadmin.usuarios.index') }}">Usuarios</a></li>
                        @endif
                            @if(auth()->user()->esAdmin())
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.items.index') }}">Items</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.reportes.index') }}">Reporte</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.reimpresiones.index') }}">Reimpresiones</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.devoluciones.index') }}">Devoluciones</a></li>
                            @endif
                            @if(auth()->user()->esCajero())
                                <li class="nav-item"><a class="nav-link" href="{{ route('cajero.cobros.create') }}">Registrar cobro</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('cajero.reimpresion.mis_solicitudes') }}">Mis reimpresiones</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('cajero.devolucion.mis_solicitudes') }}">Mis devoluciones</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('cajero.caja.cerrar') }}">Cerrar caja</a></li>
                            @endif
                    </ul>
                    <span class="navbar-text text-white me-3">{{ auth()->user()->persona->nombreCompleto() }}</span>
                    @if(auth()->user()->esCajero() && auth()->user()->arqueoAbierto())
                        <span class="navbar-text text-white-50 small">Cierra tu caja para poder salir</span>
                    @else
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-light btn-sm" type="submit">Cerrar session</button>
                        </form>
                    @endif
                </div>
            </div>
        </nav>
    @endauth

    <div class="container mb-5">
        @if(session('mensaje'))
            <div class="alert alert-success">{{ session('mensaje') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('contenido')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
