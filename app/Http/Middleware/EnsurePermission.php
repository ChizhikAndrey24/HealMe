<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $required = Permission::tryFrom($permission);

        abort_unless(
            $user !== null && $required !== null && $user->hasPermission($required),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
