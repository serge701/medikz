<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Controlador base. Métodos cómodos compartidos por todos los controladores.
 */
abstract class Controller
{
    /** Renderiza una vista con el layout indicado. */
    protected function render(string $view, array $data = [], string $layout = 'app'): void
    {
        view($view, $data, $layout);
    }

    /** Lee un campo de POST con valor por defecto y trim. */
    protected function input(string $key, mixed $default = ''): mixed
    {
        $val = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($val) ? trim($val) : $val;
    }

    /** Verdadero si la petición es POST. */
    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}
