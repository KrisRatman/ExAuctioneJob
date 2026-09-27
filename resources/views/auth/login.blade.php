<x-layout title="Вход">
    <div class="mx-auto max-w-md px-4 py-8 sm:py-14">
        <h1 class="text-center text-2xl font-extrabold sm:text-3xl tracking-tight">Вход</h1>
        <p class="mt-2 text-center text-slate-500">Нет аккаунта? <a href="{{ route('register') }}" class="font-bold text-brand-600 hover:text-brand-700">Зарегистрируйтесь</a></p>

        <form method="post" action="{{ route('login') }}" class="card mt-6 space-y-5 p-5 sm:mt-8 sm:p-6">
            @csrf
            <x-field label="Email" for="email">
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
            </x-field>
            <x-field label="Пароль" for="password">
                <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
            </x-field>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 accent-brand-600"> Запомнить меня
            </label>
            <button type="submit" class="btn-primary w-full py-3">Войти</button>
        </form>

        @if (config('ideajob.demo'))
            <div class="card mt-6 p-5" data-demo>
                <div class="text-sm font-bold">Демо-стенд: войти одним кликом</div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    @foreach (['customer' => ['Заказчик', 'heroicon-o-clipboard-document-list'], 'executor' => ['Исполнитель', 'heroicon-o-wrench-screwdriver']] as $role => [$label, $icon])
                        <form method="post" action="{{ route('demo.login', $role) }}">
                            @csrf
                            <button type="submit" class="btn-secondary w-full">
                                <x-dynamic-component :component="$icon" class="size-4 text-slate-400" /> {{ $label }}
                            </button>
                        </form>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Или вручную, пароль у всех <code>password</code>: <code>customer@example.com</code>, <code>executor@example.com</code>.
                    Админка — <a href="{{ url('/admin') }}" class="font-semibold text-brand-600">/admin</a>, <code>admin@example.com</code>.
                </p>
            </div>
        @endif
    </div>
</x-layout>
