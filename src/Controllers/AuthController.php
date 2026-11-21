<?php
declare(strict_types=1);

namespace ApexGuild\Controllers;

use ApexGuild\Core\Database;
use ApexGuild\Core\Session;
use ApexGuild\Core\Router;

/**
 * Authentication & Role Switching Controller
 */
class AuthController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Render Login View with 1-Click Demo Profiles
     */
    public function showLogin(): void
    {
        if (Session::isLoggedIn()) {
            Router::redirect('/dashboard');
            return;
        }

        $users = $this->db->fetchAll("SELECT id, name, email, role, department FROM users ORDER BY id ASC");

        Router::render('auth/login', [
            'title'     => 'Enterprise Sign-In - ApexGuild Enterprise',
            'demoUsers' => $users,
            'flashes'   => Session::getFlashes(),
            'csrfToken' => Session::csrfToken()
        ], null); // Render standalone without main layout
    }

    /**
     * Authenticate via Email & Password
     */
    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrf     = $_POST['_csrf'] ?? '';

        if (!Session::validateCsrf($csrf)) {
            Session::flash('danger', 'Security validation expired. Please try again.');
            Router::redirect('/login');
            return;
        }

        if (empty($email) || empty($password)) {
            Session::flash('danger', 'Please enter your corporate email address and password.');
            Router::redirect('/login');
            return;
        }

        $user = $this->db->fetch("SELECT * FROM users WHERE email = ?", [$email]);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::flash('danger', 'Invalid corporate credentials. Try using one-click demo login below.');
            Router::redirect('/login');
            return;
        }

        Session::login($user);
        Session::flash('success', "Welcome back, {$user['name']}! Active role: {$user['role']}.");
        Router::redirect('/dashboard');
    }

    /**
     * One-Click Demo Role Switcher
     */
    public function switchRole(): void
    {
        $role   = $_POST['role'] ?? $_GET['role'] ?? null;
        $userId = $_POST['user_id'] ?? $_GET['user_id'] ?? null;

        if ($userId) {
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [(int)$userId]);
        } elseif ($role) {
            $user = $this->db->fetch("SELECT * FROM users WHERE role = ? LIMIT 1", [$role]);
        } else {
            $user = null;
        }

        if (!$user) {
            Session::flash('danger', 'Target demo profile could not be located.');
            Router::redirect('/dashboard');
            return;
        }

        Session::login($user);
        Session::flash('info', "Switched identity to <strong>{$user['name']}</strong> (Role: <span class=\"badge bg-primary\">{$user['role']}</span>).");

        $redirect = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? '/dashboard';
        Router::redirect($redirect);
    }

    /**
     * Terminate Session
     */
    public function logout(): void
    {
        Session::logout();
        Session::flash('info', 'You have been securely signed out of ApexGuild Enterprise.');
        Router::redirect('/login');
    }
}
