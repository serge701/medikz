<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Tenant = clínica. La unidad de aislamiento de datos.
 * Un médico aislado es simplemente una clínica con tipo_plan='individual'.
 *
 * El clinica_id vive en la sesión y NUNCA viene del navegador (ni de un input,
 * ni de la URL). Así un usuario no puede pedir datos de otra clínica.
 */
final class Tenant
{
    public static function clinicaId(): int
    {
        $user = Auth::user();
        if ($user === null || empty($user['clinica_id'])) {
            throw new RuntimeException('No hay clínica en la sesión actual.');
        }
        return (int) $user['clinica_id'];
    }
}
