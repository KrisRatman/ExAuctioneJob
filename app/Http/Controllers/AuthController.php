<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use App\Support\TagRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Неверный email или пароль.']);
        }

        $request->session()->regenerate();

        return redirect()->intended($request->user()->homeUrl());
    }

    public function showRegister(Request $request): View
    {
        return view('auth.register', [
            'role' => $request->query('role') === UserRole::Executor->value ? UserRole::Executor : UserRole::Customer,
            'tree' => Category::tree(),
        ]);
    }

    /**
     * Регистрация заказчика или исполнителя. Исполнитель сразу заполняет анкету и теги —
     * без них лента заказов для него пуста.
     */
    public function register(Request $request): RedirectResponse
    {
        $isExecutor = $request->input('role') === UserRole::Executor->value;

        $data = $request->validate([
            'role' => ['required', Rule::in([UserRole::Customer->value, UserRole::Executor->value])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[\d\s()\-]{10,20}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'description' => [Rule::requiredIf($isExecutor), 'nullable', 'string', 'min:30', 'max:3000'],
            'categories' => $isExecutor ? TagRules::executor() : ['prohibited'],
            'categories.*' => TagRules::each(),
        ], [
            'phone.regex' => 'Укажите телефон в формате +7 900 000-00-00.',
            'categories.required' => 'Отметьте хотя бы одну специализацию — по ним подбираются заказы.',
        ]);

        $user = DB::transaction(function () use ($data, $isExecutor) {
            $user = User::query()->create([
                'role' => $data['role'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);

            if ($isExecutor) {
                $user->executorProfile()->create(['description' => $data['description']]);
                $user->categories()->attach($data['categories']);
            }

            return $user;
        });

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect($user->homeUrl());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
