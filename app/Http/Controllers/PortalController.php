<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $fecha = $request->input('fecha', now()->toDateString());

        return view('portal.index', [
            'u' => $u,
            'sancion' => DB::table('sanciones')->where('usuario_id', $u->id)->where('estado', 'Activa')->first(),
            'prestamos' => DB::table('prestamos')->where('solicitante_id', $u->id)->latest('id')->limit(10)->get()->map(function ($p) {
                $p->items = DB::table('prestamo_detalles as d')->join('activos_unidades as a', 'a.id', '=', 'd.activo_unidad_id')
                    ->where('prestamo_id', $p->id)->get(['a.codigo_inventario', 'd.estado_devolucion']);
                return $p;
            }),
            'misUnidades' => DB::table('prestamo_detalles as d')->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')
                ->join('activos_unidades as a', 'a.id', '=', 'd.activo_unidad_id')->join('articulos_catalogo as c', 'c.id', '=', 'a.articulo_catalogo_id')
                ->where('p.solicitante_id', $u->id)->where('d.estado_devolucion', 'Pendiente')->get(['a.codigo_inventario', 'c.nombre']),
            'tickets' => DB::table('tickets_mantenimiento as t')->join('activos_unidades as a', 'a.id', '=', 't.activo_unidad_id')
                ->where('t.reportado_por', $u->id)->latest('t.id')->limit(10)->get(['t.folio_ticket', 't.estado', 't.descripcion_falla', 'a.codigo_inventario']),
            'fecha' => $fecha,
            'labs' => DB::table('laboratorios')->get(),
            'bloques' => DB::table('bloques_horarios')->get(),
            'reservas' => DB::table('reservas_laboratorio as r')->join('usuarios as u', 'u.id', '=', 'r.docente_id')
                ->where('fecha', $fecha)->where('estado', '!=', 'Cancelada')->get(['r.*', 'u.nombre_completo']),
        ]);
    }

    public function qr(Request $request)
    {
        $u = $request->user();
        if ($u->estado_cuenta === 'Suspendido') {
            return back()->withErrors(['qr' => 'Cuenta suspendida: no puedes solicitar préstamos.']);
        }
        $u->qr_token = 'UPTX-'.Str::upper(Str::random(10));
        $u->qr_expira_at = now()->addMinutes(5);
        $u->save();

        return back()->with('qr', $u->qr_token);
    }

    public function reportar(Request $request)
    {
        $request->validate(['codigo' => 'required', 'descripcion' => 'required|min:5|max:500']);
        $u = $request->user();
        $unidad = DB::table('activos_unidades')->where('codigo_inventario', strtoupper(trim($request->codigo)))->first();
        if (! $unidad) return back()->withErrors(['codigo' => 'No existe una unidad con ese código.']);
        if (DB::table('tickets_mantenimiento')->where('activo_unidad_id', $unidad->id)->whereIn('estado', ['Abierto', 'En_Diagnostico'])->exists()) {
            return back()->withErrors(['codigo' => 'Esa unidad ya tiene un ticket abierto.']);
        }
        $prestamo = DB::table('prestamo_detalles')->where('activo_unidad_id', $unidad->id)->where('estado_devolucion', 'Pendiente')->value('prestamo_id');
        $folio = sprintf('TCK-2026-%04d', DB::table('tickets_mantenimiento')->max('id') + 1);

        DB::transaction(function () use ($u, $unidad, $prestamo, $folio, $request) {
            DB::table('tickets_mantenimiento')->insert([
                'folio_ticket' => $folio, 'activo_unidad_id' => $unidad->id, 'prestamo_id' => $prestamo, 'reportado_por' => $u->id,
                'origen_reporte' => $u->tieneRol('Docente') ? 'Reporte_Docente' : 'Reporte_Alumno', 'descripcion_falla' => $request->descripcion,
                'prioridad' => 'Media', 'estado' => 'Abierto', 'created_at' => now(), 'updated_at' => now(),
            ]);
            // Si está en anaquel se retira del stock; si está prestada se retira al devolverla
            if ($unidad->estado_fisico === 'Disponible') {
                DB::table('activos_unidades')->where('id', $unidad->id)->update(['estado_fisico' => 'En_Mantenimiento']);
                DB::table('articulos_catalogo')->where('id', $unidad->articulo_catalogo_id)->decrement('stock_disponible');
            }
            DB::table('bitacora_auditoria')->insert(['usuario_id' => $u->id, 'modulo' => 'Tickets', 'accion' => "Reporte de falla $folio", 'entidad_afectada' => 'tickets_mantenimiento']);
        });

        return back()->with('ok', "Ticket $folio levantado. Ya aparece en el panel de supervisión.");
    }

    public function reservar(Request $request)
    {
        abort_unless($request->user()->tieneRol('Docente'), 403);
        $d = $request->validate([
            'laboratorio_id' => 'required|exists:laboratorios,id', 'bloque_id' => 'required|exists:bloques_horarios,id',
            'fecha' => 'required|date|after_or_equal:today', 'asignatura' => 'required|max:100', 'grupo' => 'required|max:20',
        ]);
        $ocupado = DB::table('reservas_laboratorio')->where($request->only('laboratorio_id', 'bloque_id', 'fecha'))
            ->where('estado', '!=', 'Cancelada')->exists();
        if ($ocupado) {
            return back()->withErrors(['lab' => 'Ese laboratorio ya está reservado en ese bloque (empalme).']);
        }
        $folio = sprintf('LAB-2026-%04d', DB::table('reservas_laboratorio')->max('id') + 1);
        DB::table('reservas_laboratorio')->insert($d + ['folio' => $folio, 'docente_id' => $request->user()->id,
            'estado' => 'Programada', 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('ok', "Reserva confirmada automáticamente con folio $folio.");
    }
}
