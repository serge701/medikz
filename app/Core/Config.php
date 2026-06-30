<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $items = [];
    private static string $baseUrl = '';
    private static string $basePath = '';

    public static function boot(): void
    {
        self::loadEnv(dirname(__DIR__, 2) . '/.env');
        self::$items = require dirname(__DIR__, 2) . '/config/config.php';
        date_default_timezone_set(self::get('app')['timezone'] ?? 'UTC');
        self::detectBaseUrl();
    }

    private static function loadEnv(string $path): void
    {
        if (!is_file($path)) return;

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (!str_contains($line, '=')) continue;

            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);

            if (strlen($val) >= 2 && $val[0] === '"' && $val[-1] === '"') {
                $val = substr($val, 1, -1);
            } elseif (strlen($val) >= 2 && $val[0] === "'" && $val[-1] === "'") {
                $val = substr($val, 1, -1);
            }

            if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
                putenv("{$key}={$val}");
                $_ENV[$key]    = $val;
                $_SERVER[$key] = $val;
            }
        }
    }

    private static function detectBaseUrl(): void
    {
        $docRoot   = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'] ?? __FILE__));

        if ($docRoot !== '' && stripos($scriptDir, $docRoot) === 0) {
            $basePath = substr($scriptDir, strlen($docRoot));
        } else {
            $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        }

        $basePath = ($basePath === '/' || $basePath === '.') ? '' : rtrim($basePath, '/');

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443')
            ? 'https' : 'http';

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        self::$basePath = $basePath;
        self::$baseUrl  = $scheme . '://' . $host . $basePath;
    }

    public static function get(string $key): mixed
    {
        return self::$items[$key] ?? null;
    }

    public static function baseUrl(): string
    {
        return self::$baseUrl;
    }

    /** Ruta base sin esquema/host, p.ej. "/medapp". Útil para el Router. */
    public static function basePath(): string
    {
        return self::$basePath;
    }
}
