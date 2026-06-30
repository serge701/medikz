<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Protección CSRF. Todo formulario POST debe incluir csrf_field()
 * y todo controlador que reciba POST debe llamar Csrf::verify().
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verify(): void
    {
        $sent = $_POST['_csrf'] ?? '';
        $real = $_SESSION['_csrf'] ?? '';

        if ($real === '' || !hash_equals($real, (string) $sent)) {
            http_response_code(419);
            view('errors/419');
            exit;
        }
    }
}
