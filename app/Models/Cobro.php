<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SolicitudDevolucion;

class Cobro extends Model
{
    protected $table = 'cobros';

    protected $fillable = [
        'usuario_id',
        'estudiante_id',
        'arqueo_caja_id',
        'monto_total',
        'tipo_pago',
        'fecha_pago',
        'estado',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function arqueoCaja()
    {
        return $this->belongsTo(ArqueoCaja::class, 'arqueo_caja_id');
    }

    public function detallePagos()
    {
        return $this->hasMany(DetallePago::class, 'cobro_id');
    }

    public function comprobante()
    {
        return $this->hasOne(Comprobante::class, 'cobro_id');
    }

    public function solicitudesDevolucion()
{
    return $this->hasMany(SolicitudDevolucion::class, 'cobro_id');
}
}
