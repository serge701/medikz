<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PacienteController;
use App\Controllers\CitaController;
use App\Controllers\ConsultaController;
use App\Controllers\RecetaController;
use App\Controllers\CobroController;
use App\Controllers\MetricasController;
use App\Controllers\UsuarioController;
use App\Controllers\RegistroController;
use App\Controllers\SuscripcionController;
use App\Controllers\MedicamentoController;
use App\Controllers\ModuleController;
use App\Controllers\SuperAdminController;

/**
 * Tabla de rutas. Cada ruta apunta a [Controlador::class, 'metodo'].
 * Las rutas se escriben SIN la subcarpeta (el Router ya la resta).
 */
return function (Router $router): void {

    // --- Autenticación y registro público ---
    $router->get('/login',    [AuthController::class, 'showLogin']);
    $router->post('/login',   [AuthController::class, 'login']);
    $router->post('/logout',  [AuthController::class, 'logout']);
    $router->get('/registro', [RegistroController::class, 'show']);
    $router->post('/registro',[RegistroController::class, 'store']);

    // --- SaaS · Suscripción ---
    $router->get('/suscripcion',          [SuscripcionController::class, 'index']);
    $router->post('/suscripcion/checkout',[SuscripcionController::class, 'checkout']);
    $router->get('/suscripcion/exito',    [SuscripcionController::class, 'exito']);

    // --- Webhook Stripe (sin CSRF, sin auth) ---
    $router->post('/webhook/stripe', [SuscripcionController::class, 'webhook']);

    // --- Super Admin (solo superadmin=1) ---
    $router->get('/superadmin',                              [SuperAdminController::class, 'index']);
    $router->get('/superadmin/clinicas/nueva',               [SuperAdminController::class, 'create']);
    $router->post('/superadmin/clinicas',                    [SuperAdminController::class, 'store']);
    $router->get('/superadmin/clinicas/{id}/editar',         [SuperAdminController::class, 'edit']);
    $router->post('/superadmin/clinicas/{id}',               [SuperAdminController::class, 'update']);

    // --- Catálogo (JSON endpoints) ---
    $router->get('/medicamentos/buscar', [MedicamentoController::class, 'buscar']);

    // --- Inicio ---
    $router->get('/',          [DashboardController::class, 'index']);
    $router->get('/dashboard', [DashboardController::class, 'index']);

    // --- Pacientes (módulo real) ---
    // OJO con el orden: las rutas estáticas van ANTES que /pacientes/{id},
    // porque {id} también casaría con "buscar" o "nuevo".
    $router->get('/pacientes',                 [PacienteController::class, 'index']);
    $router->get('/pacientes/buscar',          [PacienteController::class, 'buscar']);
    $router->get('/pacientes/nuevo',           [PacienteController::class, 'create']);
    $router->post('/pacientes',                [PacienteController::class, 'store']);
    $router->get('/pacientes/{id}/editar',     [PacienteController::class, 'edit']);
    $router->post('/pacientes/{id}/eliminar',  [PacienteController::class, 'destroy']);
    $router->get('/pacientes/{id}',            [PacienteController::class, 'show']);
    $router->post('/pacientes/{id}',           [PacienteController::class, 'update']);

    // --- Agenda y citas ---
    $router->get('/agenda',                  [CitaController::class, 'index']);
    $router->get('/agenda/eventos',          [CitaController::class, 'eventos']);
    $router->get('/agenda/nueva',            [CitaController::class, 'create']);
    $router->post('/agenda',                 [CitaController::class, 'store']);
    $router->get('/agenda/{id}/editar',      [CitaController::class, 'edit']);
    $router->post('/agenda/{id}/cancelar',   [CitaController::class, 'cancelar']);
    $router->post('/agenda/{id}/atender',    [CitaController::class, 'atender']);
    $router->post('/agenda/{id}/mover',      [CitaController::class, 'mover']);
    $router->get('/agenda/{id}',             [CitaController::class, 'show']);
    $router->post('/agenda/{id}',            [CitaController::class, 'update']);

    // --- Historial clínico / Consultas ---
    $router->get('/consultas',                  [ConsultaController::class, 'index']);
    $router->get('/consultas/nueva',            [ConsultaController::class, 'create']);
    $router->post('/consultas',                 [ConsultaController::class, 'store']);
    $router->get('/consultas/{id}/editar',      [ConsultaController::class, 'edit']);
    $router->post('/consultas/{id}/eliminar',   [ConsultaController::class, 'destroy']);
    $router->get('/consultas/{id}',             [ConsultaController::class, 'show']);
    $router->post('/consultas/{id}',            [ConsultaController::class, 'update']);

    // --- Recetas digitales ---
    // /recetas/verificar/{codigo} va ANTES que /{id} para no confundirse.
    $router->get('/recetas',                        [RecetaController::class, 'index']);
    $router->get('/recetas/nueva',                  [RecetaController::class, 'create']);
    $router->post('/recetas',                       [RecetaController::class, 'store']);
    $router->get('/recetas/verificar/{codigo}',     [RecetaController::class, 'verificar']);
    $router->get('/recetas/{id}/editar',            [RecetaController::class, 'edit']);
    $router->get('/recetas/{id}/pdf',               [RecetaController::class, 'pdf']);
    $router->post('/recetas/{id}/eliminar',         [RecetaController::class, 'destroy']);
    $router->get('/recetas/{id}',                   [RecetaController::class, 'show']);
    $router->post('/recetas/{id}',                  [RecetaController::class, 'update']);

    // --- Cobros / ventas ---
    $router->get('/cobros',                  [CobroController::class, 'index']);
    $router->get('/cobros/nuevo',            [CobroController::class, 'create']);
    $router->post('/cobros',                 [CobroController::class, 'store']);
    $router->get('/cobros/{id}/editar',      [CobroController::class, 'edit']);
    $router->post('/cobros/{id}/cancelar',   [CobroController::class, 'cancelar']);
    $router->post('/cobros/{id}/eliminar',   [CobroController::class, 'destroy']);
    $router->get('/cobros/{id}',             [CobroController::class, 'show']);
    $router->post('/cobros/{id}',            [CobroController::class, 'update']);

    // --- Métricas ---
    $router->get('/metricas', [MetricasController::class, 'index']);

    // --- Usuarios ---
    $router->get('/usuarios',                [UsuarioController::class, 'index']);
    $router->get('/usuarios/nuevo',          [UsuarioController::class, 'create']);
    $router->post('/usuarios',               [UsuarioController::class, 'store']);
    $router->get('/usuarios/{id}/editar',    [UsuarioController::class, 'edit']);
    $router->post('/usuarios/{id}',          [UsuarioController::class, 'update']);
    $router->post('/usuarios/{id}/password', [UsuarioController::class, 'changePassword']);
    $router->post('/usuarios/{id}/activar',  [UsuarioController::class, 'toggleActivo']);
};
