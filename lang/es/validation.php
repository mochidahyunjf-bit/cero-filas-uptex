<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'accepted' => 'Debes aceptar :attribute.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'exists' => 'El :attribute seleccionado no es válido.',
    'in' => 'El :attribute seleccionado no es válido.',
    'file' => ':attribute debe ser un archivo.',
    'date' => ':attribute no es una fecha válida.',
    'after_or_equal' => ':attribute debe ser hoy o una fecha posterior.',
    'min' => ['string' => ':attribute debe tener al menos :min caracteres.'],
    'max' => ['string' => ':attribute no debe exceder :max caracteres.'],
    'password' => [
        'letters' => ':attribute debe contener al menos una letra.',
        'numbers' => ':attribute debe contener al menos un número.',
    ],
    'attributes' => [
        'qr' => 'QR del usuario', 'codigos' => 'unidades escaneadas', 'codigo' => 'código de la unidad', 'descripcion' => 'descripción de la falla', 'estado' => 'estado',
        'usuario' => 'usuario', 'password' => 'contraseña', 'motivo' => 'motivo', 'csv' => 'archivo CSV',
        'laboratorio_id' => 'laboratorio', 'bloque_id' => 'bloque', 'fecha' => 'fecha', 'asignatura' => 'asignatura', 'grupo' => 'grupo',
    ],
];
