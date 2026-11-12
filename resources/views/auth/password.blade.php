@extends('layouts.app')
@section('titulo', 'Cambiar contraseña')

@section('contenido')
<div class="mx-auto max-w-md">
    <div class="mb-4">
        <span class="text-xs font-semibold uppercase tracking-wide text-uptex">Primer ingreso · Paso 1 de 2</span>
        <h1 class="text-xl font-bold">Crea tu nueva contraseña</h1>
        <p class="text-sm text-uptex-gris">Por seguridad, la contraseña temporal debe cambiarse antes de continuar.</p>
    </div>
    <form method="POST" action="{{ route('password.guardar') }}" class="space-y-4 rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium" for="password">Nueva contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="w-full rounded-xl border border-uptex-borde px-4 py-3 focus:border-uptex focus:outline-none focus:ring-2 focus:ring-uptex/20">
            <p class="mt-1 text-xs text-uptex-gris">Mínimo 8 caracteres, con letras y números.</p>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="password_confirmation">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="w-full rounded-xl border border-uptex-borde px-4 py-3 focus:border-uptex focus:outline-none focus:ring-2 focus:ring-uptex/20">
        </div>
        <button class="w-full rounded-xl bg-uptex py-3 font-semibold text-white hover:bg-uptex-dark">Guardar y continuar</button>
    </form>
</div>
@endsection
