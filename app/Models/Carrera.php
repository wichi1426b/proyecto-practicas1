<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carreras';

    protected $fillable = [
        'nombre',
    ];

    public function items()
    {
        return $this->belongsToMany(Item::class, 'carrera_item')->withTimestamps();
    }

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'carrera_id');
    }
}
