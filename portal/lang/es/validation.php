<?php

return [
    'required' => 'El campo :attribute es obligatorio.', 'required_with' => 'Completa :attribute cuando registres :values.',
    'email' => 'El campo :attribute debe ser un correo válido.', 'unique' => 'El valor de :attribute ya está registrado.',
    'exists' => 'El valor seleccionado de :attribute no existe.', 'integer' => 'El campo :attribute debe ser un número entero.',
    'numeric' => 'El campo :attribute debe ser numérico.', 'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'date' => 'El campo :attribute debe ser una fecha válida.', 'after' => 'El campo :attribute debe ser posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser igual o posterior a :date.', 'before' => 'El campo :attribute debe ser anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser igual o anterior a :date.', 'string' => 'El campo :attribute debe ser texto.',
    'array' => 'El campo :attribute debe ser una lista válida.', 'distinct' => 'No repitas valores en :attribute.',
    'confirmed' => 'La confirmación de :attribute no coincide.', 'in' => 'El valor de :attribute no está permitido.',
    'min' => ['string' => ':attribute debe tener al menos :min caracteres.', 'numeric' => ':attribute debe ser como mínimo :min.', 'array' => ':attribute debe contener al menos :min elemento(s).'],
    'max' => ['string' => ':attribute no puede superar :max caracteres.', 'numeric' => ':attribute no puede superar :max.', 'array' => ':attribute admite como máximo :max elementos.', 'file' => 'El archivo no puede superar :max KB.'],
    'between' => ['numeric' => ':attribute debe estar entre :min y :max.'], 'size' => ['array' => ':attribute requiere exactamente :size elementos.'],
    'lte' => ['numeric' => ':attribute no puede superar :value.'], 'mimes' => 'El archivo debe tener formato :values.',
    'attributes' => ['email' => 'correo', 'password' => 'contraseña', 'name' => 'nombre', 'capacity' => 'capacidad', 'reserve' => 'reserva', 'merit' => 'mérito', 'date' => 'fecha', 'grade' => 'nota', 'file' => 'archivo', 'preferences' => 'preferencias', 'starts_on' => 'inicio del rote', 'ends_on' => 'fin del rote', 'opens_at' => 'apertura', 'closes_at' => 'cierre'],
];
