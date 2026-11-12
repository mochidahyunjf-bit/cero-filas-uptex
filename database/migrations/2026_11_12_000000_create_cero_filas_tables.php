<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Módulo A: Identidad y roles
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->unique();
            $t->string('descripcion');
        });

        Schema::create('usuarios', function (Blueprint $t) {
            $t->id();
            $t->foreignId('rol_id')->constrained('roles');
            $t->string('identificador_institucional')->unique();
            $t->string('nombre_completo');
            $t->string('correo_institucional')->unique();
            $t->string('password_hash');
            $t->boolean('debe_cambiar_password')->default(true);
            $t->boolean('acepto_privacidad')->default(false);
            $t->dateTime('fecha_consentimiento')->nullable();
            $t->string('estado_cuenta')->default('Activo');
            $t->string('qr_token')->nullable()->unique();
            $t->dateTime('qr_expira_at')->nullable();
            $t->timestamps();
        });

        // Módulo B: Laboratorios
        Schema::create('laboratorios', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->unique();
            $t->string('nombre');
            $t->string('tipo'); // Computo | Robotica
            $t->integer('capacidad_alumnos');
            $t->string('ubicacion');
            $t->string('estado')->default('Disponible');
        });

        Schema::create('bloques_horarios', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->time('hora_inicio');
            $t->time('hora_fin');
        });

        Schema::create('reservas_laboratorio', function (Blueprint $t) {
            $t->id();
            $t->string('folio')->unique();
            $t->foreignId('laboratorio_id')->constrained('laboratorios');
            $t->foreignId('docente_id')->constrained('usuarios');
            $t->date('fecha');
            $t->foreignId('bloque_id')->constrained('bloques_horarios');
            $t->string('asignatura');
            $t->string('grupo');
            $t->string('estado')->default('Programada');
            $t->boolean('aviso_15min_enviado')->default(false);
            $t->boolean('aviso_fin_enviado')->default(false);
            $t->timestamps();
            $t->index(['laboratorio_id', 'fecha', 'bloque_id']);
        });

        // Módulo C: Inventario y kits
        Schema::create('categorias_material', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('area_pertenencia');
        });

        Schema::create('articulos_catalogo', function (Blueprint $t) {
            $t->id();
            $t->foreignId('categoria_id')->constrained('categorias_material');
            $t->string('codigo_tipo')->unique();
            $t->string('nombre');
            $t->char('clasificacion_abc', 1);
            $t->string('ubicacion_anaquel');
            $t->integer('stock_total')->default(0);
            $t->integer('stock_disponible')->default(0);
            $t->boolean('activo')->default(true);
        });

        Schema::create('activos_unidades', function (Blueprint $t) {
            $t->id();
            $t->foreignId('articulo_catalogo_id')->constrained('articulos_catalogo');
            $t->string('codigo_inventario')->unique();
            $t->string('qr_fisico_hash')->unique();
            $t->string('numero_serie')->nullable();
            $t->string('estado_fisico')->default('Disponible');
            $t->text('observaciones')->nullable();
        });

        Schema::create('plantillas_material', function (Blueprint $t) {
            $t->id();
            $t->string('codigo_kit')->unique();
            $t->foreignId('creado_por')->nullable()->constrained('usuarios');
            $t->string('nombre_plantilla');
            $t->string('descripcion');
            $t->boolean('activa')->default(true);
        });

        Schema::create('plantilla_detalle', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plantilla_id')->constrained('plantillas_material')->cascadeOnDelete();
            $t->foreignId('articulo_catalogo_id')->constrained('articulos_catalogo');
            $t->integer('cantidad_sugerida');
        });

        // Módulo D: Préstamos
        Schema::create('prestamos', function (Blueprint $t) {
            $t->id();
            $t->string('folio')->unique();
            $t->foreignId('solicitante_id')->constrained('usuarios');
            $t->foreignId('reserva_laboratorio_id')->nullable()->constrained('reservas_laboratorio');
            $t->date('fecha_prestamo');
            $t->foreignId('bloque_id')->nullable()->constrained('bloques_horarios');
            $t->string('tipo_solicitud')->default('Express_Ventanilla');
            $t->string('qr_dinamico_token')->nullable()->unique();
            $t->dateTime('qr_expira_at')->nullable();
            // Activo | Parcial | Devuelto_OK | Devuelto_Con_Falla | Vencido | Cancelado
            $t->string('estado')->default('Activo');
            $t->foreignId('entregado_por')->nullable()->constrained('usuarios');
            $t->dateTime('fecha_entrega_real')->nullable();
            $t->foreignId('recibido_por')->nullable()->constrained('usuarios');
            $t->dateTime('fecha_devolucion_real')->nullable();
            $t->boolean('alerta_15min_enviada')->default(false);
            $t->boolean('alerta_24h_enviada')->default(false);
            $t->boolean('sincronizado_offline')->default(false);
            $t->timestamps();
        });

        Schema::create('prestamo_detalles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('prestamo_id')->constrained('prestamos')->cascadeOnDelete();
            $t->foreignId('articulo_catalogo_id')->constrained('articulos_catalogo');
            $t->foreignId('activo_unidad_id')->nullable()->constrained('activos_unidades');
            $t->string('estado_entrega')->default('OK');
            $t->string('estado_devolucion')->default('Pendiente');
            $t->string('nota_devolucion')->nullable();
        });

        Schema::create('sanciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('usuario_id')->constrained('usuarios');
            $t->foreignId('prestamo_id')->nullable()->constrained('prestamos');
            $t->string('motivo');
            $t->text('descripcion');
            $t->dateTime('fecha_inicio');
            $t->dateTime('fecha_fin')->nullable();
            $t->string('estado')->default('Activa');
            $t->foreignId('levantada_por')->nullable()->constrained('usuarios');
            $t->timestamps();
        });

        // Módulo E: Tickets y bitácora
        Schema::create('tickets_mantenimiento', function (Blueprint $t) {
            $t->id();
            $t->string('folio_ticket')->unique();
            $t->foreignId('activo_unidad_id')->constrained('activos_unidades');
            $t->foreignId('prestamo_id')->nullable()->constrained('prestamos');
            $t->foreignId('reportado_por')->constrained('usuarios');
            $t->string('origen_reporte');
            $t->text('descripcion_falla');
            $t->string('prioridad')->default('Media');
            $t->string('estado')->default('Abierto');
            $t->foreignId('atendido_por')->nullable()->constrained('usuarios');
            $t->dateTime('closed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('bitacora_auditoria', function (Blueprint $t) {
            $t->id();
            $t->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $t->string('modulo');
            $t->string('accion');
            $t->string('entidad_afectada');
            $t->unsignedBigInteger('entidad_id')->nullable();
            $t->json('datos_anteriores')->nullable();
            $t->json('datos_nuevos')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach ([
            'bitacora_auditoria', 'tickets_mantenimiento', 'sanciones', 'prestamo_detalles', 'prestamos',
            'plantilla_detalle', 'plantillas_material', 'activos_unidades', 'articulos_catalogo',
            'categorias_material', 'reservas_laboratorio', 'bloques_horarios', 'laboratorios', 'usuarios', 'roles',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
