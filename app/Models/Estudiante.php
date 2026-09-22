<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'estudiantes';

    protected $fillable = [
        'persona_ci',
        'carrera_id',
        'estado',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_ci', 'ci');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function cobros()
    {
        return $this->hasMany(Cobro::class, 'estudiante_id');
    }
}
