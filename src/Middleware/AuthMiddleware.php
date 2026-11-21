<?php
declare(strict_types=1);

namespace ApexGuild\Middleware;

use ApexGuild\Core\Session;
use ApexGuild\Core\Router;

/**
 * Authentication Middleware
 * Enforces authenticated active session across protected enterprise application routes.
 */
class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Session::isLoggedIn()) {
            if (str_starts_with($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                Router::json(['error' => 'Unauthenticated session', 'code' => 401], 401);
            }
            Session::flash('warning', 'Please sign in to access enterprise workflows.');
            Router::redirect('/login');
            return false;
        }

        return true;
    }
}
