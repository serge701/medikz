<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class Consulta extends BaseModel
{
    protected string $table = 'consultas';

    protected array $fillable = [
        'paciente_id', 'medico_id', 'cita_id', 'fecha_consulta',
        'motivo_consulta', 'exploracion_fisica', 'diagnostico',
        'tratamiento', 'observaciones', 'proximo_control', 'creado_por',
    ];

    /**
     * Lista de consultas con JOIN a paciente y médico.
     * Si se pasa $pacienteId filtra por ese paciente.
     *
     * @return array<int,array<string,mixed>>
     */
    public function recientes(
        int     $limit      = 50,
        ?int    $pacienteId = null,
        ?string $desde      = null,
        ?string $hasta      = null
    ): array {
        $where  = ['c.clinica_id = :cid', 'c.deleted_at IS NULL'];
        $params = ['cid' => $this->clinicaId()];

        if ($pacienteId !== null) {
            $where[]        = 'c.paciente_id = :pid';
            $params['pid']  = $pacienteId;
        }
        if ($desde !== null) {
            $where[]          = 'c.fecha_consulta >= :desde';
            $params['desde']  = $desde;
        }
        if ($hasta !== null) {
            $where[]          = 'c.fecha_consulta <= :hasta';
            $params['hasta']  = $hasta;
        }

        $sql = "SELECT c.*,
                       p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap,
                       p.apellido_materno AS pac_am,
                       m.nombre AS med_nombre,
                       ci.hora_inicio AS cita_hora
                FROM consultas c
                INNER JOIN pacientes p  ON p.id  = c.paciente_id AND p.deleted_at IS NULL
                LEFT  JOIN medicos  m  ON m.id  = c.medico_id   AND m.deleted_at IS NULL
                LEFT  JOIN citas    ci ON ci.id = c.cita_id     AND ci.deleted_at IS NULL
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.fecha_consulta DESC, ci.hora_inicio ASC, c.created_at DESC
                LIMIT " . $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Últimas consultas de un paciente (para la ficha). */
    public function porPaciente(int $pacienteId, int $limit = 5): array
    {
        return $this->recientes($limit, $pacienteId);
    }

    /** Devuelve la consulta vinculada a una cita, o null si no existe. */
    public function porCita(int $citaId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM consultas
             WHERE clinica_id = :cid AND cita_id = :cita_id AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute(['cid' => $this->clinicaId(), 'cita_id' => $citaId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Número de consultas en un rango de fechas.
     */
    public function contarEnPeriodo(string $desde, string $hasta): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM consultas
             WHERE clinica_id = :cid AND deleted_at IS NULL
               AND fecha_consulta BETWEEN :desde AND :hasta"
        );
        $stmt->execute(['cid' => $this->clinicaId(), 'desde' => $desde, 'hasta' => $hasta]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Una consulta con datos completos (JOIN paciente + médico).
     * Usar en show() en lugar de find() para tener todo en una sola consulta.
     */
    public function conDetalle(int $id): ?array
    {
        $sql = "SELECT c.*,
                       p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap,
                       p.apellido_materno AS pac_am,
                       p.fecha_nacimiento AS pac_nacimiento,
                       p.tipo_sangre AS pac_tipo_sangre,
                       p.alergias AS pac_alergias,
                       m.nombre AS med_nombre,
                       m.cedula_profesional AS med_cedula,
                       m.especialidad AS med_especialidad
                FROM consultas c
                INNER JOIN pacientes p ON p.id = c.paciente_id AND p.deleted_at IS NULL
                LEFT  JOIN medicos  m ON m.id = c.medico_id  AND m.deleted_at IS NULL
                WHERE c.id = :id AND c.clinica_id = :cid AND c.deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'cid' => $this->clinicaId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
