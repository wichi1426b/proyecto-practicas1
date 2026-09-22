<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarCajaAbierta
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->arqueoAbierto()) {
            return redirect()->route('cajero.caja.apertura');
        }

        return $next($request);
    }
}
