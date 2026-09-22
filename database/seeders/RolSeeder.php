<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'admin', 'cajero'] as $tipo) {
            Rol::firstOrCreate(['tipo' => $tipo]);
        }
    }
}
