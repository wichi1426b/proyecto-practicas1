<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    protected $table = 'abonos';

    protected $fillable = [
        'cobro_id',
        'arqueo_caja_id',
        'usuario_id',
        'monto',
        'monto_recibido',
        'cambio',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function cobro()
    {
        return $this->belongsTo(Cobro::class, 'cobro_id');
    }

    public function arqueoCaja()
    {
        return $this->belongsTo(ArqueoCaja::class, 'arqueo_caja_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
