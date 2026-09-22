<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'personas';

    protected $primaryKey = 'ci';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'ci',
        'nombre',
        'ap_paterno',
        'ap_materno',
    ];

    public function usuario()
    {
        return $this->hasOne(Usuario::class, 'persona_ci', 'ci');
    }

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'persona_ci', 'ci');
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre} {$this->ap_paterno} {$this->ap_materno}");
    }
}
