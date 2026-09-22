<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    public function mostrarLogin()
    {
        return view('auth.login');
    }
    public function iniciarSesion(Request $request)
    {
        $credenciales = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            return back()->withErrors([
                'username' => 'Usuario o contraseña incorrectos.',
            ])->onlyInput('username');
        }

        $usuario = Auth::user();
        if ($usuario->estado !== 'activo') {
            Auth::logout();
            return back()->withErrors([
                'username' => 'Tu usuario está inactivo. Contacta a un administrador.',
            ])->onlyInput('username');
        }
        $request->session()->regenerate();
        return $this->redirigirSegunRol($usuario);
    }
    public function cerrarSesion(Request $request)
    {
        $usuario = $request->user();
        if ($usuario->esCajero() && $usuario->arqueoAbierto()) {
            return redirect()->route('cajero.caja.cerrar')->with('mensaje', 'Debes cerrar tu caja antes de cerrar sesión.');
        }
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
    private function redirigirSegunRol($usuario)
    {
        return redirect()->route($usuario->rutaInicio());
    }
}
