<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class Cobro extends BaseModel
{
    protected string $table = 'cobros';

    protected array $fillable = [
        'paciente_id', 'cita_id', 'consulta_id', 'fecha_cobro',
        'concepto', 'monto', 'metodo_pago', 'estado', 'notas', 'creado_por',
    ];

    /**
     * Cobros de un día con JOIN a paciente.
     * @return array<int,array<string,mixed>>
     */
    public function porFecha(string $fecha): array
    {
        $sql = "SELECT c.*,
                       p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap,
                       p.apellido_materno AS pac_am, p.telefono AS pac_telefono
                FROM cobros c
                INNER JOIN pacientes p ON p.id = c.paciente_id AND p.deleted_at IS NULL
                WHERE c.clinica_id = :cid AND c.fecha_cobro = :fecha AND c.deleted_at IS NULL
                ORDER BY c.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $this->clinicaId(), 'fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    /**
     * Cobros de un paciente (más recientes primero).
     * @return array<int,array<string,mixed>>
     */
    public function porPaciente(int $pacienteId, int $limit = 20): array
    {
        $sql = "SELECT * FROM cobros
                WHERE clinica_id = :cid AND paciente_id = :pid AND deleted_at IS NULL
                ORDER BY fecha_cobro DESC, created_at DESC
                LIMIT " . $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $this->clinicaId(), 'pid' => $pacienteId]);
        return $stmt->fetchAll();
    }

    /**
     * Totales del día: suma global y desglose por método de pago.
     * Solo cuenta cobros con estado = 'pagado'.
     *
     * @return array{total: float, por_metodo: array<string,float>}
     */
    public function totalesPorFecha(string $fecha): array
    {
        $sql = "SELECT metodo_pago, SUM(monto) AS subtotal
                FROM cobros
                WHERE clinica_id = :cid AND fecha_cobro = :fecha
                  AND estado = 'pagado' AND deleted_at IS NULL
                GROUP BY metodo_pago";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $this->clinicaId(), 'fecha' => $fecha]);
        $rows = $stmt->fetchAll();

        $total     = 0.0;
        $porMetodo = [];
        foreach ($rows as $row) {
            $porMetodo[$row['metodo_pago']] = (float) $row['subtotal'];
            $total += (float) $row['subtotal'];
        }
        return ['total' => $total, 'por_metodo' => $porMetodo];
    }

    /**
     * Suma de cobros pagados en un rango de fechas.
     */
    public function totalEnPeriodo(string $desde, string $hasta): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0)
             FROM cobros
             WHERE clinica_id = :cid AND deleted_at IS NULL
               AND estado = 'pagado'
               AND fecha_cobro BETWEEN :desde AND :hasta"
        );
        $stmt->execute(['cid' => $this->clinicaId(), 'desde' => $desde, 'hasta' => $hasta]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Cobro con datos completos (JOIN paciente).
     */
    public function conDetalle(int $id): ?array
    {
        $sql = "SELECT c.*,
                       p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap,
                       p.apellido_materno AS pac_am, p.telefono AS pac_telefono
                FROM cobros c
                INNER JOIN pacientes p ON p.id = c.paciente_id AND p.deleted_at IS NULL
                WHERE c.id = :id AND c.clinica_id = :cid AND c.deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'cid' => $this->clinicaId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
