<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class Medico extends BaseModel
{
    protected string $table = 'medicos';

    protected array $fillable = [
        'usuario_id', 'nombre', 'cedula_profesional', 'especialidad',
        'cedula_especialidad', 'universidad', 'telefono', 'activo',
    ];

    /** Lista de médicos activos de la clínica. */
    public function activos(): array
    {
        return $this->all(['activo' => 1], 'nombre ASC', 50);
    }

    /** Perfil médico vinculado a un usuario. */
    public function porUsuario(int $usuarioId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM medicos
             WHERE clinica_id = :cid AND usuario_id = :uid AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute(['cid' => $this->clinicaId(), 'uid' => $usuarioId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
