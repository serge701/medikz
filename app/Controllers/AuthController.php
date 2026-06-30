<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Auditoria;

final class AuthController extends Controller
{
    /** GET /login */
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }
        $this->render('auth/login', [], 'guest');
    }

    /** POST /login */
    public function login(): void
    {
        Csrf::verify();

        $email = $this->input('email');
        $password = (string) $this->input('password');

        set_old(['email' => $email]);

        if ($email === '' || $password === '') {
            flash('error', 'Ingresa tu correo y contraseña.');
            redirect('login');
        }

        if (!Auth::attempt($email, $password)) {
            Auditoria::log('login.fallido', null, null, ['email' => $email]);
            flash('error', 'Credenciales incorrectas.');
            redirect('login');
        }

        clear_old();
        Auditoria::log('login.exito');
        redirect('dashboard');
    }

    /** POST /logout */
    public function logout(): void
    {
        Csrf::verify();
        Auditoria::log('logout');
        Auth::logout();
        redirect('login');
    }
}
