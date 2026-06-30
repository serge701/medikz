<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Auth;

/**
 * Helpers globales. La pieza clave es url(): TODO enlace, redirección y assets
 * pasan por aquí, anteponiendo la BASE_URL detectada. Nunca escribas "/algo"
 * a mano en las vistas; usa url('algo').
 */

if (!function_exists('url')) {
    /** Construye una URL absoluta a partir de una ruta interna. */
    function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $base = Config::baseUrl();
        return $path === '' ? $base . '/' : $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    /** URL a un archivo dentro de /assets. */
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    /** Redirige a una ruta interna (usa url() internamente). */
    function redirect(string $path = ''): void
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('e')) {
    /** Escapa salida para HTML. Úsalo SIEMPRE al imprimir datos. */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('view')) {
    /**
     * Renderiza una vista de /app/Views.
     * $layout: 'app' (con sidebar), 'guest' (login) o '' (sin layout).
     */
    function view(string $name, array $data = [], string $layout = ''): void
    {
        $viewsDir = dirname(__DIR__) . '/Views/';
        $viewFile = $viewsDir . str_replace('.', '/', $name) . '.php';

        if (!is_file($viewFile)) {
            http_response_code(500);
            echo "Vista no encontrada: " . e($name);
            return;
        }

        extract($data, EXTR_SKIP);

        // El contenido de la vista se captura en $content y se inyecta al layout.
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === '') {
            echo $content;
            return;
        }

        $layoutFile = $viewsDir . 'layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }
        require $layoutFile;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    /** Campo oculto para incluir en todo formulario POST. */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('flash')) {
    /** Guarda un mensaje de un solo uso (se muestra en la próxima vista). */
    function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }
}

if (!function_exists('get_flash')) {
    /** Lee y borra un mensaje flash. */
    function get_flash(string $key): ?string
    {
        $msg = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }
}

if (!function_exists('old')) {
    /** Recupera el último valor enviado (para repoblar formularios tras error). */
    function old(string $key, string $default = ''): string
    {
        return e($_SESSION['_old'][$key] ?? $default);
    }
}

if (!function_exists('set_old')) {
    /** Guarda los inputs para repoblar el formulario si hay error de validación. */
    function set_old(array $data): void
    {
        unset($data['_csrf'], $data['password'], $data['password_confirmation']);
        $_SESSION['_old'] = $data;
    }
}

if (!function_exists('clear_old')) {
    function clear_old(): void
    {
        unset($_SESSION['_old']);
    }
}

if (!function_exists('auth')) {
    /** @return array<string,mixed>|null */
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('active')) {
    /** Devuelve 'active' si el segmento actual coincide (para resaltar el menú). */
    function active(string $segment): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        return str_contains($uri, '/' . $segment) ? 'active' : '';
    }
}

if (!function_exists('edad_anios')) {
    /** Calcula la edad en años a partir de una fecha 'YYYY-MM-DD'. Null si no hay fecha. */
    function edad_anios(?string $fecha): ?int
    {
        if (empty($fecha)) {
            return null;
        }
        try {
            $nac = new DateTime($fecha);
            return (int) $nac->diff(new DateTime('today'))->y;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('nombre_completo')) {
    /** Une nombre + apellidos en una sola cadena legible. */
    function nombre_completo(array $p): string
    {
        return trim(($p['nombre'] ?? '') . ' ' . ($p['apellido_paterno'] ?? '') . ' ' . ($p['apellido_materno'] ?? ''));
    }
}

if (!function_exists('sexo_label')) {
    function sexo_label(?string $s): string
    {
        return match ($s) {
            'M' => 'Masculino',
            'F' => 'Femenino',
            'O' => 'Otro',
            default => '—',
        };
    }
}

if (!function_exists('fecha_legible')) {
    /** Convierte 'YYYY-MM-DD' a 'd/m/Y'. Cadena vacía si no hay fecha. */
    function fecha_legible(?string $fecha): string
    {
        if (empty($fecha)) {
            return '';
        }
        try {
            return (new DateTime($fecha))->format('d/m/Y');
        } catch (\Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('fecha_dia_es')) {
    /** Formatea un DateTime como "Martes, 24 de junio de 2026". */
    function fecha_dia_es(DateTime $d): string
    {
        $dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
        $meses = ['','enero','febrero','marzo','abril','mayo','junio',
                  'julio','agosto','septiembre','octubre','noviembre','diciembre'];
        return ucfirst($dias[(int) $d->format('w')]) . ', '
             . (int) $d->format('j') . ' de '
             . $meses[(int) $d->format('n')] . ' de '
             . $d->format('Y');
    }
}

if (!function_exists('hora_legible')) {
    /** '09:30:00' → '09:30'. */
    function hora_legible(?string $hora): string
    {
        return !empty($hora) ? substr($hora, 0, 5) : '';
    }
}

if (!function_exists('estado_cita_label')) {
    function estado_cita_label(?string $estado): string
    {
        return match ($estado) {
            'programada' => 'Programada',
            'confirmada' => 'Confirmada',
            'atendida'   => 'Atendida',
            'cancelada'  => 'Cancelada',
            'no_asistio' => 'No asistió',
            default      => '—',
        };
    }
}

if (!function_exists('estado_cita_badge')) {
    function estado_cita_badge(?string $estado): string
    {
        $class = match ($estado) {
            'programada' => 'secondary',
            'confirmada' => 'primary',
            'atendida'   => 'success',
            'cancelada'  => 'danger',
            'no_asistio' => 'warning',
            default      => 'light',
        };
        return '<span class="badge text-bg-' . $class . '">' . estado_cita_label($estado) . '</span>';
    }
}

if (!function_exists('formato_moneda')) {
    /** Formatea un número como moneda: 1234.5 → '$1,234.50' */
    function formato_moneda(float|string|null $monto, string $symbol = '$'): string
    {
        return $symbol . number_format((float) $monto, 2, '.', ',');
    }
}

if (!function_exists('metodo_pago_label')) {
    function metodo_pago_label(?string $m): string
    {
        return match ($m) {
            'efectivo'     => 'Efectivo',
            'tarjeta'      => 'Tarjeta',
            'transferencia'=> 'Transferencia',
            'cheque'       => 'Cheque',
            default        => '—',
        };
    }
}

if (!function_exists('metodo_pago_icon')) {
    function metodo_pago_icon(?string $m): string
    {
        return match ($m) {
            'efectivo'     => 'bi-cash-coin',
            'tarjeta'      => 'bi-credit-card',
            'transferencia'=> 'bi-arrow-left-right',
            'cheque'       => 'bi-file-earmark-text',
            default        => 'bi-question-circle',
        };
    }
}

if (!function_exists('estado_cobro_label')) {
    function estado_cobro_label(?string $e): string
    {
        return match ($e) {
            'pagado'    => 'Pagado',
            'pendiente' => 'Pendiente',
            'cancelado' => 'Cancelado',
            default     => '—',
        };
    }
}

if (!function_exists('estado_cobro_badge')) {
    function estado_cobro_badge(?string $e): string
    {
        $class = match ($e) {
            'pagado'    => 'success',
            'pendiente' => 'warning',
            'cancelado' => 'danger',
            default     => 'secondary',
        };
        return '<span class="badge text-bg-' . $class . '">' . estado_cobro_label($e) . '</span>';
    }
}
