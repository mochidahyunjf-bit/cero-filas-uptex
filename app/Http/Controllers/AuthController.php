<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private const ACCESO_RAPIDO = [
        'Alumno' => '231650001',
        'Docente' => 'DOC-001',
        'Administrador' => 'ADM-01',
        'Supervisor' => 'SUP-01',
        'Superadministrador' => 'SADM-01',
    ];

    public function form()
    {
        return view('auth.login', ['accesoRapido' => config('app.demo') ? array_keys(self::ACCESO_RAPIDO) : []]);
    }

    public function login(Request $request)
    {
        $datos = $request->validate([
            'usuario' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ]);

        $entrada = trim($datos['usuario']);
        if (str_contains($entrada, '@') && ! str_ends_with(strtolower($entrada), '@uptex.edu.mx')) {
            return back()->withErrors(['usuario' => 'Solo se permiten correos institucionales @uptex.edu.mx.'])->onlyInput('usuario');
        }

        $usuario = Usuario::where('correo_institucional', $entrada)->orWhere('identificador_institucional', $entrada)->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password_hash)) {
            return back()->withErrors(['usuario' => 'Credenciales incorrectas.'])->onlyInput('usuario');
        }
        if ($usuario->estado_cuenta === 'Inactivo') {
            return back()->withErrors(['usuario' => 'La cuenta está inactiva. Acude a Coordinación de Laboratorios.'])->onlyInput('usuario');
        }

        return $this->iniciarSesion($request, $usuario);
    }

    public function accesoRapido(Request $request, string $rol)
    {
        abort_unless(config('app.demo') && isset(self::ACCESO_RAPIDO[$rol]), 404);

        return $this->iniciarSesion($request, Usuario::where('identificador_institucional', self::ACCESO_RAPIDO[$rol])->firstOrFail());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function passwordForm()
    {
        return view('auth.password');
    }

    public function passwordGuardar(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $usuario = $request->user();
        if (Hash::check($request->password, $usuario->password_hash)) {
            return back()->withErrors(['password' => 'La nueva contraseña debe ser distinta a la temporal.']);
        }

        $usuario->update(['password_hash' => Hash::make($request->password), 'debe_cambiar_password' => false]);
        $this->bitacora($usuario->id, 'Cambio de contraseña en primer ingreso');

        return redirect($usuario->rutaInicio());
    }

    public function consentimientoForm()
    {
        return view('auth.consentimiento');
    }

    public function consentimientoGuardar(Request $request)
    {
        $request->validate([
            'privacidad' => ['accepted'],
            'corresponsabilidad' => ['accepted'],
        ], [
            'accepted' => 'Debes aceptar ambos documentos para continuar.',
        ]);
        $usuario = $request->user();
        $usuario->update(['acepto_privacidad' => true, 'fecha_consentimiento' => now()]);
        $this->bitacora($usuario->id, 'Aceptación de aviso LFPDPPP y carta de corresponsabilidad');

        return redirect($usuario->rutaInicio());
    }

    private function iniciarSesion(Request $request, Usuario $usuario)
    {
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended($usuario->rutaInicio());
    }

    private function bitacora(int $usuarioId, string $accion): void
    {
        DB::table('bitacora_auditoria')->insert([
            'usuario_id' => $usuarioId, 'modulo' => 'Cuentas', 'accion' => $accion,
            'entidad_afectada' => 'usuarios', 'entidad_id' => $usuarioId,
        ]);
    }
}
