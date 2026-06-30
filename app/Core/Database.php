<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexión única (singleton) a la base de datos vía PDO.
 * Todo en la app usa esta clase. Nunca se concatena SQL: siempre preparadas.
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function conn(): PDO
    {
        if (self::$instance === null) {
            $cfg = Config::get('db');

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['name'],
                $cfg['charset']
            );

            try {
                self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // preparadas reales
                ]);
            } catch (PDOException $e) {
                // No exponemos credenciales ni el DSN al usuario final.
                throw new RuntimeException(
                    'No se pudo conectar a la base de datos. Revisa config/config.php y que MySQL esté encendido en XAMPP. Detalle: '
                    . $e->getMessage()
                );
            }
        }

        return self::$instance;
    }
}
