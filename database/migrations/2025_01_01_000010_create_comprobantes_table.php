<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobro_id')->unique()->constrained('cobros');
            $table->string('numero_comprobante')->unique();
            $table->dateTime('fecha_emision');
            $table->boolean('es_reimpresion')->default(false);
            $table->boolean('anulado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
