@extends('layouts.app')
@section('titulo', 'Aviso de privacidad')

@section('contenido')
<div class="mx-auto max-w-2xl">
    <div class="mb-4">
        <span class="text-xs font-semibold uppercase tracking-wide text-uptex">Primer ingreso · Paso 2 de 2</span>
        <h1 class="text-xl font-bold">Privacidad y corresponsabilidad</h1>
    </div>
    <form method="POST" action="{{ route('consentimiento.guardar') }}" class="space-y-4">
        @csrf
        <section class="rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
            <h2 class="mb-2 font-semibold">Aviso de privacidad (LFPDPPP)</h2>
            <div class="max-h-40 space-y-2 overflow-y-auto text-sm text-uptex-gris">
                <p>La Universidad Politécnica de Texcoco trata únicamente tu identificador institucional (matrícula o número de empleado), nombre completo, correo institucional y el registro de este consentimiento.</p>
                <p>Estos datos se usan exclusivamente para gestionar reservas de laboratorio y préstamos de material. No se almacenan fotografías, CURP ni credenciales físicas, y no se comparten con terceros.</p>
                <p>Puedes ejercer tus derechos ARCO ante la Jefatura de TI de UPTEX.</p>
            </div>
            <label class="mt-3 flex items-start gap-3 text-sm">
                <input type="checkbox" name="privacidad" value="1" class="mt-0.5 h-5 w-5 accent-uptex" required>
                <span>He leído y acepto el aviso de privacidad.</span>
            </label>
        </section>
        <section class="rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
            <h2 class="mb-2 font-semibold">Carta de corresponsabilidad</h2>
            <ul class="list-disc space-y-1 pl-5 text-sm text-uptex-gris">
                <li>El material prestado debe devolverse el mismo día, a más tardar a las 21:00 h.</li>
                <li>Cada unidad tiene un código único; soy responsable de las unidades registradas a mi nombre.</li>
                <li>Debo reportar cualquier falla o daño al devolver el material.</li>
                <li>El daño o extravío no reportado puede suspender mi cuenta hasta su regularización.</li>
            </ul>
            <label class="mt-3 flex items-start gap-3 text-sm">
                <input type="checkbox" name="corresponsabilidad" value="1" class="mt-0.5 h-5 w-5 accent-uptex" required>
                <span>Acepto la carta de corresponsabilidad.</span>
            </label>
        </section>
        <button class="w-full rounded-xl bg-uptex py-3 font-semibold text-white hover:bg-uptex-dark">Aceptar y continuar</button>
    </form>
</div>
@endsection
