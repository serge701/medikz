<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Usuario;

/**
 * Autenticación y sesión.
 * - Sesión endurecida (httponly, samesite, regeneración de ID al iniciar sesión).
 * - Guarda en sesión solo lo esencial: id, clinica_id, rol, es_propietario, nombre.
 */
final class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $cfg = Config::get('session');

        session_name($cfg['name']);
        session_set_cookie_params([
            'lifetime' => $cfg['lifetime'],
            'path'     => Config::basePath() === '' ? '/' : Config::basePath(),
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);

        session_start();
    }

    /**
     * Intenta autenticar por email + contraseña.
     * Devuelve true si tuvo éxito.
     */
    public static function attempt(string $email, string $password): bool
    {
        $usuarioModel = new Usuario();
        $user = $usuarioModel->findByEmail($email);

        if ($user === null || (int) $user['activo'] !== 1) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Rehash transparente si el algoritmo cambió.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $usuarioModel->updatePasswordHash(
                (int) $user['id'],
                password_hash($password, PASSWORD_DEFAULT)
            );
        }

        // Evita fijación de sesión.
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'             => (int) $user['id'],
            'clinica_id'     => (int) $user['clinica_id'],
            'nombre'         => $user['nombre'],
            'email'          => $user['email'],
            'rol'            => $user['rol'],
            'es_propietario' => (int) $user['es_propietario'],
            'superadmin'     => (int) ($user['superadmin'] ?? 0),
        ];

        $usuarioModel->touchLastLogin((int) $user['id']);

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']) ? (int) $_SESSION['user']['id'] : null;
    }

    public static function rol(): ?string
    {
        return $_SESSION['user']['rol'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::rol(), $roles, true);
    }

    public static function esSuperAdmin(): bool
    {
        return (int) ($_SESSION['user']['superadmin'] ?? 0) === 1;
    }

    public static function requireSuperAdmin(): void
    {
        self::require();
        if (!self::esSuperAdmin()) {
            http_response_code(403);
            die('Acceso denegado.');
        }
    }

    public static function esPropietario(): bool
    {
        return (int) ($_SESSION['user']['es_propietario'] ?? 0) === 1;
    }

    /**
     * REGLA DE NEGOCIO / CUMPLIMIENTO:
     * La recepción NO puede ver historial clínico ni notas de consulta.
     * Solo médico y admin_clinica acceden a información clínica.
     */
    public static function puedeVerClinico(): bool
    {
        return self::is('medico', 'admin_clinica');
    }

    /**
     * Crea la sesión directamente (sin validar contraseña).
     * Usado en el flujo de registro para loguear al propietario recién creado.
     */
    public static function loginManual(
        int $clinicaId,
        int $usuarioId,
        string $nombre,
        string $email,
        string $rol,
        int $esPropietario
    ): void {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'             => $usuarioId,
            'clinica_id'     => $clinicaId,
            'nombre'         => $nombre,
            'email'          => $email,
            'rol'            => $rol,
            'es_propietario' => $esPropietario,
        ];
        (new \App\Models\Usuario())->touchLastLogin($usuarioId);
    }

    /** Exige sesión activa y suscripción vigente, o redirige según corresponda. */
    public static function require(): void
    {
        if (!self::check()) {
            flash('error', 'Inicia sesión para continuar.');
            redirect('login');
            exit;
        }
        Suscripcion::guardia();
    }

    /** Exige uno de los roles dados o muestra 403. */
    public static function requireRole(string ...$roles): void
    {
        self::require();
        if (!self::is(...$roles)) {
            http_response_code(403);
            view('errors/403');
            exit;
        }
    }
}
