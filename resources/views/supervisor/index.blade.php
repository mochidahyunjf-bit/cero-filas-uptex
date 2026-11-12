@extends('layouts.app')
@section('titulo', 'Supervisión')

@section('contenido')
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <h1 class="text-xl font-bold">Panel de supervisión</h1>
    @if (auth()->user()->tieneRol('Supervisor'))<a href="{{ route('ventanilla') }}" class="text-sm font-medium text-uptex">Ir a ventanilla →</a>@endif
</div>

<div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
    @foreach ($kpi as $k => $v)
        <div class="rounded-2xl border border-uptex-borde bg-white p-4 shadow-sm">
            <div class="text-2xl font-bold {{ $k === 'No devueltos' && $v ? 'text-uptex-rojo' : 'text-uptex' }}">{{ $v }}</div>
            <div class="text-xs text-uptex-gris">{{ $k }}</div>
        </div>
    @endforeach
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <section class="rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-semibold">Tickets de mantenimiento</h2>
        @forelse ($tickets as $t)
            <div class="mb-2 flex items-center justify-between gap-2 rounded-xl border border-uptex-borde p-3 text-sm">
                <div><b>{{ $t->folio_ticket }}</b> · {{ $t->codigo_inventario }}<div class="text-xs text-uptex-gris">{{ $t->descripcion_falla }} · {{ $t->prioridad }}</div></div>
                <form method="POST" action="{{ route('sup.reparar', $t->id) }}">@csrf<button class="rounded-lg bg-uptex px-3 py-2 text-xs font-semibold text-white">Reparado</button></form>
            </div>
        @empty <p class="text-sm text-uptex-gris">Sin tickets abiertos.</p> @endforelse
    </section>

    <section class="rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-semibold">Sanciones activas</h2>
        @forelse ($sanciones as $s)
            <form method="POST" action="{{ route('sup.levantar', $s->id) }}" class="mb-2 rounded-xl border border-uptex-borde p-3 text-sm">@csrf
                <b>{{ $s->nombre_completo }}</b> ({{ $s->identificador_institucional }})
                <div class="text-xs text-uptex-rojo">{{ str_replace('_', ' ', $s->motivo) }} · {{ $s->fecha_fin ? 'hasta '.\Carbon\Carbon::parse($s->fecha_fin)->format('d/m') : 'indefinida' }}</div>
                <div class="mt-2 flex gap-2"><input name="motivo" required placeholder="Motivo para levantar" class="flex-1 rounded-lg border border-uptex-borde p-2 text-xs">
                <button class="rounded-lg border border-uptex px-3 text-xs font-semibold text-uptex">Levantar</button></div>
            </form>
        @empty <p class="text-sm text-uptex-gris">Sin sanciones.</p> @endforelse
    </section>

    <section class="rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm lg:col-span-2">
        <h2 class="mb-3 font-semibold">Inventario · layout ABC</h2>
        <div class="overflow-x-auto"><table class="w-full min-w-[480px] text-sm">
            <tr class="text-left text-xs text-uptex-gris"><th>ABC</th><th>Artículo</th><th>Ubicación</th><th class="text-right">Disponible</th></tr>
            @foreach ($inventario as $a)
                <tr class="border-t border-uptex-borde"><td class="py-2 font-bold text-uptex">{{ $a->clasificacion_abc }}</td><td>{{ $a->nombre }}</td><td class="text-xs text-uptex-gris">{{ $a->ubicacion_anaquel }}</td><td class="text-right">{{ $a->stock_disponible }}/{{ $a->stock_total }}</td></tr>
            @endforeach
        </table></div>
    </section>

    @if (auth()->user()->tieneRol('Superadministrador'))
    <section class="rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
        <h2 class="mb-1 font-semibold">Carga masiva de usuarios (CSV)</h2>
        <p class="mb-3 text-xs text-uptex-gris">Columnas: <code>matricula,nombre,correo,rol,password</code>. Rol y password opcionales.</p>
        <form method="POST" action="{{ route('sup.importar') }}" enctype="multipart/form-data" class="flex flex-wrap gap-2">@csrf
            <input type="file" name="csv" accept=".csv" required class="flex-1 text-sm">
            <button class="rounded-lg bg-uptex px-4 py-2 text-sm font-semibold text-white">Importar</button>
        </form>
    </section>
    <section class="rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-semibold">Bitácora inmutable</h2>
        <div class="max-h-64 space-y-1 overflow-y-auto text-xs">
            @foreach ($bitacora as $b)<div class="border-b border-uptex-borde py-1"><span class="text-uptex-gris">{{ $b->created_at }}</span> · <b>{{ $b->modulo }}</b> · {{ $b->accion }}</div>@endforeach
        </div>
    </section>
    @endif
</div>
@endsection
