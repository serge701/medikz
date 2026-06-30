<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use InvalidArgumentException;

/**
 * Modelo base. AQUÍ vive la defensa #1 del multi-tenant:
 *
 * Toda consulta (find, all, create, update, delete) inyecta automáticamente
 * el clinica_id de la sesión. Es IMPOSIBLE olvidar el filtro y filtrar datos
 * de una clínica a otra, porque los modelos nunca consultan "crudo": pasan
 * siempre por estos métodos.
 *
 * Además:
 * - Solo se aceptan columnas declaradas en $fillable (anti mass-assignment).
 * - Borrado lógico por defecto (deleted_at). Nunca se borran datos clínicos.
 */
abstract class BaseModel
{
    protected PDO $db;

    /** Nombre de la tabla. */
    protected string $table;

    /** Columnas que create()/update() pueden tocar. */
    protected array $fillable = [];

    /** Si la tabla está aislada por clínica (casi todas). */
    protected bool $tenantScoped = true;

    /** Si la tabla usa borrado lógico (deleted_at). */
    protected bool $softDelete = true;

    public function __construct()
    {
        $this->db = Database::conn();
    }

    protected function clinicaId(): int
    {
        return Tenant::clinicaId();
    }

    /** Busca un registro por id dentro de la clínica actual. */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $params = ['id' => $id];

        if ($this->tenantScoped) {
            $sql .= " AND clinica_id = :clinica_id";
            $params['clinica_id'] = $this->clinicaId();
        }
        if ($this->softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Lista registros. $where es un arreglo columna=>valor (igualdad exacta).
     * Las columnas se validan contra una lista blanca.
     *
     * @param array<string,mixed> $where
     * @return array<int,array<string,mixed>>
     */
    public function all(array $where = [], string $orderBy = 'id DESC', int $limit = 200): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if ($this->tenantScoped) {
            $sql .= " AND clinica_id = :clinica_id";
            $params['clinica_id'] = $this->clinicaId();
        }
        if ($this->softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }

        foreach ($where as $col => $val) {
            $this->assertColumn($col);
            $sql .= " AND {$col} = :w_{$col}";
            $params["w_{$col}"] = $val;
        }

        $sql .= ' ORDER BY ' . $this->safeOrderBy($orderBy);
        $sql .= ' LIMIT ' . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** Crea un registro inyectando clinica_id. Devuelve el id nuevo. */
    public function create(array $data): int
    {
        $data = $this->onlyFillable($data);

        if ($this->tenantScoped) {
            $data['clinica_id'] = $this->clinicaId();
        }

        $cols = array_keys($data);
        $placeholders = array_map(static fn($c) => ":{$c}", $cols);

        $sql = sprintf(
            "INSERT INTO {$this->table} (%s) VALUES (%s)",
            implode(', ', $cols),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    /** Actualiza un registro dentro de la clínica actual. */
    public function update(int $id, array $data): bool
    {
        $data = $this->onlyFillable($data);
        if ($data === []) {
            return false;
        }

        $sets = array_map(static fn($c) => "{$c} = :{$c}", array_keys($data));

        $sql = sprintf("UPDATE {$this->table} SET %s WHERE id = :id", implode(', ', $sets));
        $params = $data + ['id' => $id];

        if ($this->tenantScoped) {
            $sql .= " AND clinica_id = :clinica_id";
            $params['clinica_id'] = $this->clinicaId();
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Borrado lógico (recomendado para datos clínicos) o físico si softDelete=false. */
    public function delete(int $id): bool
    {
        if ($this->softDelete) {
            $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id";
        } else {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
        }
        $params = ['id' => $id];

        if ($this->tenantScoped) {
            $sql .= " AND clinica_id = :clinica_id";
            $params['clinica_id'] = $this->clinicaId();
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // ---------- internos ----------

    protected function onlyFillable(array $data): array
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function assertColumn(string $col): void
    {
        $allowed = array_merge($this->fillable, ['id', 'clinica_id', 'created_at', 'activo']);
        if (!in_array($col, $allowed, true)) {
            throw new InvalidArgumentException("Columna no permitida en consulta: {$col}");
        }
    }

    protected function safeOrderBy(string $orderBy): string
    {
        // Permite solo "columna ASC|DESC" con columnas conocidas.
        if (preg_match('/^([a-zA-Z_]+)\s*(ASC|DESC)?$/i', trim($orderBy), $m)) {
            $col = $m[1];
            $dir = strtoupper($m[2] ?? 'ASC');
            $allowed = array_merge($this->fillable, ['id', 'created_at', 'updated_at']);
            if (in_array($col, $allowed, true)) {
                return "{$col} {$dir}";
            }
        }
        return 'id DESC';
    }
}
