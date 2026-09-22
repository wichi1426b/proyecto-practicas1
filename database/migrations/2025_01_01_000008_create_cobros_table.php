<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('estudiante_id')->constrained('estudiantes');
            $table->foreignId('arqueo_caja_id')->constrained('arqueos_caja');
            $table->decimal('monto_total', 10, 2);
            $table->enum('tipo_pago', ['efectivo', 'tarjeta', 'transferencia'])->default('efectivo');
            $table->dateTime('fecha_pago');
            $table->enum('estado', ['pagado', 'anulado'])->default('pagado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobros');
    }
};
