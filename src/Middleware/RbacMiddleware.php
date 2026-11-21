<?php
declare(strict_types=1);

namespace ApexGuild\Middleware;

use ApexGuild\Core\Session;
use ApexGuild\Core\Router;

/**
 * Role-Based Access Control Middleware
 * Enforces role authorization and granular permission policies across system operations.
 */
class RbacMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles = [])
    {
        $this->allowedRoles = $allowedRoles;
    }

    /**
     * Factory constructor for route middleware declaration
     */
    public static function allow(string ...$roles): self
    {
        return new self($roles);
    }

    public function handle(): bool
    {
        if (!Session::isLoggedIn()) {
            Session::flash('warning', 'Please sign in to proceed.');
            Router::redirect('/login');
            return false;
        }

        $userRole = Session::role();

        if (!empty($this->allowedRoles) && !in_array($userRole, $this->allowedRoles, true)) {
            if (str_starts_with($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                Router::json([
                    'error'    => 'Forbidden: Insufficient privileges for this operation',
                    'required' => $this->allowedRoles,
                    'current'  => $userRole,
                    'code'     => 403
                ], 403);
            }

            Session::flash('danger', "Access Restricted: This resource requires [" . implode('/', $this->allowedRoles) . "] permissions. Your current role is [{$userRole}].");
            Router::redirect('/dashboard');
            return false;
        }

        return true;
    }
}
