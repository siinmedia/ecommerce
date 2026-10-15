<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Helpers;
use App\Core\Validator;
use App\Core\View;

class AuthController
{
    private array $config;

    public function __construct()
    {
        $this->config = require Database::configPath();
    }

    private function base(string $title): array
    {
        return [
            'title' => $title, 'user' => Auth::user(), 'config' => $this->config,
            'flash_success' => Helpers::flash('success'),
            'flash_error' => Helpers::flash('error'),
        ];
    }

    public function showLogin(array $p = []): void
    {
        if (Auth::check()) {
            Helpers::redirect(Auth::role() === 'admin' ? '/admin' : '/');
        }
        View::layout('auth/login', $this->base('Masuk'));
    }

    public function login(array $p = []): void
    {
        Csrf::requireValid();
        // Basic rate limit: max 10 attempts / 10 min per IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $key = 'login_' . md5($ip);
        $_SESSION[$key] = $_SESSION[$key] ?? ['count' => 0, 'until' => 0];
        if ($_SESSION[$key]['count'] >= 10 && time() < $_SESSION[$key]['until']) {
            Helpers::flash('error', 'Terlalu banyak percobaan. Coba lagi nanti.');
            Helpers::redirect('/login');
        }
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass = $_POST['password'] ?? '';
        $user = \App\Models\User::findByEmail($email);
        // Generic message to avoid account enumeration
        if (!$user || $user['status'] !== 'active' || !password_verify($pass, $user['password_hash'])) {
            $_SESSION[$key]['count']++;
            $_SESSION[$key]['until'] = time() + 600;
            Helpers::keepOld(['email' => $email]);
            Helpers::flash('error', 'Email atau password salah.');
            Helpers::redirect('/login');
        }
        unset($_SESSION[$key]);
        Auth::login($user);
        CartController::mergeAfterLogin((int)$user['id']);
        Helpers::clearOld();
        $next = $_SESSION['redirect_after_login'] ?? '/';
        unset($_SESSION['redirect_after_login']);
        if (!str_starts_with($next, '/')) {
            $next = '/';
        }
        Helpers::redirect($next);
    }

    public function showRegister(array $p = []): void
    {
        if (Auth::check()) {
            Helpers::redirect('/');
        }
        View::layout('auth/register', $this->base('Daftar'));
    }

    public function register(array $p = []): void
    {
        Csrf::requireValid();
        $v = new Validator($_POST);
        $v->required('name', 'Nama')->required('email', 'Email')->email('email')
            ->required('password', 'Password')->minLength('password', 8, 'Password');
        Helpers::keepOld(['name' => $_POST['name'] ?? '', 'email' => $_POST['email'] ?? '']);
        if (!$v->passes()) {
            Helpers::flash('error', implode(' ', $v->errors()));
            Helpers::redirect('/register');
        }
        $email = strtolower(trim($_POST['email']));
        if (\App\Models\User::findByEmail($email)) {
            Helpers::flash('error', 'Email atau password salah.');
            Helpers::redirect('/register');
        }
        $id = \App\Models\User::create(trim($_POST['name']), $email, Auth::hashPassword($_POST['password']));
        $user = \App\Models\User::find($id);
        Auth::login($user);
        CartController::mergeAfterLogin($id);
        Helpers::clearOld();
        Helpers::flash('success', 'Akun dibuat. Selamat belanja!');
        $next = $_SESSION['redirect_after_login'] ?? '/';
        unset($_SESSION['redirect_after_login']);
        Helpers::redirect(str_starts_with($next, '/') ? $next : '/');
    }

    public function logout(array $p = []): void
    {
        Csrf::requireValid();
        Auth::logout();
        Helpers::redirect('/');
    }
}
