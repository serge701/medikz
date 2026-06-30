<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class Pago extends BaseModel
{
    protected string $table    = 'pagos';
    protected bool $tenantScoped = false;   // pagos se filtran por clinica_id manualmente
    protected bool $softDelete   = false;

    protected array $fillable = [
        'clinica_id', 'stripe_session_id', 'stripe_payment_intent',
        'monto', 'moneda', 'concepto', 'plan', 'estado',
        'periodo_inicio', 'periodo_fin',
    ];

    public function crearPendiente(int $clinicaId, string $sessionId, float $monto, string $plan = 'mensual'): int
    {
        $concepto = $plan === 'anual' ? 'Suscripción anual Medikz' : 'Suscripción mensual Medikz';
        return $this->create([
            'clinica_id'        => $clinicaId,
            'stripe_session_id' => $sessionId,
            'monto'             => $monto,
            'moneda'            => 'MXN',
            'concepto'          => $concepto,
            'plan'              => $plan,
            'estado'            => 'pendiente',
        ]);
    }

    public function porSession(string $sessionId): ?array
    {
        $st = $this->db()->prepare(
            'SELECT * FROM pagos WHERE stripe_session_id = ? LIMIT 1'
        );
        $st->execute([$sessionId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function marcarCompletado(int $id, string $paymentIntent, string $inicio, string $fin): void
    {
        $this->update($id, [
            'estado'           => 'completado',
            'stripe_payment_intent' => $paymentIntent,
            'periodo_inicio'   => $inicio,
            'periodo_fin'      => $fin,
        ]);
    }

    public function ultimos(int $clinicaId, int $limite = 10): array
    {
        $st = $this->db()->prepare(
            "SELECT * FROM pagos WHERE clinica_id = ? AND estado = 'completado'
             ORDER BY created_at DESC LIMIT ?"
        );
        $st->execute([$clinicaId, $limite]);
        return $st->fetchAll();
    }

    private function db(): \PDO
    {
        return \App\Core\Database::conn();
    }
}
