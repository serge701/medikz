<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class Medicamento extends BaseModel
{
    protected string $table        = 'medicamentos';
    protected bool   $tenantScoped = false;
    protected bool   $softDelete   = false;

    protected array $fillable = ['nombre', 'concentracion', 'presentacion', 'categoria', 'activo'];

    /**
     * Búsqueda por nombre (LIKE). Devuelve máximo $limit resultados ordenados por nombre.
     * @return array<int,array<string,mixed>>
     */
    public function buscar(string $q, int $limit = 10): array
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
        $stmt = $this->db->prepare(
            "SELECT id, nombre, concentracion, presentacion, categoria
             FROM medicamentos
             WHERE activo = 1 AND nombre LIKE :q
             ORDER BY nombre ASC
             LIMIT " . $limit
        );
        $stmt->execute(['q' => $like]);
        return $stmt->fetchAll();
    }
}
