<?php

return [
    'accepted' => 'Debes aceptar :attribute.',
    'confirmed' => 'La confirmacion de :attribute no coincide.',
    'email' => 'Ingresa un correo electronico valido.',
    'in' => 'Selecciona un valor valido para :attribute.',
    'max' => [
        'string' => ':Attribute no debe tener mas de :max caracteres.',
    ],
    'regex' => 'El formato de :attribute no es valido.',
    'required' => 'El campo :attribute es obligatorio.',
    'size' => [
        'string' => ':Attribute debe tener :size caracteres.',
    ],
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'Este :attribute ya esta registrado.',

    'custom' => [
        'email' => [
            'unique' => 'Este correo electronico ya esta registrado.',
        ],
        'password' => [
            'confirmed' => 'Las contrasenas no coinciden.',
        ],
        'phone_number' => [
            'regex' => 'Escribe un numero de telefono valido.',
        ],
        'terms' => [
            'accepted' => 'Debes aceptar los terminos y condiciones para continuar.',
        ],
    ],

    'attributes' => [
        'email' => 'correo electronico',
        'first_name' => 'nombre',
        'last_name' => 'apellido',
        'password' => 'contrasena',
        'password_confirmation' => 'confirmacion de contrasena',
        'phone_country_iso2' => 'indicativo',
        'phone_number' => 'telefono movil',
        'terms' => 'los terminos y condiciones',
    ],
];
