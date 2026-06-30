<?php
/**
 * Runner de migraciones SQL para Medikz.
 *
 * USO:
 *   CLI  →  php migrate.php
 *   Web  →  https://dominio.com/migrate.php?token=TU_TOKEN
 *
 * El token se define en .env como MIGRATE_TOKEN.
 * En producción NUNCA dejes este archivo accesible sin token, o bórralo tras migrar.
 */
declare(strict_types=1);

define('BASE_DIR', __DIR__);
define('MIGRATIONS_DIR', BASE_DIR . '/migrations');

// ── Cargar .env ───────────────────────────────────────────────────────────────
$envPath = BASE_DIR . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if (strlen($v) >= 2 && (($v[0] === '"' && $v[-1] === '"') || ($v[0] === "'" && $v[-1] === "'"))) {
            $v = substr($v, 1, -1);
        }
        if (!array_key_exists($k, $_ENV)) { putenv("{$k}={$v}"); $_ENV[$k] = $v; }
    }
}

// ── Autenticación (web) ───────────────────────────────────────────────────────
$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $token = $_ENV['MIGRATE_TOKEN'] ?? '';
    if ($token === '' || ($_GET['token'] ?? '') !== $token) {
        http_response_code(403);
        exit("Acceso denegado. Agrega ?token=TU_TOKEN a la URL.\n");
    }
    header('Content-Type: text/plain; charset=utf-8');
}

// ── Conexión PDO ──────────────────────────────────────────────────────────────
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $_ENV['DB_HOST'] ?? '127.0.0.1',
    $_ENV['DB_PORT'] ?? '3306',
    $_ENV['DB_NAME'] ?? 'medapp'
);

try {
    $pdo = new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit("ERROR: No se pudo conectar a la base de datos.\n" . $e->getMessage() . "\n");
}

// ── Tabla de control de migraciones ──────────────────────────────────────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS _migraciones (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        archivo    VARCHAR(255) NOT NULL UNIQUE,
        aplicada   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$aplicadas = $pdo->query("SELECT archivo FROM _migraciones")
                 ->fetchAll(PDO::FETCH_COLUMN);
$aplicadas = array_flip($aplicadas);

// ── Modo stamp ────────────────────────────────────────────────────────────────
$stamp = ($isCli && in_array('--stamp', $argv ?? [])) || (!$isCli && isset($_GET['stamp']));

// ── Leer archivos SQL en orden ────────────────────────────────────────────────
$archivos = glob(MIGRATIONS_DIR . '/*.sql');
sort($archivos);

$pendientes = array_filter($archivos, fn($f) => !isset($aplicadas[basename($f)]));

if (empty($pendientes)) {
    echo "✓ No hay migraciones pendientes. La base de datos está al día.\n";
    exit(0);
}

if ($stamp) {
    echo "Modo STAMP — marcando migraciones como aplicadas sin ejecutarlas:\n\n";
    $stmt = $pdo->prepare("INSERT IGNORE INTO _migraciones (archivo) VALUES (?)");
    foreach ($pendientes as $archivo) {
        $nombre = basename($archivo);
        $stmt->execute([$nombre]);
        echo "  ✓ {$nombre}\n";
    }
    echo "\n✓ Listo. " . count($pendientes) . " migraciones estampadas.\n";
    exit(0);
}

// ── Aplicar cada migración ────────────────────────────────────────────────────
$ok = 0;
$err = 0;

foreach ($pendientes as $archivo) {
    $nombre = basename($archivo);
    $sql    = file_get_contents($archivo);

    echo "  → Aplicando {$nombre} ... ";

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare("INSERT INTO _migraciones (archivo) VALUES (?)");
        $stmt->execute([$nombre]);
        echo "OK\n";
        $ok++;
    } catch (PDOException $e) {
        echo "ERROR\n    " . $e->getMessage() . "\n";
        $err++;
        break;
    }
}

echo "\n";
echo "Migraciones aplicadas : {$ok}\n";
if ($err > 0) {
    echo "Errores               : {$err}  ← revisa y corrige antes de continuar\n";
    exit(1);
}
echo "✓ Listo.\n";
exit(0);
