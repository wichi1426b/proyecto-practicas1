<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_devolucion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobro_id')->constrained('cobros');
            $table->foreignId('cajero_solicitante_id')->constrained('usuarios');
            $table->foreignId('admin_autoriza_id')->nullable()->constrained('usuarios');
            $table->text('motivo');
            $table->decimal('monto_devuelto', 10, 2)->nullable();
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_resolucion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_devolucion');
    }
};
