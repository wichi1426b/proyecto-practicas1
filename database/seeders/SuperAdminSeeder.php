<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $persona = Persona::firstOrCreate(
            ['ci' => '1000001'],
            [
                'nombre' => 'Super',
                'ap_paterno' => 'Admin',
                'ap_materno' => null,
            ]
        );

        $rol = Rol::where('tipo', 'super_admin')->firstOrFail();

        Usuario::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'persona_ci' => $persona->ci,
                'rol_id' => $rol->id,
                'password' => Hash::make('superadmin'),
                'estado' => 'activo',
            ]
        );
    }
}
