<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пускает только пользователей с нужной ролью: `role:customer`, `role:executor`.
 * Остальных отправляет в их собственный кабинет.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if ($user->role->value !== $role) {
            return redirect($user->homeUrl());
        }

        return $next($request);
    }
}
