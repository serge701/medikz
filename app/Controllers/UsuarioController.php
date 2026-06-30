<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Auditoria;
use App\Models\Usuario;
use App\Models\Medico;

final class UsuarioController extends Controller
{
    private Usuario $usuarios;
    private Medico  $medicos;

    public function __construct()
    {
        $this->usuarios = new Usuario();
        $this->medicos  = new Medico();
    }

    /** GET /usuarios */
    public function index(): void
    {
        Auth::require();
        $this->soloAdmin();

        $this->render('usuarios/index', [
            'pageTitle' => 'Usuarios',
            'lista'     => $this->usuarios->deClinica(),
        ]);
    }

    /** GET /usuarios/nuevo */
    public function create(): void
    {
        Auth::require();
        $this->soloAdmin();
        clear_old();

        $this->render('usuarios/form', [
            'pageTitle'       => 'Nuevo usuario',
            'usuario'         => null,
            'medico'          => null,
            'errores'         => [],
            'erroresPassword' => [],
            'rolesPermitidos' => $this->rolesPermitidos(),
        ]);
    }

    /** POST /usuarios */
    public function store(): void
    {
        Auth::require();
        $this->soloAdmin();
        Csrf::verify();

        $data    = $this->datosDesdePost();
        $errores = $this->validarCrear($data);

        if ($errores !== []) {
            set_old($_POST);
            $this->render('usuarios/form', [
                'pageTitle'       => 'Nuevo usuario',
                'usuario'         => null,
                'medico'          => null,
                'errores'         => $errores,
                'erroresPassword' => [],
                'rolesPermitidos' => $this->rolesPermitidos(),
            ]);
            return;
        }

        $uid = $this->usuarios->create([
            'nombre'        => $data['nombre'],
            'email'         => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'rol'           => $data['rol'],
            'es_propietario'=> 0,
            'activo'        => 1,
        ]);

        if ($data['rol'] === 'medico') {
            $this->guardarMedico($uid, $data, null);
        }

        Auditoria::log('usuario.crear', 'usuario', $uid, [
            'email' => $data['email'],
            'rol'   => $data['rol'],
        ]);
        clear_old();
        flash('success', 'Usuario creado correctamente.');
        redirect('usuarios');
    }

    /** GET /usuarios/{id}/editar */
    public function edit(array $params): void
    {
        Auth::require();
        $this->soloAdmin();

        $usuario = $this->or404((int) $params['id']);
        $medico  = $this->medicos->porUsuario((int) $usuario['id']);

        clear_old();
        $this->render('usuarios/form', [
            'pageTitle'       => 'Editar usuario',
            'usuario'         => $usuario,
            'medico'          => $medico,
            'errores'         => [],
            'erroresPassword' => [],
            'rolesPermitidos' => $this->rolesPermitidos(),
        ]);
    }

    /** POST /usuarios/{id} */
    public function update(array $params): void
    {
        Auth::require();
        $this->soloAdmin();
        Csrf::verify();

        $id      = (int) $params['id'];
        $usuario = $this->or404($id);
        $data    = $this->datosDesdePost();
        $errores = $this->validarEditar($data, $id);

        if ($errores !== []) {
            set_old($_POST);
            $this->render('usuarios/form', [
                'pageTitle'       => 'Editar usuario',
                'usuario'         => $usuario,
                'medico'          => $this->medicos->porUsuario($id),
                'errores'         => $errores,
                'erroresPassword' => [],
                'rolesPermitidos' => $this->rolesPermitidos(),
            ]);
            return;
        }

        $updates = ['nombre' => $data['nombre'], 'email' => $data['email']];
        if (!(int) $usuario['es_propietario']) {
            $updates['rol']    = $data['rol'];
            $updates['activo'] = $data['activo'];
        }
        $this->usuarios->update($id, $updates);

        $rolFinal = (int) $usuario['es_propietario'] ? $usuario['rol'] : $data['rol'];
        if ($rolFinal === 'medico') {
            $this->guardarMedico($id, $data, $this->medicos->porUsuario($id));
        }

        Auditoria::log('usuario.editar', 'usuario', $id);
        clear_old();
        flash('success', 'Usuario actualizado correctamente.');
        redirect('usuarios/' . $id . '/editar');
    }

    /** POST /usuarios/{id}/password */
    public function changePassword(array $params): void
    {
        Auth::require();
        $id = (int) $params['id'];

        if (Auth::id() !== $id && !Auth::is('admin_clinica') && !Auth::esPropietario()) {
            http_response_code(403); view('errors/403'); return;
        }
        Csrf::verify();

        $usuario = $this->or404($id);
        $nueva   = trim((string) ($_POST['password_nuevo']   ?? ''));
        $confirm = trim((string) ($_POST['password_confirm'] ?? ''));
        $errores = [];

        if (strlen($nueva) < 8) {
            $errores['password_nuevo'] = 'Mínimo 8 caracteres.';
        } elseif ($nueva !== $confirm) {
            $errores['password_confirm'] = 'Las contraseñas no coinciden.';
        }

        if ($errores !== []) {
            $this->render('usuarios/form', [
                'pageTitle'       => 'Editar usuario',
                'usuario'         => $usuario,
                'medico'          => $this->medicos->porUsuario($id),
                'errores'         => [],
                'erroresPassword' => $errores,
                'rolesPermitidos' => $this->rolesPermitidos(),
            ]);
            return;
        }

        $this->usuarios->updatePasswordHash($id, password_hash($nueva, PASSWORD_DEFAULT));
        Auditoria::log('usuario.cambiar_password', 'usuario', $id);
        flash('success', 'Contraseña actualizada.');
        redirect('usuarios/' . $id . '/editar');
    }

    /** POST /usuarios/{id}/activar — toggle activo */
    public function toggleActivo(array $params): void
    {
        Auth::require();
        $this->soloAdmin();
        Csrf::verify();

        $id      = (int) $params['id'];
        $usuario = $this->or404($id);

        if ((int) $usuario['es_propietario']) {
            flash('error', 'No se puede desactivar al propietario de la clínica.');
            redirect('usuarios');
            return;
        }
        if ($id === Auth::id()) {
            flash('error', 'No puedes desactivar tu propia cuenta.');
            redirect('usuarios');
            return;
        }

        $nuevo = (int) $usuario['activo'] === 1 ? 0 : 1;
        $this->usuarios->update($id, ['activo' => $nuevo]);
        Auditoria::log($nuevo ? 'usuario.activar' : 'usuario.desactivar', 'usuario', $id);
        flash('success', 'Usuario ' . ($nuevo ? 'activado' : 'desactivado') . '.');
        redirect('usuarios');
    }

    // ── internos ──────────────────────────────────────────────────────────

    private function soloAdmin(): void
    {
        if (!Auth::is('admin_clinica') && !Auth::esPropietario()) {
            http_response_code(403); view('errors/403'); exit;
        }
    }

    /**
     * Roles que el usuario actual puede asignar.
     * - admin_clinica → puede crear médicos, admins y recepcionistas.
     * - propietario con rol médico → solo puede gestionar su recepción.
     */
    private function rolesPermitidos(): array
    {
        if (Auth::is('admin_clinica')) {
            return ['medico', 'admin_clinica', 'recepcion'];
        }
        return ['recepcion'];
    }

    private function or404(int $id): array
    {
        $u = $this->usuarios->find($id);
        if ($u === null) { http_response_code(404); view('errors/404'); exit; }
        return $u;
    }

    private function datosDesdePost(): array
    {
        return [
            'nombre'                  => trim((string) ($_POST['nombre']                  ?? '')),
            'email'                   => strtolower(trim((string) ($_POST['email']        ?? ''))),
            'rol'                     => trim((string) ($_POST['rol']                     ?? 'recepcion')),
            'activo'                  => isset($_POST['activo']) ? 1 : 0,
            'password'                => (string) ($_POST['password']                     ?? ''),
            'password_confirm'        => (string) ($_POST['password_confirm']             ?? ''),
            'med_nombre'              => trim((string) ($_POST['med_nombre']              ?? '')),
            'med_cedula_profesional'  => trim((string) ($_POST['med_cedula_profesional']  ?? '')),
            'med_especialidad'        => trim((string) ($_POST['med_especialidad']        ?? '')),
            'med_cedula_especialidad' => trim((string) ($_POST['med_cedula_especialidad'] ?? '')),
            'med_universidad'         => trim((string) ($_POST['med_universidad']         ?? '')),
            'med_telefono'            => trim((string) ($_POST['med_telefono']            ?? '')),
        ];
    }

    private function validarCrear(array $d): array
    {
        $e = $this->validarBase($d);
        if ($d['password'] === '') {
            $e['password'] = 'La contraseña es obligatoria.';
        } elseif (strlen($d['password']) < 8) {
            $e['password'] = 'Mínimo 8 caracteres.';
        } elseif ($d['password'] !== $d['password_confirm']) {
            $e['password_confirm'] = 'Las contraseñas no coinciden.';
        }
        return $e;
    }

    private function validarEditar(array $d, int $id): array
    {
        return $this->validarBase($d, $id);
    }

    private function validarBase(array $d, ?int $excludeId = null): array
    {
        $e = [];
        if ($d['nombre'] === '') {
            $e['nombre'] = 'El nombre es obligatorio.';
        }
        if ($d['email'] === '') {
            $e['email'] = 'El email es obligatorio.';
        } elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Formato de email no válido.';
        } elseif ($this->usuarios->existeEmail($d['email'], $excludeId)) {
            $e['email'] = 'Este email ya está registrado.';
        }
        if (!in_array($d['rol'], $this->rolesPermitidos(), true)) {
            $e['rol'] = 'No tienes permiso para asignar ese rol.';
        }
        return $e;
    }

    private function guardarMedico(int $usuarioId, array $data, ?array $existente): void
    {
        $medicoData = [
            'nombre'              => $data['med_nombre'] !== '' ? $data['med_nombre'] : $data['nombre'],
            'cedula_profesional'  => $data['med_cedula_profesional']  ?: null,
            'especialidad'        => $data['med_especialidad']         ?: null,
            'cedula_especialidad' => $data['med_cedula_especialidad']  ?: null,
            'universidad'         => $data['med_universidad']          ?: null,
            'telefono'            => $data['med_telefono']             ?: null,
            'activo'              => 1,
        ];

        if ($existente !== null) {
            $this->medicos->update((int) $existente['id'], $medicoData);
        } else {
            $medicoData['usuario_id'] = $usuarioId;
            $this->medicos->create($medicoData);
        }
    }
}
