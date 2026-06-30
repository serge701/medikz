<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Tenant;
use PDO;

/**
 * Consultas analíticas de solo-lectura que cruzan varias tablas.
 * No extiende BaseModel porque no hace CRUD individual.
 */
final class Metrica
{
    private PDO $db;
    private int $cid;

    public function __construct()
    {
        $this->db  = Database::conn();
        $this->cid = Tenant::clinicaId();
    }

    // ── KPIs puntuales ────────────────────────────────────────────────────

    public function totalPacientes(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM pacientes
             WHERE clinica_id = :cid AND deleted_at IS NULL"
        );
        $stmt->execute(['cid' => $this->cid]);
        return (int) $stmt->fetchColumn();
    }

    public function totalCitas(string $desde, string $hasta): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM citas
             WHERE clinica_id = :cid AND deleted_at IS NULL
               AND fecha BETWEEN :desde AND :hasta"
        );
        $stmt->execute(['cid' => $this->cid, 'desde' => $desde, 'hasta' => $hasta]);
        return (int) $stmt->fetchColumn();
    }

    public function totalConsultas(string $desde, string $hasta): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM consultas
             WHERE clinica_id = :cid AND deleted_at IS NULL
               AND fecha_consulta BETWEEN :desde AND :hasta"
        );
        $stmt->execute(['cid' => $this->cid, 'desde' => $desde, 'hasta' => $hasta]);
        return (int) $stmt->fetchColumn();
    }

    public function totalIngresos(string $desde, string $hasta): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0)
             FROM cobros
             WHERE clinica_id = :cid AND deleted_at IS NULL
               AND estado = 'pagado'
               AND fecha_cobro BETWEEN :desde AND :hasta"
        );
        $stmt->execute(['cid' => $this->cid, 'desde' => $desde, 'hasta' => $hasta]);
        return (float) $stmt->fetchColumn();
    }

    // ── Series temporales (últimos N meses) ───────────────────────────────

    /**
     * @return array<int,array{mes:string,total:string}>
     */
    public function ingresosPorMes(int $meses = 12): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(fecha_cobro, '%Y-%m') AS mes,
                    SUM(monto) AS total
             FROM cobros
             WHERE clinica_id = :cid AND deleted_at IS NULL AND estado = 'pagado'
               AND fecha_cobro >= DATE_SUB(CURDATE(), INTERVAL :m MONTH)
             GROUP BY mes
             ORDER BY mes ASC"
        );
        $stmt->bindValue('cid', $this->cid, PDO::PARAM_INT);
        $stmt->bindValue('m',   $meses,     PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return array<int,array{mes:string,total:string}>
     */
    public function pacientesNuevosPorMes(int $meses = 12): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes,
                    COUNT(*) AS total
             FROM pacientes
             WHERE clinica_id = :cid AND deleted_at IS NULL
               AND created_at >= DATE_SUB(NOW(), INTERVAL :m MONTH)
             GROUP BY mes
             ORDER BY mes ASC"
        );
        $stmt->bindValue('cid', $this->cid, PDO::PARAM_INT);
        $stmt->bindValue('m',   $meses,     PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ── Distribuciones por periodo ─────────────────────────────────────────

    /**
     * @return array<int,array{estado:string,total:string}>
     */
    public function citasPorEstado(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT estado, COUNT(*) AS total
             FROM citas
             WHERE clinica_id = :cid AND deleted_at IS NULL
               AND fecha BETWEEN :desde AND :hasta
             GROUP BY estado
             ORDER BY total DESC"
        );
        $stmt->execute(['cid' => $this->cid, 'desde' => $desde, 'hasta' => $hasta]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int,array{metodo_pago:string,veces:string,subtotal:string}>
     */
    public function cobrosPorMetodo(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT metodo_pago, COUNT(*) AS veces, SUM(monto) AS subtotal
             FROM cobros
             WHERE clinica_id = :cid AND deleted_at IS NULL AND estado = 'pagado'
               AND fecha_cobro BETWEEN :desde AND :hasta
             GROUP BY metodo_pago
             ORDER BY subtotal DESC"
        );
        $stmt->execute(['cid' => $this->cid, 'desde' => $desde, 'hasta' => $hasta]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int,array{concepto:string,veces:string,monto_total:string}>
     */
    public function topConceptos(string $desde, string $hasta, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT concepto, COUNT(*) AS veces, SUM(monto) AS monto_total
             FROM cobros
             WHERE clinica_id = :cid AND deleted_at IS NULL AND estado = 'pagado'
               AND fecha_cobro BETWEEN :desde AND :hasta
             GROUP BY concepto
             ORDER BY monto_total DESC
             LIMIT " . $limit
        );
        $stmt->execute(['cid' => $this->cid, 'desde' => $desde, 'hasta' => $hasta]);
        return $stmt->fetchAll();
    }
}
