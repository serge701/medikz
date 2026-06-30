<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;
use PDO;

/**
 * Pacientes. Hereda el aislamiento por clinica_id del BaseModel.
 * Añade búsqueda instantánea y detección de posibles duplicados.
 */
final class Paciente extends BaseModel
{
    protected string $table = 'pacientes';

    protected array $fillable = [
        'nombre', 'apellido_paterno', 'apellido_materno', 'sexo', 'fecha_nacimiento',
        'curp', 'telefono', 'email', 'direccion', 'ciudad', 'estado', 'cp',
        'tipo_sangre', 'alergias', 'antecedentes', 'contacto_emergencia',
        'tel_emergencia', 'creado_por',
    ];

    /**
     * Búsqueda instantánea por nombre completo, teléfono o CURP.
     * Siempre acotada a la clínica de la sesión.
     *
     * @return array<int,array<string,mixed>>
     */
    public function buscar(string $q, int $limit = 25): array
    {
        $q = trim($q);

        $sql = "SELECT id, nombre, apellido_paterno, apellido_materno, sexo,
                       fecha_nacimiento, telefono, curp
                FROM {$this->table}
                WHERE clinica_id = :cid AND deleted_at IS NULL";

        $params = ['cid' => $this->clinicaId()];

        if ($q !== '') {
            $sql .= " AND (
                        CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE :t1
                        OR telefono LIKE :t2
                        OR curp LIKE :t3
                      )";
            $like = '%' . $q . '%';
            $params['t1'] = $like;
            $params['t2'] = $like;
            $params['t3'] = $like;
        }

        $sql .= " ORDER BY apellido_paterno, apellido_materno, nombre
                  LIMIT " . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Busca posibles duplicados por nombre completo exacto dentro de la clínica.
     * <=> es comparación segura ante NULL (para apellido materno vacío).
     *
     * @return array<int,array<string,mixed>>
     */
    public function duplicados(string $nombre, string $apPaterno, ?string $apMaterno): array
    {
        $sql = "SELECT id, nombre, apellido_paterno, apellido_materno, telefono, fecha_nacimiento
                FROM {$this->table}
                WHERE clinica_id = :cid AND deleted_at IS NULL
                  AND nombre = :n
                  AND apellido_paterno = :ap
                  AND (apellido_materno <=> :am)
                LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'cid' => $this->clinicaId(),
            'n'   => $nombre,
            'ap'  => $apPaterno,
            'am'  => ($apMaterno === '' ? null : $apMaterno),
        ]);
        return $stmt->fetchAll();
    }

    public function contar(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table}
             WHERE clinica_id = :cid AND deleted_at IS NULL"
        );
        $stmt->execute(['cid' => $this->clinicaId()]);
        return (int) $stmt->fetchColumn();
    }
}
