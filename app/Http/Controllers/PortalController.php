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
