// config/app.php
return [
    'name' => 'Impakto Mídia',
    'version' => '2.0',
    'timezone' => 'America/Recife',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'pagination' => [
        'per_page' => 10,
        'max_per_page' => 100
    ]
];