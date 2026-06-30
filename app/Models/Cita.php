<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class Cita extends BaseModel
{
    protected string $table = 'citas';

    protected array $fillable = [
        'paciente_id', 'medico_id', 'fecha', 'hora_inicio', 'hora_fin',
        'motivo', 'estado', 'notas', 'motivo_cancelacion', 'creado_por',
    ];

    /**
     * Citas de un día con datos del paciente y del médico (JOIN).
     * @return array<int,array<string,mixed>>
     */
    public function porFecha(string $fecha): array
    {
        $sql = "SELECT c.*,
                       p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap,
                       p.apellido_materno AS pac_am, p.telefono AS pac_telefono,
                       m.nombre AS med_nombre
                FROM citas c
                INNER JOIN pacientes p ON p.id = c.paciente_id AND p.deleted_at IS NULL
                LEFT  JOIN medicos  m ON m.id = c.medico_id  AND m.deleted_at IS NULL
                WHERE c.clinica_id = :cid AND c.fecha = :fecha AND c.deleted_at IS NULL
                ORDER BY c.hora_inicio ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $this->clinicaId(), 'fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    /**
     * Historial de citas de un paciente (más recientes primero).
     * @return array<int,array<string,mixed>>
     */
    public function porPaciente(int $pacienteId, int $limit = 10): array
    {
        $sql = "SELECT c.*, m.nombre AS med_nombre
                FROM citas c
                LEFT JOIN medicos m ON m.id = c.medico_id AND m.deleted_at IS NULL
                WHERE c.clinica_id = :cid AND c.paciente_id = :pid AND c.deleted_at IS NULL
                ORDER BY c.fecha DESC, c.hora_inicio DESC
                LIMIT " . $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $this->clinicaId(), 'pid' => $pacienteId]);
        return $stmt->fetchAll();
    }

    /**
     * Próximas citas pendientes (para el dashboard).
     * @return array<int,array<string,mixed>>
     */
    public function proximas(int $limit = 5): array
    {
        $sql = "SELECT c.*,
                       p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap,
                       p.apellido_materno AS pac_am
                FROM citas c
                INNER JOIN pacientes p ON p.id = c.paciente_id AND p.deleted_at IS NULL
                WHERE c.clinica_id = :cid
                  AND c.deleted_at IS NULL
                  AND c.estado IN ('programada','confirmada')
                  AND CONCAT(c.fecha, ' ', c.hora_inicio) >= NOW()
                ORDER BY c.fecha ASC, c.hora_inicio ASC
                LIMIT " . $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $this->clinicaId()]);
        return $stmt->fetchAll();
    }

    /**
     * Detecta si un médico ya tiene una cita activa que se traslapa con el horario dado.
     * Usa lógica de intervalos: A y B se traslapan si A.inicio < B.fin Y A.fin > B.inicio.
     * $excluirId sirve para ignorar la cita actual al editar.
     */
    public function hayConflicto(
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int   $medicoId  = null,
        ?int   $excluirId = null
    ): bool {
        $sql = "SELECT COUNT(*) FROM citas
                WHERE clinica_id  = :cid
                  AND deleted_at  IS NULL
                  AND fecha       = :fecha
                  AND estado      NOT IN ('cancelada', 'atendida')
                  AND hora_inicio < :hora_fin
                  AND hora_fin    > :hora_inicio";

        $params = [
            'cid'        => $this->clinicaId(),
            'fecha'      => $fecha,
            'hora_inicio'=> $horaInicio,
            'hora_fin'   => $horaFin,
        ];

        if ($medicoId !== null) {
            $sql .= " AND medico_id = :medico_id";
            $params['medico_id'] = $medicoId;
        }
        if ($excluirId !== null) {
            $sql .= " AND id != :excluir_id";
            $params['excluir_id'] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Citas en un rango de fechas con datos de paciente y médico (para FullCalendar).
     * @return array<int,array<string,mixed>>
     */
    public function porRango(string $inicio, string $fin): array
    {
        $sql = "SELECT c.*,
                       p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap,
                       p.apellido_materno AS pac_am,
                       m.nombre AS med_nombre
                FROM citas c
                INNER JOIN pacientes p ON p.id = c.paciente_id AND p.deleted_at IS NULL
                LEFT  JOIN medicos  m ON m.id = c.medico_id  AND m.deleted_at IS NULL
                WHERE c.clinica_id = :cid
                  AND c.fecha BETWEEN :inicio AND :fin
                  AND c.deleted_at IS NULL
                ORDER BY c.fecha ASC, c.hora_inicio ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cid' => $this->clinicaId(), 'inicio' => $inicio, 'fin' => $fin]);
        return $stmt->fetchAll();
    }

    public function cambiarEstado(int $id, string $estado, ?string $motivo = null): bool
    {
        $extra  = '';
        $params = ['estado' => $estado, 'id' => $id, 'cid' => $this->clinicaId()];
        if ($motivo !== null) {
            $extra            = ', motivo_cancelacion = :motivo';
            $params['motivo'] = $motivo;
        }
        $stmt = $this->db->prepare(
            "UPDATE citas SET estado = :estado{$extra}
             WHERE id = :id AND clinica_id = :cid AND deleted_at IS NULL"
        );
        return $stmt->execute($params);
    }
}
