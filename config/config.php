<?php
declare(strict_types=1);

return [
    'db' => [
        'host'    => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port'    => $_ENV['DB_PORT'] ?? '3306',
        'name'    => $_ENV['DB_NAME'] ?? 'medapp',
        'user'    => $_ENV['DB_USER'] ?? 'root',
        'pass'    => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
    ],

    'app' => [
        'name'     => $_ENV['APP_NAME']     ?? 'Medikz',
        'env'      => $_ENV['APP_ENV']      ?? 'local',
        'debug'    => filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City',
    ],

    'session' => [
        'name'     => 'medapp_session',
        'lifetime' => 60 * 60 * 4,
    ],

    'stripe' => [
        'public_key'     => $_ENV['STRIPE_PUBLIC_KEY']  ?? '',
        'secret_key'     => $_ENV['STRIPE_SECRET_KEY']  ?? '',
        'webhook_secret' => $_ENV['STRIPE_WEBHOOK']     ?? '',
        'precio_mxn'     => (int) ($_ENV['STRIPE_PRECIO_MXN']   ?? 38900),
        'precio_anual'   => (int) ($_ENV['STRIPE_PRECIO_ANUAL'] ?? 390000),
        'trial_dias'     => (int) ($_ENV['STRIPE_TRIAL_DIAS']   ?? 14),
    ],

    'twilio' => [
        'account_sid'   => $_ENV['TWILIO_SID']     ?? '',
        'auth_token'    => $_ENV['TWILIO_TOKEN']   ?? '',
        'from_whatsapp' => $_ENV['TWILIO_FROM']    ?? 'whatsapp:+14155238886',
        'enabled'       => filter_var($_ENV['TWILIO_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    ],
];
