<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
      
        if (! Schema::hasColumn('solicitudes_reimpresion', 'reimpresion_ejecutada')) {
            Schema::table('solicitudes_reimpresion', function (Blueprint $table) {
                $table->boolean('reimpresion_ejecutada')->default(false)->after('fecha_autorizacion');
            });
        }

        Schema::table('cobros', function (Blueprint $table) {
            $table->decimal('monto_pagado', 10, 2)->default(0)->after('monto_total');
            $table->decimal('saldo_pendiente', 10, 2)->default(0)->after('monto_pagado');
        });

        DB::statement("ALTER TABLE cobros MODIFY estado ENUM('pendiente', 'pagado', 'anulado') NOT NULL DEFAULT 'pagado'");

  
        DB::table('cobros')->update(['monto_pagado' => DB::raw('monto_total'), 'saldo_pendiente' => 0]);

        Schema::create('abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobro_id')->constrained('cobros');
            $table->foreignId('arqueo_caja_id')->constrained('arqueos_caja');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->decimal('monto', 10, 2);
            $table->decimal('monto_recibido', 10, 2);
            $table->decimal('cambio', 10, 2)->default(0);
            $table->dateTime('fecha');
            $table->timestamps();
        });

        $cobros = DB::table('cobros')->get();
        foreach ($cobros as $cobro) {
            DB::table('abonos')->insert([
                'cobro_id' => $cobro->id,
                'arqueo_caja_id' => $cobro->arqueo_caja_id,
                'usuario_id' => $cobro->usuario_id,
                'monto' => $cobro->monto_total,
                'monto_recibido' => $cobro->monto_total,
                'cambio' => 0,
                'fecha' => $cobro->fecha_pago,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Schema::table('solicitudes_devolucion', function (Blueprint $table) {
            $table->foreignId('arqueo_caja_id')->nullable()->after('cobro_id')->constrained('arqueos_caja');
            $table->string('numero_comprobante')->nullable()->unique()->after('monto_devuelto');
            $table->dateTime('fecha_entrega')->nullable()->after('fecha_resolucion');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_devolucion', function (Blueprint $table) {
            $table->dropConstrainedForeignId('arqueo_caja_id');
            $table->dropUnique(['numero_comprobante']);
            $table->dropColumn(['numero_comprobante', 'fecha_entrega']);
        });

        Schema::dropIfExists('abonos');

        DB::table('cobros')->where('estado', 'pendiente')->update(['estado' => 'pagado']);
        DB::statement("ALTER TABLE cobros MODIFY estado ENUM('pagado', 'anulado') NOT NULL DEFAULT 'pagado'");

        Schema::table('cobros', function (Blueprint $table) {
            $table->dropColumn(['monto_pagado', 'saldo_pendiente']);
        });
    }
};
