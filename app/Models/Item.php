<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';

    protected $fillable = [
        'creado_por',
        'nombre',
        'descripcion',
        'monto',
        'estado',
    ];

    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    public function detallePagos()
    {
        return $this->hasMany(DetallePago::class, 'item_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}
