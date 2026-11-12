<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private array $rolId = [];

    public function run(): void
    {
        $ahora = now();
        $hoy = $ahora->toDateString();

        // Roles
        foreach ([
            'Alumno' => 'Estudiante con matrícula y correo institucional',
            'Docente' => 'Profesor que reserva laboratorios y solicita material',
            'Administrador' => 'Técnico de ventanilla (encargado de préstamos)',
            'Supervisor' => 'Coordinación de laboratorios e inventario',
            'Superadministrador' => 'Jefatura TI / servidor Raven',
        ] as $nombre => $desc) {
            $this->rolId[$nombre] = DB::table('roles')->insertGetId(['nombre' => $nombre, 'descripcion' => $desc]);
        }

        // Usuarios
        $nombres = ['Jonathan', 'María', 'José', 'Fernanda', 'Luis', 'Daniela', 'Carlos', 'Valeria', 'Miguel', 'Ximena',
            'Jorge', 'Andrea', 'Diego', 'Paola', 'Ricardo', 'Sofía', 'Alejandro', 'Camila', 'Eduardo', 'Mariana',
            'Héctor', 'Regina', 'Iván', 'Lucía', 'Omar'];
        $apellidos = ['Fuentes De La Rosa', 'Hernández López', 'García Martínez', 'Ramírez Cruz', 'Sánchez Flores',
            'Torres Morales', 'Rivera Gómez', 'Jiménez Ruiz', 'Mendoza Ortiz', 'Castillo Vargas', 'Romero Silva',
            'Contreras Reyes', 'Aguilar Medina', 'Vázquez Castro', 'Domínguez Soto', 'Guerrero Peña', 'Navarro Ríos'];

        $alumnos = [];
        for ($i = 1; $i <= 50; $i++) {
            $matricula = (string) (231650000 + $i);
            $nombre = $i === 1 ? 'Jonathan Fuentes De La Rosa'
                : $nombres[($i * 7) % count($nombres)].' '.$apellidos[($i * 5) % count($apellidos)];
            $alumnos[$i] = $this->usuario('Alumno', $matricula, $nombre, $matricula.'@uptex.edu.mx', [
                // 231650002 queda como "primer ingreso" para demostrar cambio de contraseña y consentimiento
                'nuevo' => $i === 2,
                'estado' => in_array($i, [49, 50]) ? 'Suspendido' : 'Activo',
            ]);
        }

        $docentes = [];
        foreach ([
            'Ing. Roberto Salinas Pérez', 'Mtra. Claudia Ibarra Luna', 'Dra. Patricia Ochoa Durán', 'Ing. Arturo Benítez Mora',
            'Mtro. Fernando Cázares Gil', 'Dra. Gabriela Montes Rojas', 'Ing. Sergio Valdez Ponce', 'Mtra. Elena Quiroz Lara',
            'Mtro. Raúl Espinoza Núñez', 'Ing. Laura Trejo Campos',
        ] as $k => $nombre) {
            $id = 'DOC-'.str_pad($k + 1, 3, '0', STR_PAD_LEFT);
            $partes = explode(' ', $nombre);
            $correo = strtolower(\Illuminate\Support\Str::ascii($partes[1].'.'.$partes[2])).'@uptex.edu.mx';
            $docentes[$k + 1] = $this->usuario('Docente', $id, $nombre, $correo);
        }

        $adm1 = $this->usuario('Administrador', 'ADM-01', 'Técnico Matutino - Pedro Alvarado', 'ventanilla.matutino@uptex.edu.mx');
        $this->usuario('Administrador', 'ADM-02', 'Técnico Vespertino - Rosa Elizondo', 'ventanilla.vespertino@uptex.edu.mx');
        $sup1 = $this->usuario('Supervisor', 'SUP-01', 'Coord. Laboratorios - Martha Solís', 'coord.laboratorios@uptex.edu.mx');
        $this->usuario('Supervisor', 'SUP-02', 'Control de Inventario - Javier Ugalde', 'control.inventario@uptex.edu.mx');
        $this->usuario('Superadministrador', 'SADM-01', 'Jefatura TI - Servidor Raven', 'ti@uptex.edu.mx');

        // Sanciones activas de ejemplo
        DB::table('sanciones')->insert([
            ['usuario_id' => $alumnos[49], 'prestamo_id' => null, 'motivo' => 'Devolucion_Tardia',
                'descripcion' => 'Devolución 25 min después del cierre del bloque.', 'fecha_inicio' => $ahora->copy()->subDays(2),
                'fecha_fin' => $ahora->copy()->addDays(5), 'estado' => 'Activa', 'levantada_por' => null, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['usuario_id' => $alumnos[50], 'prestamo_id' => null, 'motivo' => 'Dano_No_Reportado',
                'descripcion' => 'Multímetro devuelto con pantalla rota sin reporte previo.', 'fecha_inicio' => $ahora->copy()->subDay(),
                'fecha_fin' => null, 'estado' => 'Activa', 'levantada_por' => null, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);

        // Laboratorios y bloques
        $labs = [];
        foreach ([
            ['LAB-SIS-01', 'Laboratorio de Sistemas 1', 'Computo', 30, 'Edificio A, planta baja'],
            ['LAB-SIS-02', 'Laboratorio de Sistemas 2', 'Computo', 30, 'Edificio A, primer piso'],
            ['LAB-ROB-01', 'Laboratorio de Robótica y Electrónica 1', 'Robotica', 24, 'Edificio B, planta baja'],
            ['LAB-ROB-02', 'Laboratorio de Robótica y Electrónica 2', 'Robotica', 24, 'Edificio B, primer piso'],
        ] as [$codigo, $nombre, $tipo, $cap, $ubic]) {
            $labs[$codigo] = DB::table('laboratorios')->insertGetId([
                'codigo' => $codigo, 'nombre' => $nombre, 'tipo' => $tipo,
                'capacidad_alumnos' => $cap, 'ubicacion' => $ubic, 'estado' => 'Disponible',
            ]);
        }

        $bloques = [];
        foreach (range(0, 6) as $b) {
            $ini = sprintf('%02d:00', 7 + $b * 2);
            $fin = sprintf('%02d:00', 9 + $b * 2);
            $bloques[$b + 1] = DB::table('bloques_horarios')->insertGetId([
                'nombre' => 'Módulo '.($b + 1)." ($ini - $fin)", 'hora_inicio' => $ini, 'hora_fin' => $fin,
            ]);
        }

        foreach ([
            ['LAB-SIS-01', 1, 2, 'Programación Web', 'ISC-5A'],
            ['LAB-SIS-02', 2, 3, 'Redes de Computadoras', 'ISC-7B'],
            ['LAB-ROB-01', 3, 2, 'Electrónica Digital', 'IRO-3A'],
            ['LAB-ROB-02', 4, 4, 'Sistemas Embebidos', 'IRO-6A'],
        ] as $n => [$lab, $doc, $bloque, $asig, $grupo]) {
            DB::table('reservas_laboratorio')->insert([
                'folio' => sprintf('LAB-2026-%04d', $n + 1), 'laboratorio_id' => $labs[$lab], 'docente_id' => $docentes[$doc],
                'fecha' => $hoy, 'bloque_id' => $bloques[$bloque], 'asignatura' => $asig, 'grupo' => $grupo,
                'estado' => 'Programada', 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        // Catálogo e inventario unitario (110 unidades)
        $cat = [
            'Cables' => DB::table('categorias_material')->insertGetId(['nombre' => 'Cables y conectividad', 'area_pertenencia' => 'Sistemas']),
            'Perifericos' => DB::table('categorias_material')->insertGetId(['nombre' => 'Periféricos', 'area_pertenencia' => 'Sistemas']),
            'Presentacion' => DB::table('categorias_material')->insertGetId(['nombre' => 'Presentación', 'area_pertenencia' => 'General']),
            'Instrumentacion' => DB::table('categorias_material')->insertGetId(['nombre' => 'Instrumentación y soldadura', 'area_pertenencia' => 'Electronica']),
        ];
        $articulos = [];
        foreach ([
            ['HDMI', 'Cable HDMI 2.0', 'Cables', 'A', 'Zona A1 - Ventanilla Sistemas', 20],
            ['ETH', 'Cable Ethernet Cat6', 'Cables', 'A', 'Zona A1 - Ventanilla Sistemas', 25],
            ['MOU', 'Mouse óptico USB', 'Perifericos', 'A', 'Zona A1 - Ventanilla Sistemas', 15],
            ['TEC', 'Teclado USB', 'Perifericos', 'A', 'Zona A1 - Ventanilla Sistemas', 15],
            ['AC', 'Cable de corriente AC', 'Cables', 'A', 'Zona A1 - Ventanilla Sistemas', 10],
            ['PRES', 'Presentador de diapositivas', 'Presentacion', 'B', 'Zona A1 - Ventanilla Sistemas', 4],
            ['PROY', 'Cañón / Proyector', 'Presentacion', 'B', 'Zona B1 - Anaquel medio', 4],
            ['FUE', 'Fuente de poder DC', 'Instrumentacion', 'A', 'Zona A2 - Ventanilla Electrónica', 6],
            ['CAU', 'Cautín de estación', 'Instrumentacion', 'A', 'Zona A2 - Ventanilla Electrónica', 6],
            ['MUL', 'Multímetro digital', 'Instrumentacion', 'A', 'Zona A2 - Ventanilla Electrónica', 5],
        ] as [$codigo, $nombre, $categoria, $abc, $ubic, $total]) {
            $articulos[$codigo] = DB::table('articulos_catalogo')->insertGetId([
                'categoria_id' => $cat[$categoria], 'codigo_tipo' => $codigo, 'nombre' => $nombre, 'clasificacion_abc' => $abc,
                'ubicacion_anaquel' => $ubic, 'stock_total' => $total, 'stock_disponible' => $total, 'activo' => true,
            ]);
            $unidades = [];
            for ($u = 1; $u <= $total; $u++) {
                $inv = sprintf('UPTEX-%s-%03d', $codigo, $u);
                $unidades[] = [
                    'articulo_catalogo_id' => $articulos[$codigo], 'codigo_inventario' => $inv,
                    'qr_fisico_hash' => substr(sha1($inv), 0, 20), 'numero_serie' => null, 'estado_fisico' => 'Disponible',
                ];
            }
            DB::table('activos_unidades')->insert($unidades);
        }
        $unidad = fn (string $inv) => DB::table('activos_unidades')->where('codigo_inventario', $inv)->value('id');

        // Kits predefinidos
        foreach ([
            ['KIT-PRES-01', 'Kit Presentación Básico', 'Para exposiciones en aula', ['HDMI' => 2, 'AC' => 1]],
            ['KIT-PRES-02', 'Kit Presentación Completo', 'Proyección completa', ['PROY' => 1, 'PRES' => 1, 'HDMI' => 1, 'AC' => 1]],
            ['KIT-PERI-01', 'Kit Periféricos Estación PC', 'Teclado y mouse', ['TEC' => 1, 'MOU' => 1]],
            ['KIT-RED-01', 'Kit Práctica de Redes', 'Cableado de práctica', ['ETH' => 4]],
            ['KIT-ELEC-01', 'Kit Banco de Electrónica', 'Mesa de trabajo de robótica', ['FUE' => 1, 'MUL' => 1, 'CAU' => 1]],
            ['KIT-MED-01', 'Kit Diagnóstico y Medición', 'Medición de circuitos', ['MUL' => 1, 'FUE' => 1]],
        ] as [$codigo, $nombre, $desc, $items]) {
            $kit = DB::table('plantillas_material')->insertGetId([
                'codigo_kit' => $codigo, 'creado_por' => $sup1, 'nombre_plantilla' => $nombre, 'descripcion' => $desc, 'activa' => true,
            ]);
            foreach ($items as $art => $cant) {
                DB::table('plantilla_detalle')->insert([
                    'plantilla_id' => $kit, 'articulo_catalogo_id' => $articulos[$art], 'cantidad_sugerida' => $cant,
                ]);
            }
        }

        // Préstamos activos para demostrar el carril de devolución (el último es de ayer: alerta de no devuelto)
        foreach ([
            [3, ['UPTEX-HDMI-001', 'UPTEX-HDMI-002', 'UPTEX-AC-001'], 0],
            [4, ['UPTEX-TEC-001', 'UPTEX-MOU-001'], 0],
            [5, ['UPTEX-FUE-001', 'UPTEX-MUL-001', 'UPTEX-CAU-001'], 0],
            [6, ['UPTEX-ETH-001', 'UPTEX-ETH-002', 'UPTEX-ETH-003', 'UPTEX-ETH-004'], 0],
            [7, ['UPTEX-PROY-001', 'UPTEX-PRES-001'], 1],
        ] as $n => [$alumno, $codigos, $diasAtras]) {
            $entrega = $ahora->copy()->subDays($diasAtras)->setTime(9 + $n, 5);
            $prestamo = DB::table('prestamos')->insertGetId([
                'folio' => sprintf('PRE-2026-%04d', $n + 1), 'solicitante_id' => $alumnos[$alumno], 'fecha_prestamo' => $entrega->toDateString(),
                'tipo_solicitud' => 'Express_Ventanilla', 'estado' => 'Activo', 'entregado_por' => $adm1,
                'fecha_entrega_real' => $entrega, 'created_at' => $entrega, 'updated_at' => $entrega,
            ]);
            foreach ($codigos as $inv) {
                $id = $unidad($inv);
                DB::table('prestamo_detalles')->insert([
                    'prestamo_id' => $prestamo, 'activo_unidad_id' => $id,
                    'articulo_catalogo_id' => DB::table('activos_unidades')->where('id', $id)->value('articulo_catalogo_id'),
                ]);
                DB::table('activos_unidades')->where('id', $id)->update(['estado_fisico' => 'Prestado']);
            }
        }

        // Tickets abiertos (3 unidades en mantenimiento)
        foreach ([
            ['UPTEX-HDMI-020', 'Conector doblado, sin señal de video.', 'Media'],
            ['UPTEX-MOU-015', 'Sensor óptico no responde.', 'Baja'],
            ['UPTEX-MUL-005', 'Pantalla con lecturas erráticas.', 'Alta'],
        ] as $n => [$inv, $falla, $prioridad]) {
            $id = $unidad($inv);
            DB::table('activos_unidades')->where('id', $id)->update(['estado_fisico' => 'En_Mantenimiento']);
            DB::table('tickets_mantenimiento')->insert([
                'folio_ticket' => sprintf('TCK-2026-%04d', $n + 1), 'activo_unidad_id' => $id, 'reportado_por' => $adm1,
                'origen_reporte' => 'Devolucion_Ventanilla', 'descripcion_falla' => $falla, 'prioridad' => $prioridad,
                'estado' => 'Abierto', 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        // Stock disponible real
        foreach ($articulos as $id) {
            DB::table('articulos_catalogo')->where('id', $id)->update([
                'stock_disponible' => DB::table('activos_unidades')->where('articulo_catalogo_id', $id)->where('estado_fisico', 'Disponible')->count(),
            ]);
        }

        DB::table('bitacora_auditoria')->insert([
            'usuario_id' => null, 'modulo' => 'Inventario', 'accion' => 'Carga inicial de demo',
            'entidad_afectada' => 'sistema', 'datos_nuevos' => json_encode(['unidades' => 110, 'usuarios' => 65]),
        ]);
    }

    private function usuario(string $rol, string $id, string $nombre, string $correo, array $opc = []): int
    {
        $nuevo = $opc['nuevo'] ?? false;

        return DB::table('usuarios')->insertGetId([
            'rol_id' => $this->rolId[$rol],
            'identificador_institucional' => $id,
            'nombre_completo' => $nombre,
            'correo_institucional' => $correo,
            'password_hash' => Hash::make(Usuario::passwordPorDefecto($nombre, $id)),
            'debe_cambiar_password' => $nuevo,
            'acepto_privacidad' => ! $nuevo,
            'fecha_consentimiento' => $nuevo ? null : now(),
            'estado_cuenta' => $opc['estado'] ?? 'Activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
