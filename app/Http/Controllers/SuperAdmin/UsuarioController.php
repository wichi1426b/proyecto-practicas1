<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::with(['persona', 'rol'])->orderBy('username')->get();

        return view('superadmin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = Rol::orderBy('tipo')->get();

        return view('superadmin.usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'ci' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:100'],
            'ap_paterno' => ['required', 'string', 'max:100'],
            'ap_materno' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:usuarios,username'],
            'password' => ['required', 'string', 'min:6'],
            'rol_id' => ['required', 'exists:roles,id'],
        ]);

        DB::transaction(function () use ($datos) {
            $persona = Persona::firstOrCreate(
                ['ci' => $datos['ci']],
                [
                    'nombre' => $datos['nombre'],
                    'ap_paterno' => $datos['ap_paterno'],
                    'ap_materno' => $datos['ap_materno'] ?? null,
                ]
            );

            Usuario::create([
                'persona_ci' => $persona->ci,
                'rol_id' => $datos['rol_id'],
                'username' => $datos['username'],
                'password' => Hash::make($datos['password']),
                'estado' => 'activo',
            ]);
        });

        return redirect()->route('superadmin.usuarios.index')->with('mensaje', 'Usuario creado correctamente.');
    }

    public function edit(Usuario $usuario)
    {
        $usuario->load(['persona', 'rol']);
        $roles = Rol::orderBy('tipo')->get();

        return view('superadmin.usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, Usuario $usuario)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'ap_paterno' => ['required', 'string', 'max:100'],
            'ap_materno' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:usuarios,username,' . $usuario->id],
            'password' => ['nullable', 'string', 'min:6'],
            'rol_id' => ['required', 'exists:roles,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        DB::transaction(function () use ($datos, $usuario) {
            $usuario->persona->update([
                'nombre' => $datos['nombre'],
                'ap_paterno' => $datos['ap_paterno'],
                'ap_materno' => $datos['ap_materno'] ?? null,
            ]);

            $usuario->username = $datos['username'];
            $usuario->rol_id = $datos['rol_id'];
            $usuario->estado = $datos['estado'];

            if (! empty($datos['password'])) {
                $usuario->password = Hash::make($datos['password']);
            }

            $usuario->save();
        });

        return redirect()->route('superadmin.usuarios.index')->with('mensaje', 'Usuario actualizado correctamente.');
    }
}
