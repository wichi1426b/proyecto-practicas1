<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudReimpresion extends Model
{
    protected $table = 'solicitudes_reimpresion';

    protected $fillable = [
        'comprobante_id',
        'cajero_solicitante_id',
        'admin_autoriza_id',
        'motivo',
        'estado',
        'fecha_solicitud',
        'fecha_autorizacion',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_autorizacion' => 'datetime',
    ];

    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_id');
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
