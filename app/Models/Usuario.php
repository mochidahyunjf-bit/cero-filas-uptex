<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = [
        'rol_id', 'identificador_institucional', 'nombre_completo', 'correo_institucional',
        'password_hash', 'debe_cambiar_password', 'acepto_privacidad', 'fecha_consentimiento', 'estado_cuenta',
    ];

    protected $hidden = ['password_hash', 'qr_token'];

    protected function casts(): array
    {
        return [
            'debe_cambiar_password' => 'boolean',
            'acepto_privacidad' => 'boolean',
            'fecha_consentimiento' => 'datetime',
            'qr_expira_at' => 'datetime',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol->nombre, $roles, true);
    }

    public function rutaInicio(): string
    {
        return match ($this->rol->nombre) {
            'Administrador' => route('ventanilla'),
            'Supervisor', 'Superadministrador' => route('supervisor'),
            default => route('portal'),
        };
    }

    // Regla: 3 primeras letras del nombre (sin grado académico) + identificador + "#26"
    public static function passwordPorDefecto(string $nombre, string $identificador): string
    {
        $sinGrado = preg_replace('/^(Ing|Mtr[oa]|Dr[a]?|Lic)\.\s*/u', '', trim($nombre));

        return Str::ucfirst(Str::lower(Str::substr(Str::ascii($sinGrado), 0, 3))).$identificador.'#26';
    }
}
