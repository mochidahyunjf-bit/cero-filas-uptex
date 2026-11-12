@extends('layouts.app')
@section('titulo', 'Ventanilla')

@section('contenido')
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <h1 class="text-xl font-bold">Terminal de ventanilla</h1>
    <div class="flex flex-wrap gap-2">
        @if (auth()->user()->tieneRol('Supervisor'))<a href="{{ route('supervisor') }}" class="rounded-lg bg-uptex px-3 py-2 text-sm font-medium text-white">← Panel de supervisión</a>@endif
        <a href="{{ route('ventanilla.padron') }}" class="rounded-lg border border-uptex px-3 py-2 text-sm font-medium text-uptex">⬇ Padrón offline (CSV)</a>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-2">
    <section class="rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-semibold text-uptex">1 · Carril de entrega</h2>
        <form method="POST" action="{{ route('ventanilla.entregar') }}" class="space-y-3">@csrf
            <label class="block text-sm font-medium">QR del usuario (o matrícula sin celular)</label>
            <input id="qr" name="qr" autofocus class="w-full rounded-xl border border-uptex-borde p-3" placeholder="Escanea el QR…">
            <div class="rounded-xl bg-uptex-fondo p-3 text-xs">
                <b>Simulador de lector</b>
                <select onchange="qr.value=this.value" class="mt-1 w-full rounded-lg border border-uptex-borde p-2">
                    <option value="">— QR generados (vigentes) —</option>
                    @foreach ($qrs as $q)<option value="{{ $q->qr_token }}">{{ $q->qr_token }} — {{ $q->nombre_completo }}</option>@endforeach
                    <optgroup label="Contingencia: matrícula">@foreach ($usuarios as $x)<option value="{{ $x->identificador_institucional }}">{{ $x->identificador_institucional }} — {{ $x->nombre_completo }}</option>@endforeach</optgroup>
                </select>
            </div>
            <label class="block text-sm font-medium">Kit (guía de lo que hay que escanear)</label>
            <select onchange="kit.textContent=this.value" class="w-full rounded-lg border border-uptex-borde p-2 text-sm">
                <option value="">Material suelto</option>
                @foreach ($kits as $k)<option value="{{ $k->items->map(fn ($i) => $i->cantidad_sugerida.'× '.$i->nombre)->join(' · ') }}">{{ $k->codigo_kit }} — {{ $k->nombre_plantilla }}</option>@endforeach
            </select>
            <p id="kit" class="text-xs font-medium text-uptex"></p>
            <label class="block text-sm font-medium">Unidades escaneadas</label>
            <div class="flex gap-2">
                <input id="scan" class="min-w-0 flex-1 rounded-xl border border-uptex-borde p-3" placeholder="Escanea la etiqueta y Enter"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();agregarCodigo(this.value);this.value=''}">
                <button type="button" onclick="agregarCodigo(scan.value);scan.value=''" class="rounded-xl bg-uptex-lima px-4 font-semibold text-white">Agregar</button>
            </div>
            <select onchange="agregarCodigo(this.value);this.value=''" class="w-full rounded-lg border border-uptex-borde p-2 text-xs">
                <option value="">Simulador: elegir unidad disponible…</option>
                @foreach ($disponibles as $d)<option value="{{ $d->codigo_inventario }}">{{ $d->codigo_inventario }} — {{ $d->nombre }} ({{ $d->ubicacion_anaquel }})</option>@endforeach
            </select>
            <textarea id="codigos" name="codigos" rows="3" class="w-full rounded-xl border border-uptex-borde p-2 font-mono text-xs" placeholder="UPTEX-HDMI-003"></textarea>
            <button class="w-full rounded-xl bg-uptex py-3 font-semibold text-white">Confirmar entrega</button>
        </form>
    </section>

    <section class="rounded-2xl border border-uptex-borde bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-semibold text-uptex">2 · Carril express de devolución</h2>
        <form method="POST" action="{{ route('ventanilla.devolver') }}" class="space-y-3">@csrf
            <label class="block text-sm font-medium">QR del alumno, matrícula o folio</label>
            <input id="quien" class="w-full rounded-xl border border-uptex-borde p-3" placeholder="Escanea el QR del alumno y Enter"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();elegirPrestamo(this.value)}" onchange="elegirPrestamo(this.value)">
            <div class="rounded-xl bg-uptex-fondo p-3 text-xs">
                <b>Simulador de lector</b>
                <select id="simPrestamo" onchange="quien.value=this.value;elegirPrestamo(this.value)" class="mt-1 w-full rounded-lg border border-uptex-borde p-2"></select>
            </div>
            <input type="hidden" name="prestamo_id" id="prestamoId">
            <div id="pendientes" class="hidden rounded-xl border border-uptex-lima bg-uptex-lima/10 p-3 text-xs"></div>

            <label class="block text-sm font-medium">Unidad devuelta</label>
            <input id="dev" name="codigo" required class="w-full rounded-xl border border-uptex-borde p-3" placeholder="Escanea la etiqueta de la unidad">
            <select id="simUnidad" onchange="dev.value=this.value" class="w-full rounded-lg border border-uptex-borde p-2 text-xs">
                <option value="">Simulador: primero identifica al alumno…</option>
            </select>
            <input name="nota" class="w-full rounded-lg border border-uptex-borde p-2 text-sm" placeholder="Nota de falla (opcional)">
            <div class="grid grid-cols-2 gap-2">
                <button name="estado" value="Funcional" class="rounded-xl bg-uptex py-3 font-semibold text-white">✓ Funcional</button>
                <button name="estado" value="Con_Falla" class="rounded-xl bg-uptex-rojo py-3 font-semibold text-white">✕ Con falla</button>
            </div>
        </form>

        <h3 class="mb-2 mt-6 text-sm font-semibold">Préstamos abiertos <span class="text-xs font-normal text-uptex-gris">(se actualiza cada 3 s)</span></h3>
        <div id="abiertos" class="space-y-2 text-xs"></div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    function agregarCodigo(c) { c = (c || "").trim().toUpperCase(); if (c && !codigos.value.split('\n').includes(c)) codigos.value = (codigos.value ? codigos.value + "\n" : "") + c; }
    const pintar = lista => abiertos.innerHTML = lista.map(p => `
        <div class="rounded-lg border p-2 ${p.estado === 'Vencido' ? 'border-uptex-rojo bg-uptex-rojo/10' : 'border-uptex-borde'}">
            <b>${p.folio}</b> · ${p.nombre_completo} <span class="float-right font-semibold ${p.estado === 'Vencido' ? 'text-uptex-rojo' : 'text-uptex'}">${p.estado === 'Vencido' ? '⚠ NO DEVUELTO' : p.estado}</span>
            <div class="text-uptex-gris">${p.items.map(i => i.codigo).join(', ')}</div></div>`).join('') || '<p class="text-uptex-gris">Sin préstamos abiertos.</p>';

    let lista = @json($abiertos);
    const qrs = @json($qrs->pluck('identificador_institucional', 'qr_token'));
    let actual = null;

    function elegirPrestamo(v) {
        v = (v || '').trim();
        const mat = qrs[v] || v;
        actual = lista.find(p => p.folio === v.toUpperCase() || p.identificador_institucional === mat) || null;
        prestamoId.value = actual ? actual.id : '';
        pintarPendientes();
    }
    function pintarPendientes() {
        if (actual) actual = lista.find(p => p.id === actual.id) || null;
        pendientes.classList.toggle('hidden', !actual);
        simUnidad.innerHTML = '<option value="">' + (actual ? 'Simulador: elegir unidad que se llevó…' : 'Simulador: primero identifica al alumno…') + '</option>'
            + (actual ? actual.items.map(i => `<option value="${i.codigo}">${i.codigo} — ${i.nombre}</option>`).join('') : '');
        if (actual) pendientes.innerHTML = `<b>${actual.folio} · ${actual.nombre_completo}</b> (${actual.identificador_institucional})<br>Pendientes por devolver:<ul class="mt-1 list-disc pl-4">`
            + actual.items.map(i => `<li>${i.codigo} — ${i.nombre}</li>`).join('') + '</ul>';
    }
    function refrescar(nueva) {
        lista = nueva; pintar(lista);
        const sel = simPrestamo.value;
        simPrestamo.innerHTML = '<option value="">— Préstamos abiertos —</option>' + lista.map(p => `<option value="${p.folio}">${p.folio} — ${p.nombre_completo} (${p.items.length} pend.)</option>`).join('');
        simPrestamo.value = sel;
        pintarPendientes();
    }
    refrescar(lista);
    @if (old('prestamo_id') || session('prestamo_id'))
        elegirPrestamo((lista.find(p => p.id == {{ (int) (old('prestamo_id') ?: session('prestamo_id')) }}) || {}).folio);
    @endif
    setInterval(() => fetch('{{ route('ventanilla.alertas') }}').then(r => r.json()).then(refrescar).catch(() => {}), 3000);
</script>
@endpush
