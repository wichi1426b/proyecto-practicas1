<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudDevolucion extends Model
{
    protected $table = 'solicitudes_devolucion';

    protected $fillable = [
        'cobro_id',
        'arqueo_caja_id',
        'numero_comprobante',
        'fecha_entrega',
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
        'fecha_entrega' => 'datetime',
    ];

    public function cobro()
    {
        return $this->belongsTo(Cobro::class, 'cobro_id');
    }

    public function arqueoCaja()
    {
        return $this->belongsTo(ArqueoCaja::class, 'arqueo_caja_id');
    }

    public function fueEntregada(): bool
    {
        return $this->fecha_entrega !== null;
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
