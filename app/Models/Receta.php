<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class Receta extends BaseModel
{
    protected string $table = 'recetas';

    protected array $fillable = [
        'paciente_id', 'medico_id', 'consulta_id', 'fecha_receta',
        'diagnostico', 'medicamentos', 'indicaciones_generales',
        'codigo_verificacion', 'creado_por',
    ];

    /**
     * Recetas recientes con datos de paciente y médico.
     * @return array<int,array<string,mixed>>
     */
    public function recientes(int $limit = 50, ?int $pacienteId = null): array
    {
        $filtro = $pacienteId !== null ? ' AND r.paciente_id = :pid' : '';
        $params = ['cid' => $this->clinicaId()];
        if ($pacienteId !== null) {
            $params['pid'] = $pacienteId;
        }

        $sql = "SELECT r.*,
                       p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap,
                       p.apellido_materno AS pac_am,
                       m.nombre AS med_nombre
                FROM recetas r
                INNER JOIN pacientes p ON p.id = r.paciente_id AND p.deleted_at IS NULL
                LEFT  JOIN medicos  m ON m.id = r.medico_id  AND m.deleted_at IS NULL
                WHERE r.clinica_id = :cid AND r.deleted_at IS NULL{$filtro}
                ORDER BY r.fecha_receta DESC, r.created_at DESC
                LIMIT " . $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Receta con todos los datos para PDF y vista de detalle.
     */
    public function conDetalle(int $id): ?array
    {
        $sql = "SELECT r.*,
                       p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap,
                       p.apellido_materno AS pac_am,
                       p.fecha_nacimiento AS pac_nacimiento,
                       p.sexo AS pac_sexo,
                       m.nombre AS med_nombre,
                       m.cedula_profesional AS med_cedula,
                       m.especialidad AS med_especialidad,
                       m.universidad AS med_universidad
                FROM recetas r
                INNER JOIN pacientes p ON p.id = r.paciente_id AND p.deleted_at IS NULL
                LEFT  JOIN medicos  m ON m.id = r.medico_id  AND m.deleted_at IS NULL
                WHERE r.id = :id AND r.clinica_id = :cid AND r.deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'cid' => $this->clinicaId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Búsqueda pública por código de verificación (sin filtro de clínica).
     */
    public function porCodigo(string $codigo): ?array
    {
        $sql = "SELECT r.*,
                       p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap,
                       p.apellido_materno AS pac_am,
                       p.fecha_nacimiento AS pac_nacimiento,
                       m.nombre AS med_nombre,
                       m.cedula_profesional AS med_cedula,
                       m.especialidad AS med_especialidad,
                       c.nombre AS clinica_nombre, c.telefono AS clinica_telefono
                FROM recetas r
                INNER JOIN pacientes p ON p.id = r.paciente_id
                LEFT  JOIN medicos  m ON m.id = r.medico_id
                INNER JOIN clinicas c ON c.id = r.clinica_id
                WHERE r.codigo_verificacion = :codigo AND r.deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['codigo' => $codigo]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function generarCodigo(): string
    {
        do {
            $codigo = strtoupper(bin2hex(random_bytes(5)));
            $existe = $this->db->prepare("SELECT id FROM recetas WHERE codigo_verificacion = :c LIMIT 1");
            $existe->execute(['c' => $codigo]);
        } while ($existe->fetchColumn());

        return $codigo;
    }
}
