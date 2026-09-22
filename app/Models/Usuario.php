<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = [
        'persona_ci',
        'rol_id',
        'username',
        'password',
        'estado',
    ];

    protected $hidden = [
        'password',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_ci', 'ci');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'creado_por');
    }

    public function arqueosCaja()
    {
        return $this->hasMany(ArqueoCaja::class, 'usuario_id');
    }

    public function cobros()
    {
        return $this->hasMany(Cobro::class, 'usuario_id');
    }

    public function esSuperAdmin(): bool
    {
        return $this->rol->tipo === 'super_admin';
    }

    public function esAdmin(): bool
    {
        return $this->rol->tipo === 'admin';
    }

    public function esCajero(): bool
    {
        return $this->rol->tipo === 'cajero';
    }

    public function arqueoAbierto()
    {
        return $this->arqueosCaja()->where('estado', 'abierto')->latest('fecha_apertura')->first();
    }

    public function rutaInicio(): string
    {
        return match ($this->rol->tipo) {
            'super_admin' => 'superadmin.usuarios.index',
            'admin' => 'admin.items.index',
            'cajero' => $this->arqueoAbierto() ? 'cajero.cobros.create' : 'cajero.caja.apertura',
        };
    }
}
