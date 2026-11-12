@extends('layouts.app')
@section('titulo', 'Portal')

@section('contenido')
<h1 class="text-xl font-bold">Hola, {{ $u->nombre_completo }}</h1>
<p class="mb-4 text-sm text-uptex-gris">{{ $u->rol->nombre }} · {{ $u->identificador_institucional }}</p>

@if ($sancion)
    <div class="mb-4 rounded-xl border border-uptex-rojo/30 bg-uptex-rojo/10 p-4 text-sm text-uptex-rojo">
        <b>Cuenta suspendida</b> ({{ str_replace('_', ' ', $sancion->motivo) }}). {{ $sancion->fecha_fin ? 'Liberación: '.\Carbon\Carbon::parse($sancion->fecha_fin)->format('d/m/Y') : 'Bloqueo hasta regularizar con el Supervisor.' }}
    </div>
@endif

<div class="grid gap-4 lg:grid-cols-2">
    <section class="rounded-2xl border border-uptex-borde bg-white p-5 text-center shadow-sm">
        <h2 class="mb-1 font-semibold">Mi QR de préstamo</h2>
        <p class="mb-4 text-xs text-uptex-gris">Preséntalo en ventanilla. Caduca en 5 minutos y es de un solo uso.</p>
        @if (session('qr'))
            <div id="qr" class="mx-auto mb-2 w-fit rounded-xl border-4 border-uptex p-2"></div>
            <code class="text-xs text-uptex-gris">{{ session('qr') }}</code>
            <p class="mt-1 text-sm font-semibold text-uptex">Expira en <span id="t">5:00</span></p>
        @endif
        <form method="POST" action="{{ route('portal.qr') }}" class="mt-4">@csrf
            <button class="w-full rounded-xl bg-uptex py-3 font-semibold text-white hover:bg-uptex-dark" @disabled($sancion)>Generar QR dinámico</button>
        </form>
    </section>

    <section class="rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-semibold">Mis préstamos</h2>
        @forelse ($prestamos as $p)
            <div class="mb-2 rounded-xl border border-uptex-borde p-3 text-sm">
                <div class="flex justify-between"><b>{{ $p->folio }}</b>
                    <span class="rounded-full px-2 text-xs {{ in_array($p->estado, ['Vencido']) ? 'bg-uptex-rojo text-white' : 'bg-uptex-lima/20 text-uptex-dark' }}">{{ str_replace('_', ' ', $p->estado) }}</span></div>
                <div class="text-xs text-uptex-gris">{{ $p->items->map(fn ($i) => $i->codigo_inventario.($i->estado_devolucion !== 'Pendiente' ? ' ✓' : ''))->join(', ') }}</div>
            </div>
        @empty <p class="text-sm text-uptex-gris">Sin préstamos.</p> @endforelse
        <p class="mt-2 text-xs text-uptex-gris">Devolución el mismo día, antes de las 21:00 h.</p>
    </section>
</div>

<section class="mt-4 rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
    <h2 class="mb-1 font-semibold">Reportar falla de equipo</h2>
    <p class="mb-3 text-xs text-uptex-gris">Levanta un ticket si una unidad no funciona. Reportarlo a tiempo evita sanciones por daño no reportado.</p>
    <form method="POST" action="{{ route('portal.reportar') }}" class="grid gap-2 sm:grid-cols-3">@csrf
        <div class="sm:col-span-1">
            <input id="codigoFalla" name="codigo" required value="{{ old('codigo') }}" placeholder="Código, ej. UPTEX-HDMI-003" class="w-full rounded-lg border border-uptex-borde p-2 text-sm">
            @if ($misUnidades->isNotEmpty())
                <select onchange="codigoFalla.value=this.value" class="mt-2 w-full rounded-lg border border-uptex-borde p-2 text-xs">
                    <option value="">O elige una unidad que tienes prestada…</option>
                    @foreach ($misUnidades as $m)<option value="{{ $m->codigo_inventario }}">{{ $m->codigo_inventario }} — {{ $m->nombre }}</option>@endforeach
                </select>
            @endif
        </div>
        <textarea name="descripcion" required rows="2" placeholder="¿Qué falla tiene?" class="rounded-lg border border-uptex-borde p-2 text-sm sm:col-span-2">{{ old('descripcion') }}</textarea>
        <button class="rounded-lg bg-uptex-rojo p-2 font-semibold text-white sm:col-span-3">✕ Levantar ticket de falla</button>
    </form>
    @if ($tickets->isNotEmpty())
        <h3 class="mb-2 mt-4 text-sm font-semibold">Mis tickets</h3>
        @foreach ($tickets as $t)
            <div class="mb-2 flex justify-between gap-2 rounded-xl border border-uptex-borde p-3 text-sm">
                <div><b>{{ $t->folio_ticket }}</b> · {{ $t->codigo_inventario }}<div class="text-xs text-uptex-gris">{{ $t->descripcion_falla }}</div></div>
                <span class="h-fit rounded-full px-2 text-xs {{ $t->estado === 'Reparado' ? 'bg-uptex text-white' : 'bg-uptex-rojo/10 text-uptex-rojo' }}">{{ str_replace('_', ' ', $t->estado) }}</span>
            </div>
        @endforeach
    @endif
</section>

@if ($u->tieneRol('Docente'))
<section class="mt-4 rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-semibold">Agenda de laboratorios</h2>
        <form><input type="date" name="fecha" value="{{ $fecha }}" onchange="this.form.submit()" class="rounded-lg border border-uptex-borde px-2 py-1 text-sm"></form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[560px] text-xs">
            <tr><th class="p-1 text-left">Bloque</th>@foreach ($labs as $l)<th class="p-1">{{ $l->nombre }}<div class="font-normal text-uptex-gris">{{ $l->tipo === 'Computo' ? 'Cómputo' : 'Robótica · mesas' }}</div></th>@endforeach</tr>
            @foreach ($bloques as $b)
                <tr><td class="p-1 font-medium">{{ substr($b->hora_inicio, 0, 5) }}–{{ substr($b->hora_fin, 0, 5) }}</td>
                @foreach ($labs as $l)
                    @php $r = $reservas->first(fn ($r) => $r->laboratorio_id == $l->id && $r->bloque_id == $b->id); @endphp
                    <td class="p-1"><div class="rounded-lg p-2 text-center {{ $r ? 'bg-uptex-rojo/10 text-uptex-rojo' : 'bg-uptex/10 text-uptex' }}">{{ $r ? $r->asignatura.' · '.$r->grupo : 'Libre' }}</div></td>
                @endforeach</tr>
            @endforeach
        </table>
    </div>
    <form method="POST" action="{{ route('portal.reservar') }}" class="mt-4 grid gap-2 sm:grid-cols-3">@csrf
        <select name="laboratorio_id" class="rounded-lg border border-uptex-borde p-2">@foreach ($labs as $l)<option value="{{ $l->id }}">{{ $l->nombre }}</option>@endforeach</select>
        <select name="bloque_id" class="rounded-lg border border-uptex-borde p-2">@foreach ($bloques as $b)<option value="{{ $b->id }}">{{ $b->nombre }}</option>@endforeach</select>
        <input type="date" name="fecha" value="{{ $fecha }}" class="rounded-lg border border-uptex-borde p-2">
        <input name="asignatura" placeholder="Asignatura" required class="rounded-lg border border-uptex-borde p-2">
        <input name="grupo" placeholder="Grupo" required class="rounded-lg border border-uptex-borde p-2">
        <button class="rounded-lg bg-uptex p-2 font-semibold text-white">Reservar</button>
    </form>
</section>
@endif
@endsection

@if (session('qr'))
@push('scripts')
<script src="{{ asset('vendor/qrcode.min.js') }}"></script>
<script>
    new QRCode(document.getElementById('qr'), { text: @json(session('qr')), width: 200, height: 200 });
    let s = 300; setInterval(() => { s--; document.getElementById('t').textContent = s > 0 ? Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0') : 'expirado'; }, 1000);
</script>
@endpush
@endif
