<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    // Material no devuelto: después de las 21:00 del día de entrega
    $vencidos = DB::table('prestamos')->whereIn('estado', ['Activo', 'Parcial'])
        ->where(fn ($q) => $q->where('fecha_prestamo', '<', today())->orWhereRaw('? >= "21:00:00"', [now()->format('H:i:s')]))->get();
    foreach ($vencidos as $p) {
        DB::table('prestamos')->where('id', $p->id)->update(['estado' => 'Vencido']);
        $u = DB::table('usuarios')->find($p->solicitante_id);
        Mail::raw("El préstamo {$p->folio} no fue devuelto antes de las 21:00. Acude a ventanilla.", fn ($m) => $m->to($u->correo_institucional)->subject('Material no devuelto'));
    }
    // Escalamiento a supervisión: 24 h desde la entrega
    foreach (DB::table('prestamos')->where('estado', 'Vencido')->where('alerta_24h_enviada', false)->where('fecha_entrega_real', '<=', now()->subDay())->get() as $p) {
        DB::table('prestamos')->where('id', $p->id)->update(['alerta_24h_enviada' => true]);
        Mail::raw("El préstamo {$p->folio} lleva más de 24 h sin devolverse.", fn ($m) => $m->to('coord.laboratorios@uptex.edu.mx')->subject('Alerta 24 h: material no devuelto'));
    }
    // Avisos al docente: 15 min antes y al terminar su bloque
    $reservas = DB::table('reservas_laboratorio as r')->join('bloques_horarios as b', 'b.id', '=', 'r.bloque_id')
        ->join('usuarios as u', 'u.id', '=', 'r.docente_id')->join('laboratorios as l', 'l.id', '=', 'r.laboratorio_id')
        ->where('r.fecha', today())->where('r.estado', '!=', 'Cancelada')->get(['r.*', 'b.hora_fin', 'u.correo_institucional', 'l.nombre as lab']);
    foreach ($reservas as $r) {
        $fin = today()->setTimeFromTimeString($r->hora_fin);
        if (! $r->aviso_15min_enviado && now()->gte($fin->copy()->subMinutes(15)) && now()->lt($fin)) {
            DB::table('reservas_laboratorio')->where('id', $r->id)->update(['aviso_15min_enviado' => true]);
            Mail::raw("Su sesión en {$r->lab} termina en 15 minutos ({$r->hora_fin}).", fn ($m) => $m->to($r->correo_institucional)->subject('Aviso: 15 minutos para terminar'));
        }
        if (! $r->aviso_fin_enviado && now()->gte($fin)) {
            DB::table('reservas_laboratorio')->where('id', $r->id)->update(['aviso_fin_enviado' => true, 'estado' => 'Completada']);
            Mail::raw("Su tiempo en {$r->lab} terminó. Por favor desocupe el laboratorio.", fn ($m) => $m->to($r->correo_institucional)->subject('Fin de sesión de laboratorio'));
        }
    }
})->everyMinute()->name('alertas')->withoutOverlapping();
