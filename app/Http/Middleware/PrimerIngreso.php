<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrimerIngreso
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario->debe_cambiar_password) {
            return redirect()->route('password.form');
        }
        if (! $usuario->acepto_privacidad) {
            return redirect()->route('consentimiento.form');
        }

        return $next($request);
    }
}
