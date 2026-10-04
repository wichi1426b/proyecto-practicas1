<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArqueoCaja extends Model
{
    protected $table = 'arqueos_caja';

    protected $fillable = [
        'usuario_id',
        'monto_apertura',
        'fecha_apertura',
        'monto_cierre_sistema',
        'monto_cierre_fisico',
        'diferencia',
        'estado',
        'fecha_cierre',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function cobros()
    {
        return $this->hasMany(Cobro::class, 'arqueo_caja_id');
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class, 'arqueo_caja_id');
    }

    public function devoluciones()
    {
        return $this->hasMany(SolicitudDevolucion::class, 'arqueo_caja_id');
    }

    public function totalRecaudado(): float
    {
        return (float) $this->abonos()->sum('monto');
    }

    public function totalRecibido(): float
    {
        return (float) $this->abonos()->sum('monto_recibido');
    }

    public function totalCambio(): float
    {
        return (float) $this->abonos()->sum('cambio');
    }

    public function totalDevuelto(): float
    {
        return (float) $this->devoluciones()->whereNotNull('fecha_entrega')->sum('monto_devuelto');
    }

    public function montoSistemaCalculado(): float
    {
        return round((float) $this->monto_apertura + $this->totalRecaudado() - $this->totalDevuelto(), 2);
    }
}
