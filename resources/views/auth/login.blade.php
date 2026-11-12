@extends('layouts.app')
@section('titulo', 'Iniciar sesión')

@push('head')
<style>main{max-width:none!important;padding:0!important}</style>
@endpush

@section('contenido')
<div class="grid min-h-screen lg:grid-cols-2">
    <aside class="relative hidden overflow-hidden bg-[#EEF5EF] px-16 py-16 lg:flex lg:flex-col lg:justify-center">
        <div class="absolute -right-16 -top-24 h-80 w-80 rounded-full bg-uptex-lima/15"></div>
        <div class="absolute bottom-16 right-24 h-28 w-28 rotate-12 rounded-3xl bg-uptex-rojo"></div>
        <div class="mb-10 text-5xl font-black leading-none"><span class="text-uptex">UP</span><span class="text-uptex-rojo">T</span><span class="text-uptex-gris">ex</span>
            <div class="mt-2 text-sm font-semibold tracking-widest text-uptex-texto">UNIVERSIDAD POLITÉCNICA DE TEXCOCO</div></div>
        <div class="mb-6 h-1 w-12 rounded bg-uptex-lima"></div>
        <p class="mb-3 text-xs font-semibold tracking-[0.2em] text-uptex">PLATAFORMA INTEGRAL DE LABORATORIOS</p>
        <h1 class="text-6xl font-bold leading-[1.05] tracking-tight text-uptex-dark">Menos filas.<br>Más práctica.</h1>
        <p class="mt-6 max-w-md text-lg text-uptex-gris">Reserva laboratorios y solicita material académico desde un solo lugar.</p>
        <div class="mt-10 flex items-center gap-4"><span class="text-3xl font-bold text-uptex-rojo">30 s</span><span class="border-l border-uptex-borde pl-4 text-xs text-uptex-gris">Meta de entrega<br>en ventanilla</span></div>
    </aside>

    <div class="flex items-center justify-center bg-white px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 text-3xl font-black lg:hidden"><span class="text-uptex">UP</span><span class="text-uptex-rojo">T</span><span class="text-uptex-gris">ex</span></div>
            <p class="text-xs font-semibold tracking-[0.15em] text-uptex">ACCESO INSTITUCIONAL</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight">Bienvenido de vuelta</h2>
            <p class="mb-6 mt-2 text-uptex-gris">Ingresa con tus datos de la comunidad UPTEX.</p>


            <form id="f" method="POST" action="{{ url('/login') }}" class="space-y-4">@csrf
                <div><label class="mb-1 block text-sm font-medium">Correo o identificador institucional</label>
                    <input name="usuario" value="{{ old('usuario') }}" placeholder="alumno@uptex.edu.mx" class="w-full rounded-xl border border-uptex-borde px-4 py-3 focus:border-uptex focus:outline-none"></div>
                <div><label class="mb-1 block text-sm font-medium">Contraseña</label>
                    <input type="password" name="password" class="w-full rounded-xl border border-uptex-borde px-4 py-3 focus:border-uptex focus:outline-none"></div>
                @if ($accesoRapido)
                <div><label class="mb-1 block text-sm font-medium">Vista del prototipo</label>
                    <select id="vista" class="w-full rounded-xl border border-uptex-borde px-4 py-3">
                        <option value="">Usar mis credenciales</option>
                        @foreach ($accesoRapido as $r)<option value="{{ route('acceso.rapido', $r) }}">{{ $r }}</option>@endforeach
                    </select></div>
                @endif
                <button class="w-full rounded-xl bg-uptex py-3.5 font-semibold text-white shadow-lg shadow-uptex/20 hover:bg-uptex-dark">Ingresar a la plataforma →</button>
                <p class="text-center text-xs text-uptex-gris">✓ Acceso seguro. Tus datos se protegen conforme a la LFPDPPP.</p>
            </form>
            <p class="mt-10 text-center text-xs text-uptex-gris">¿Necesitas ayuda? <span class="font-semibold text-uptex">soporte.laboratorios@uptex.edu.mx</span></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    f.onsubmit = () => { const v = document.getElementById('vista'); if (v && v.value) f.action = v.value; };
</script>
@endpush
