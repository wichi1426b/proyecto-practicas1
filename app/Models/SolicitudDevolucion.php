<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudDevolucion extends Model
{
    protected $table = 'solicitudes_devolucion';

    protected $fillable = [
        'cobro_id',
        'cajero_solicitante_id',
        'admin_autoriza_id',
        'motivo',
        'monto_devuelto',
        'estado',
        'fecha_solicitud',
        'fecha_resolucion',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_resolucion' => 'datetime',
    ];

    public function cobro()
    {
        return $this->belongsTo(Cobro::class, 'cobro_id');
    }

    public function cajeroSolicitante()
    {
        return $this->belongsTo(Usuario::class, 'cajero_solicitante_id');
    }

    public function adminAutoriza()
    {
        return $this->belongsTo(Usuario::class, 'admin_autoriza_id');
    }
}
