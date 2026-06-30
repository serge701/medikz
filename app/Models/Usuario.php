<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Usuarios del sistema (médicos, recepción, admin de clínica).
 * El email es único globalmente: al iniciar sesión, el email determina a qué
 * clínica pertenece el usuario. Por eso findByEmail NO filtra por tenant.
 */
final class Usuario extends BaseModel
{
    protected string $table = 'usuarios';

    protected array $fillable = [
        'clinica_id', 'nombre', 'email', 'password_hash',
        'rol', 'es_propietario', 'activo',
    ];

    /** Búsqueda global por email para el login (sin scope de clínica). */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE email = :email AND deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function touchLastLogin(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET last_login = NOW() WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET password_hash = :h WHERE id = :id"
        );
        $stmt->execute(['h' => $hash, 'id' => $id]);
    }

    /** Lista todos los usuarios de la clínica con su perfil médico (LEFT JOIN). */
    public function deClinica(): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, m.id AS medico_id, m.cedula_profesional, m.especialidad
             FROM usuarios u
             LEFT JOIN medicos m ON m.usuario_id = u.id
                 AND m.deleted_at IS NULL AND m.clinica_id = u.clinica_id
             WHERE u.clinica_id = :cid AND u.deleted_at IS NULL
             ORDER BY u.es_propietario DESC, u.nombre ASC"
        );
        $stmt->execute(['cid' => $this->clinicaId()]);
        return $stmt->fetchAll();
    }

    /** Comprueba si un email existe en CUALQUIER clínica (para registro público). */
    public function existeEmailGlobal(string $email): bool
    {
        return $this->existeEmail($email);
    }

    /** Comprueba si un email ya existe (globalmente, excluyendo opcionalmente un id). */
    public function existeEmail(string $email, ?int $excludeId = null): bool
    {
        $sql    = "SELECT 1 FROM usuarios WHERE email = :email AND deleted_at IS NULL";
        $params = ['email' => $email];
        if ($excludeId !== null) {
            $sql .= " AND id != :eid";
            $params['eid'] = $excludeId;
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }
}
