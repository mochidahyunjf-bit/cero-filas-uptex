<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SupervisorController extends Controller
{
    public function index(Request $request)
    {
        return view('supervisor.index', [
            'kpi' => [
                'Préstamos abiertos' => DB::table('prestamos')->whereIn('estado', ['Activo', 'Parcial'])->count(),
                'No devueltos' => DB::table('prestamos')->where('estado', 'Vencido')->count(),
                'Tickets abiertos' => DB::table('tickets_mantenimiento')->where('estado', 'Abierto')->count(),
                'Sanciones activas' => DB::table('sanciones')->where('estado', 'Activa')->count(),
                'Reservas hoy' => DB::table('reservas_laboratorio')->where('fecha', now()->toDateString())->count(),
                'Unidades disponibles' => DB::table('activos_unidades')->where('estado_fisico', 'Disponible')->count(),
            ],
            'inventario' => DB::table('articulos_catalogo')->orderBy('clasificacion_abc')->get(),
            'tickets' => DB::table('tickets_mantenimiento as t')->join('activos_unidades as a', 'a.id', '=', 't.activo_unidad_id')
                ->where('t.estado', 'Abierto')->get(['t.*', 'a.codigo_inventario']),
            'sanciones' => DB::table('sanciones as s')->join('usuarios as u', 'u.id', '=', 's.usuario_id')
                ->where('s.estado', 'Activa')->get(['s.*', 'u.nombre_completo', 'u.identificador_institucional']),
            'bitacora' => $request->user()->tieneRol('Superadministrador')
                ? DB::table('bitacora_auditoria')->latest('id')->limit(30)->get() : collect(),
        ]);
    }

    public function reparar(Request $request, int $id)
    {
        $t = DB::table('tickets_mantenimiento')->find($id);
        DB::table('tickets_mantenimiento')->where('id', $id)->update(['estado' => 'Reparado', 'atendido_por' => $request->user()->id, 'closed_at' => now()]);
        DB::table('activos_unidades')->where('id', $t->activo_unidad_id)->update(['estado_fisico' => 'Disponible']);
        DB::table('articulos_catalogo')->where('id', DB::table('activos_unidades')->where('id', $t->activo_unidad_id)->value('articulo_catalogo_id'))->increment('stock_disponible');

        return back()->with('ok', "Ticket {$t->folio_ticket} reparado; unidad de vuelta al stock.");
    }

    public function levantar(Request $request, int $id)
    {
        $request->validate(['motivo' => 'required|min:5']);
        $s = DB::table('sanciones')->find($id);
        DB::table('sanciones')->where('id', $id)->update(['estado' => 'Condonada', 'levantada_por' => $request->user()->id, 'updated_at' => now()]);
        DB::table('usuarios')->where('id', $s->usuario_id)->update(['estado_cuenta' => 'Activo']);
        DB::table('bitacora_auditoria')->insert(['usuario_id' => $request->user()->id, 'modulo' => 'Sanciones', 'accion' => 'Sanción levantada: '.$request->motivo, 'entidad_afectada' => 'sanciones', 'entidad_id' => $id]);

        return back()->with('ok', 'Sanción levantada.');
    }

    public function importar(Request $request)
    {
        abort_unless($request->user()->tieneRol('Superadministrador'), 403);
        $request->validate(['csv' => 'required|file']);
        $roles = DB::table('roles')->pluck('id', 'nombre');
        $filas = array_map('str_getcsv', file($request->file('csv')->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        array_shift($filas);
        $creados = 0; $omitidos = 0;
        foreach ($filas as $f) {
            [$id, $nombre, $correo] = array_map('trim', array_pad($f, 3, ''));
            $rol = trim($f[3] ?? '') ?: 'Alumno';
            if (! str_ends_with($correo, '@uptex.edu.mx') || ! isset($roles[$rol])
                || Usuario::where('identificador_institucional', $id)->orWhere('correo_institucional', $correo)->exists()) { $omitidos++; continue; }
            Usuario::create(['rol_id' => $roles[$rol], 'identificador_institucional' => $id, 'nombre_completo' => $nombre, 'correo_institucional' => $correo,
                'password_hash' => Hash::make(trim($f[4] ?? '') ?: Usuario::passwordPorDefecto($nombre, $id)), 'debe_cambiar_password' => true]);
            $creados++;
        }

        return back()->with('ok', "Carga masiva: $creados creados, $omitidos omitidos (duplicados o inválidos).");
    }
}
