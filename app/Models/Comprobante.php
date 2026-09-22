<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    protected $table = 'comprobantes';

    protected $fillable = [
        'cobro_id',
        'numero_comprobante',
        'fecha_emision',
        'es_reimpresion',
        'anulado',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'es_reimpresion' => 'boolean',
        'anulado' => 'boolean',
    ];

    public function cobro()
    {
        return $this->belongsTo(Cobro::class, 'cobro_id');
    }

    public function solicitudesReimpresion()
    {
        return $this->hasMany(SolicitudReimpresion::class, 'comprobante_id');
    }
}
