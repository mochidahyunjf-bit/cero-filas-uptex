<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeskController extends Controller
{
    public function index()
    {
        return view('ventanilla.index', [
            'qrs' => Usuario::whereNotNull('qr_token')->where('qr_expira_at', '>', now())->get(),
            'usuarios' => Usuario::whereIn('rol_id', [1, 2])->where('estado_cuenta', 'Activo')->orderBy('identificador_institucional')->get(),
            'disponibles' => DB::table('activos_unidades as a')->join('articulos_catalogo as c', 'c.id', '=', 'a.articulo_catalogo_id')
                ->where('estado_fisico', 'Disponible')->orderBy('codigo_inventario')->get(['a.codigo_inventario', 'c.nombre', 'c.ubicacion_anaquel']),
            'prestados' => DB::table('activos_unidades as a')->join('articulos_catalogo as c', 'c.id', '=', 'a.articulo_catalogo_id')
                ->where('estado_fisico', 'Prestado')->orderBy('codigo_inventario')->get(['a.codigo_inventario', 'c.nombre']),
            'kits' => DB::table('plantillas_material as k')->get()->map(function ($k) {
                $k->items = DB::table('plantilla_detalle as d')->join('articulos_catalogo as c', 'c.id', '=', 'd.articulo_catalogo_id')
                    ->where('plantilla_id', $k->id)->get(['c.nombre', 'd.cantidad_sugerida']);
                return $k;
            }),
            'abiertos' => $this->abiertos(),
        ]);
    }

    public function alertas()
    {
        return response()->json($this->abiertos());
    }

    public function entregar(Request $request)
    {
        $request->validate(['qr' => 'required', 'codigos' => 'required']);
        $u = Usuario::where('qr_token', $request->qr)->where('qr_expira_at', '>', now())->first()
            ?? Usuario::where('identificador_institucional', $request->qr)->first(); // contingencia sin celular
        if (! $u) return back()->withErrors(['qr' => 'QR inválido o expirado.']);
        if ($u->estado_cuenta === 'Suspendido') return back()->withErrors(['qr' => "{$u->nombre_completo} tiene una sanción activa."]);

        $codigos = array_unique(array_filter(array_map('trim', preg_split('/[\s,]+/', $request->codigos))));
        $unidades = DB::table('activos_unidades')->whereIn('codigo_inventario', $codigos)->where('estado_fisico', 'Disponible')->get();
        if ($unidades->count() !== count($codigos)) return back()->withErrors(['codigos' => 'Alguna unidad no existe o no está disponible.']);

        DB::transaction(function () use ($u, $unidades, $request) {
            $id = DB::table('prestamos')->insertGetId([
                'folio' => sprintf('PRE-2026-%04d', DB::table('prestamos')->max('id') + 1), 'solicitante_id' => $u->id,
                'fecha_prestamo' => now()->toDateString(), 'qr_dinamico_token' => $u->qr_token, 'estado' => 'Activo',
                'entregado_por' => $request->user()->id, 'fecha_entrega_real' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($unidades as $a) {
                DB::table('prestamo_detalles')->insert(['prestamo_id' => $id, 'articulo_catalogo_id' => $a->articulo_catalogo_id, 'activo_unidad_id' => $a->id]);
                DB::table('activos_unidades')->where('id', $a->id)->update(['estado_fisico' => 'Prestado']);
                DB::table('articulos_catalogo')->where('id', $a->articulo_catalogo_id)->decrement('stock_disponible');
            }
            $u->update(['qr_token' => null, 'qr_expira_at' => null]); // QR de un solo uso
            $this->log($request, 'Prestamos', 'Entrega en ventanilla', $id);
        });

        return back()->with('ok', "Entregadas {$unidades->count()} unidades a {$u->nombre_completo}.");
    }

    public function devolver(Request $request)
    {
        $request->validate(['codigo' => 'required', 'estado' => 'required|in:Funcional,Con_Falla']);
        $det = DB::table('prestamo_detalles as d')->join('activos_unidades as a', 'a.id', '=', 'd.activo_unidad_id')
            ->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')
            ->where('a.codigo_inventario', strtoupper(trim($request->codigo)))->where('d.estado_devolucion', 'Pendiente')
            ->when($request->prestamo_id, fn ($q) => $q->where('d.prestamo_id', $request->prestamo_id))
            ->first(['d.id', 'd.prestamo_id', 'a.id as unidad_id', 'a.articulo_catalogo_id', 'p.solicitante_id', 'p.folio']);
        if (! $det) return back()->withInput()->withErrors(['codigo' => $request->prestamo_id
            ? 'Esa unidad no salió con este préstamo. Solo se aceptan las unidades registradas en la entrega.'
            : 'Esa unidad no pertenece a ningún préstamo abierto.']);

        DB::transaction(function () use ($det, $request) {
            $falla = $request->estado === 'Con_Falla';
            DB::table('prestamo_detalles')->where('id', $det->id)->update(['estado_devolucion' => $request->estado, 'nota_devolucion' => $request->nota]);
            $ticketPrevio = DB::table('tickets_mantenimiento')->where('activo_unidad_id', $det->unidad_id)->whereIn('estado', ['Abierto', 'En_Diagnostico'])->exists();
            DB::table('activos_unidades')->where('id', $det->unidad_id)->update(['estado_fisico' => ($falla || $ticketPrevio) ? 'En_Mantenimiento' : 'Disponible']);
            if ($ticketPrevio) {
                // El usuario ya reportó la falla: no se duplica el ticket ni vuelve al stock
            } elseif ($falla) {
                DB::table('tickets_mantenimiento')->insert([
                    'folio_ticket' => sprintf('TCK-2026-%04d', DB::table('tickets_mantenimiento')->max('id') + 1), 'activo_unidad_id' => $det->unidad_id,
                    'prestamo_id' => $det->prestamo_id, 'reportado_por' => $request->user()->id, 'origen_reporte' => 'Devolucion_Ventanilla',
                    'descripcion_falla' => $request->nota ?: 'Reportado con falla en devolución', 'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                DB::table('articulos_catalogo')->where('id', $det->articulo_catalogo_id)->increment('stock_disponible');
            }
            $pendientes = DB::table('prestamo_detalles')->where('prestamo_id', $det->prestamo_id)->where('estado_devolucion', 'Pendiente')->count();
            $conFalla = DB::table('prestamo_detalles')->where('prestamo_id', $det->prestamo_id)->where('estado_devolucion', 'Con_Falla')->exists();
            DB::table('prestamos')->where('id', $det->prestamo_id)->update([
                'estado' => $pendientes ? 'Parcial' : ($conFalla ? 'Devuelto_Con_Falla' : 'Devuelto_OK'),
                'recibido_por' => $request->user()->id, 'fecha_devolucion_real' => $pendientes ? null : now(), 'updated_at' => now(),
            ]);
            $this->log($request, 'Prestamos', "Devolución {$request->codigo} ({$request->estado})", $det->prestamo_id);
        });

        return back()->with(['ok' => "Unidad {$request->codigo} recibida ({$request->estado}).", 'prestamo_id' => $det->prestamo_id]);
    }

    public function padron()
    {
        return response()->streamDownload(function () {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['folio', 'matricula', 'nombre', 'unidad', 'estado']);
            foreach ($this->abiertos() as $p) foreach ($p->items as $i) fputcsv($f, [$p->folio, $p->identificador_institucional, $p->nombre_completo, $i->codigo.' '.$i->nombre, $p->estado]);
            fclose($f);
        }, 'padron-offline-'.now()->format('Ymd-Hi').'.csv');
    }

    private function abiertos()
    {
        return DB::table('prestamos as p')->join('usuarios as u', 'u.id', '=', 'p.solicitante_id')
            ->whereIn('p.estado', ['Activo', 'Parcial', 'Vencido'])->orderBy('p.fecha_entrega_real')
            ->get(['p.id', 'p.folio', 'p.estado', 'p.fecha_entrega_real', 'u.nombre_completo', 'u.identificador_institucional'])
            ->map(function ($p) {
                $p->items = DB::table('prestamo_detalles as d')->join('activos_unidades as a', 'a.id', '=', 'd.activo_unidad_id')
                    ->join('articulos_catalogo as c', 'c.id', '=', 'a.articulo_catalogo_id')
                    ->where('prestamo_id', $p->id)->where('estado_devolucion', 'Pendiente')->get(['a.codigo_inventario as codigo', 'c.nombre']);
                return $p;
            });
    }

    private function log(Request $r, string $modulo, string $accion, ?int $id): void
    {
        DB::table('bitacora_auditoria')->insert(['usuario_id' => $r->user()->id, 'modulo' => $modulo, 'accion' => $accion, 'entidad_afectada' => 'prestamos', 'entidad_id' => $id]);
    }
}
