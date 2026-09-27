<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пускает только пользователей с нужной ролью: `role:customer`, `role:customer,executor`.
 * Остальных отправляет в их собственный кабинет.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (! in_array($user->role->value, $roles, true)) {
            return redirect($user->homeUrl());
        }

        return $next($request);
    }
}
