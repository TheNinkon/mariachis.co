<?php

return [
    'max_photos' => 4,
    'max_photo_size_kb' => 4096,
    'spam_threshold' => 4,

    // Base inicial: se puede extender con listas por idioma o ML externo.
    'offensive_terms' => [
        'idiota',
        'estafa',
        'fraude',
        'maldito',
        'imbecil',
    ],
];
