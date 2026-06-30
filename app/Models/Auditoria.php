<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Bitácora de auditoría. Inserción directa (no usa create() del BaseModel
 * porque incluye columnas de sistema y nunca se edita ni se borra).
 */
final class Auditoria extends BaseModel
{
    protected string $table = 'auditoria';
    protected bool $tenantScoped = false;
    protected bool $softDelete = false;

    public function registrar(array $data): void
    {
        $sql = "INSERT INTO {$this->table}
                (clinica_id, usuario_id, accion, entidad, entidad_id, detalle, ip, user_agent, created_at)
                VALUES
                (:clinica_id, :usuario_id, :accion, :entidad, :entidad_id, :detalle, :ip, :user_agent, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'clinica_id' => $data['clinica_id'] ?? null,
            'usuario_id' => $data['usuario_id'] ?? null,
            'accion'     => $data['accion'],
            'entidad'    => $data['entidad'] ?? null,
            'entidad_id' => $data['entidad_id'] ?? null,
            'detalle'    => $data['detalle'] ?? null,
            'ip'         => $data['ip'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }
}
