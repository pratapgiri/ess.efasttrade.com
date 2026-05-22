<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

class SuperAdminRoleOrPermissionMiddleware extends RoleOrPermissionMiddleware
{
    public function handle($request, Closure $next, $roleOrPermission, $guard = null)
    {
        $authGuard = Auth::guard($guard);
        $user = $authGuard->user();

        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        return parent::handle($request, $next, $roleOrPermission, $guard);
    }
}
