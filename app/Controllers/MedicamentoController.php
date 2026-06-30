<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Medicamento;

final class MedicamentoController extends Controller
{
    /** GET /medicamentos/buscar?q=... — responde JSON para autocomplete */
    public function buscar(): void
    {
        Auth::require();

        $q = trim((string) ($_GET['q'] ?? ''));
        if (strlen($q) < 2) {
            header('Content-Type: application/json; charset=utf-8');
            echo '[]';
            exit;
        }

        $filas = (new Medicamento())->buscar($q, 10);

        $resultado = array_map(function (array $m): array {
            return [
                'id'            => (int) $m['id'],
                'nombre'        => trim($m['nombre'] . ($m['concentracion'] ? ' ' . $m['concentracion'] : '')),
                'concentracion' => $m['concentracion'] ?? '',
                'presentacion'  => $m['presentacion']  ?? '',
                'categoria'     => $m['categoria']     ?? '',
            ];
        }, $filas);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
