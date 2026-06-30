<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;

/**
 * Placeholder temporal para módulos que aún no construimos.
 * Cada uno se irá reemplazando por su controlador real, módulo por módulo.
 */
final class ModuleController extends Controller
{
    public function pacientes(): void   { $this->stub('Pacientes', 'people'); }
    public function agenda(): void      { $this->stub('Agenda y citas', 'calendar-week'); }
    public function cobros(): void      { $this->stub('Cobros', 'cash-coin'); }

    public function consultas(): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        $this->stub('Historial clínico', 'clipboard2-pulse');
    }

    public function recetas(): void
    {
        Auth::requireRole('medico', 'admin_clinica');
        $this->stub('Recetas', 'prescription2');
    }

    private function stub(string $titulo, string $icono): void
    {
        Auth::require();
        $this->render('placeholder', ['titulo' => $titulo, 'icono' => $icono], 'app');
    }
}
