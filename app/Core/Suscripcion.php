<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Clinica;

/**
 * Lógica de estados SaaS: trial, activo, suspendido.
 * Llamado desde Auth::require() para interceptar acceso en cada petición protegida.
 */
final class Suscripcion
{
    /** Rutas que no requieren suscripción activa (prefijos). */
    private const EXCLUIDAS = ['suscri', 'webhook', 'logout', 'login', 'registro', 'superadmin'];

    /**
     * Comprueba el estado SaaS de la clínica del usuario actual.
     * Redirige a /suscripcion si la cuenta está suspendida o el trial expiró.
     * No hace nada si la ruta actual está excluida.
     */
    public static function guardia(): void
    {
        if (!Auth::check()) {
            return;
        }

        // Detectar si la ruta actual está excluida
        $ruta = self::rutaActual();
        foreach (self::EXCLUIDAS as $prefijo) {
            if (str_starts_with($ruta, $prefijo)) {
                return;
            }
        }

        $clinicaId = (int) ($_SESSION['user']['clinica_id'] ?? 0);
        if ($clinicaId === 0) {
            return;
        }

        $clinicaModel = new Clinica();
        $clinica      = $clinicaModel->find($clinicaId);

        if ($clinica === null) {
            return;
        }

        $estado = $clinica['estado_saas'] ?? 'trial';

        if ($estado === 'activo') {
            // Verificar que la suscripción no haya vencido
            if (
                $clinica['suscripcion_hasta'] !== null &&
                $clinica['suscripcion_hasta'] < date('Y-m-d')
            ) {
                // Suspender automáticamente
                $clinicaModel->update($clinicaId, ['estado_saas' => 'suspendido']);
                self::redirigirSuscripcion('Tu suscripción venció. Renueva para continuar.');
            }
            return;
        }

        if ($estado === 'trial') {
            $trialFin = $clinica['trial_ends_at'] ?? null;
            if ($trialFin !== null && $trialFin < date('Y-m-d')) {
                // Trial expirado → suspender
                $clinicaModel->update($clinicaId, ['estado_saas' => 'suspendido']);
                self::redirigirSuscripcion('Tu período de prueba terminó. Suscríbete para continuar.');
            }
            // Trial activo → dejar pasar
            return;
        }

        // estado === 'suspendido'
        self::redirigirSuscripcion('Tu cuenta está suspendida. Renueva tu suscripción.');
    }

    /** Devuelve días restantes de trial (0 si ya expiró o no aplica). */
    public static function diasTrial(array $clinica): int
    {
        if (($clinica['estado_saas'] ?? '') !== 'trial') {
            return 0;
        }
        $fin = $clinica['trial_ends_at'] ?? null;
        if ($fin === null) {
            return 0;
        }
        $diff = (new \DateTimeImmutable($fin))->diff(new \DateTimeImmutable())->days;
        // diff->days is absolute; check if fin >= hoy
        return $fin >= date('Y-m-d') ? (int) $diff : 0;
    }

    /** Calcula correctamente los días restantes del trial incluyendo hoy. */
    public static function diasTrialRestantes(array $clinica): int
    {
        if (($clinica['estado_saas'] ?? '') !== 'trial') {
            return 0;
        }
        $fin = $clinica['trial_ends_at'] ?? null;
        if ($fin === null || $fin < date('Y-m-d')) {
            return 0;
        }
        $hoy    = new \DateTimeImmutable(date('Y-m-d'));
        $finDt  = new \DateTimeImmutable($fin);
        return (int) $hoy->diff($finDt)->days + 1; // +1 incluye el día de hoy
    }

    private static function redirigirSuscripcion(string $mensaje): void
    {
        flash('error', $mensaje);
        redirect('suscripcion');
        exit;
    }

    private static function rutaActual(): string
    {
        $basePath = Config::basePath();
        $uri      = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if ($basePath !== '' && str_starts_with($uri, '/' . ltrim($basePath, '/'))) {
            $uri = substr($uri, strlen('/' . ltrim($basePath, '/')));
        }
        return ltrim($uri, '/');
    }
}
