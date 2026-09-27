<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Вход в демо-аккаунт одной кнопкой — только на демо-стенде (IDEAJOB_DEMO=true).
 */
class DemoLoginController extends Controller
{
    /** Роль → почта демо-аккаунта из DemoSeeder. */
    public const ACCOUNTS = [
        'customer' => 'customer@example.com',
        'executor' => 'executor@example.com',
    ];

    public function __invoke(Request $request, string $role): RedirectResponse
    {
        abort_unless(config('ideajob.demo') && isset(self::ACCOUNTS[$role]), 404);

        $user = User::query()->where('email', self::ACCOUNTS[$role])->firstOrFail();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect($user->homeUrl());
    }
}
