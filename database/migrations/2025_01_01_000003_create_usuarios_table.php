<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('persona_ci');
            $table->foreignId('rol_id')->constrained('roles');
            $table->string('username')->unique();
            $table->string('password');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();

            $table->foreign('persona_ci')->references('ci')->on('personas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
