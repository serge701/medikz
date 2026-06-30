<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Clínica = tenant. Un médico aislado es una clínica con tipo_plan='individual'.
 * Esta tabla NO está aislada por clínica (ella misma es la unidad de aislamiento).
 */
final class Clinica extends BaseModel
{
    protected string $table = 'clinicas';
    protected bool $tenantScoped = false;

    protected array $fillable = [
        'nombre', 'tipo_plan', 'rfc', 'telefono', 'email',
        'direccion', 'activo',
        'trial_ends_at', 'suscripcion_hasta', 'estado_saas', 'registro_ip',
        'precio_mensual', 'precio_anual',
    ];

    public function findActual(): ?array
    {
        return $this->find(\App\Core\Tenant::clinicaId());
    }

    /** Activa el trial de N días para una clínica recién registrada. */
    public function activarTrial(int $clinicaId, int $dias = 14): void
    {
        $trialFin = (new \DateTimeImmutable())->modify("+{$dias} days")->format('Y-m-d');
        $this->update($clinicaId, [
            'estado_saas'   => 'trial',
            'trial_ends_at' => $trialFin,
        ]);
    }

    /** Verifica si un teléfono ya está registrado en cualquier clínica. */
    public function existeTelefonoGlobal(string $telefono): bool
    {
        $db   = \App\Core\Database::conn();
        $stmt = $db->prepare(
            "SELECT 1 FROM clinicas WHERE telefono = :tel AND activo = 1 LIMIT 1"
        );
        $stmt->execute(['tel' => $telefono]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Cuenta registros recientes desde una IP en los últimos N días.
     * Devuelve true si la IP ya superó el límite permitido.
     */
    public function ipBloqueada(string $ip, int $dias = 30, int $limite = 2): bool
    {
        $db   = \App\Core\Database::conn();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM clinicas
             WHERE registro_ip = :ip
               AND created_at >= DATE_SUB(NOW(), INTERVAL :dias DAY)"
        );
        $stmt->execute(['ip' => $ip, 'dias' => $dias]);
        return (int) $stmt->fetchColumn() >= $limite;
    }

    /** Extiende o activa la suscripción según el plan (mensual = +1 mes, anual = +1 año). */
    public function extenderSuscripcion(int $clinicaId, string $plan = 'mensual'): void
    {
        $actual = $this->find($clinicaId);
        $base   = ($actual['suscripcion_hasta'] ?? null) && $actual['suscripcion_hasta'] > date('Y-m-d')
            ? new \DateTimeImmutable($actual['suscripcion_hasta'])
            : new \DateTimeImmutable();
        $interval   = $plan === 'anual' ? '+1 year' : '+1 month';
        $nuevaFecha = $base->modify($interval)->format('Y-m-d');

        $this->update($clinicaId, [
            'estado_saas'       => 'activo',
            'suscripcion_hasta' => $nuevaFecha,
        ]);
    }
}
