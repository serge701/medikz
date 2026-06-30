<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Auditoria as AuditoriaModel;

/**
 * Bitácora de auditoría.
 * Registra acciones sensibles (login, ver/editar/eliminar expediente, etc.).
 * Necesario por LFPDPPP (rastreabilidad de acceso a datos de salud).
 */
final class Auditoria
{
    /**
     * @param string     $accion    p.ej. 'login', 'paciente.ver', 'receta.crear'
     * @param string|null $entidad  p.ej. 'paciente'
     * @param int|null    $entidadId
     * @param array       $detalle  datos adicionales (se guardan como JSON)
     */
    public static function log(
        string $accion,
        ?string $entidad = null,
        ?int $entidadId = null,
        array $detalle = []
    ): void {
        try {
            (new AuditoriaModel())->registrar([
                'clinica_id' => Auth::user()['clinica_id'] ?? null,
                'usuario_id' => Auth::id(),
                'accion'     => $accion,
                'entidad'    => $entidad,
                'entidad_id' => $entidadId,
                'detalle'    => $detalle ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : null,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) {
            // La auditoría nunca debe tumbar la app; se registra el fallo en archivo.
            error_log('[auditoria] ' . $e->getMessage());
        }
    }

    /**
     * Igual que log() pero con clinica_id y usuario_id explícitos.
     * Útil cuando aún no hay sesión (registro, webhook de Stripe).
     */
    public static function logDirecto(
        int $clinicaId,
        ?int $usuarioId,
        string $accion,
        ?string $entidad = null,
        ?int $entidadId = null,
        array $detalle = []
    ): void {
        try {
            (new \App\Models\Auditoria())->registrar([
                'clinica_id' => $clinicaId,
                'usuario_id' => $usuarioId,
                'accion'     => $accion,
                'entidad'    => $entidad,
                'entidad_id' => $entidadId,
                'detalle'    => $detalle ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : null,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) {
            error_log('[auditoria] ' . $e->getMessage());
        }
    }
}
