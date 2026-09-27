@props(['title' => null])

@php
    $user = auth()->user();
    $navLink = fn (bool $active) => $active
        ? 'rounded-lg px-3 py-2 text-sm font-bold text-brand-700 bg-brand-50'
        : 'rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-ink';
@endphp

<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name') }} — биржа заказов</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full flex-col bg-canvas font-sans text-ink antialiased">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur" x-data="{ menu: false }">
        <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
            <a href="{{ $user ? $user->homeUrl() : route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="{{ config('app.name') }}">
                <span class="grid size-9 place-items-center rounded-xl bg-brand-600 text-white">
                    <x-heroicon-s-light-bulb class="size-5" />
                </span>
                <span class="text-lg font-extrabold tracking-tight">Idea<span class="text-brand-600">Job</span></span>
            </a>

            <nav class="ml-4 hidden items-center gap-1 md:flex" aria-label="Основное меню">
                @if ($user?->isCustomer())
                    <a href="{{ route('customer.orders.index') }}" class="{{ $navLink(request()->routeIs('customer.orders.index', 'customer.orders.show')) }}">Мои заказы</a>
                @elseif ($user?->isExecutor())
                    <a href="{{ route('executor.feed') }}" class="{{ $navLink(request()->routeIs('executor.feed')) }}">Лента заказов</a>
                    <a href="{{ route('executor.bids') }}" class="{{ $navLink(request()->routeIs('executor.bids')) }}">Мои предложения</a>
                    <a href="{{ route('executor.profile') }}" class="{{ $navLink(request()->routeIs('executor.profile')) }}">Профиль</a>
                @endif
            </nav>

            <div class="ml-auto flex items-center gap-2">
                @auth
                    @if ($user->isCustomer())
                        <a href="{{ route('customer.orders.create') }}" class="btn-primary hidden sm:inline-flex">
                            <x-heroicon-o-plus class="size-4" stroke-width="2.5" /> Разместить заказ
                        </a>
                    @elseif ($user->isAdmin())
                        <a href="{{ url('/admin') }}" class="btn-secondary">Админка</a>
                    @endif

                    <div class="relative" @click.outside="menu = false">
                        <button type="button" @click="menu = !menu" class="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-slate-100" :aria-expanded="menu">
                            <span class="grid size-8 place-items-center rounded-full bg-slate-900 text-sm font-bold text-white">{{ mb_substr($user->name, 0, 1) }}</span>
                            <span class="hidden text-left leading-tight lg:block">
                                <span class="block text-sm font-bold">{{ $user->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $user->role->getLabel() }}</span>
                            </span>
                            <x-heroicon-m-chevron-down class="size-4 text-slate-400" />
                        </button>
                        <div x-show="menu" x-cloak x-transition.origin.top.right class="absolute right-0 top-full mt-2 w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                            @if ($user->isCustomer())
                                <a href="{{ route('customer.orders.index') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-50 md:hidden">Мои заказы</a>
                                <a href="{{ route('customer.orders.create') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-50 sm:hidden">Разместить заказ</a>
                            @elseif ($user->isExecutor())
                                <a href="{{ route('executor.feed') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-50 md:hidden">Лента заказов</a>
                                <a href="{{ route('executor.bids') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-50 md:hidden">Мои предложения</a>
                                <a href="{{ route('executor.profile') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-50 md:hidden">Профиль</a>
                            @endif
                            <form method="post" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                    <x-heroicon-o-arrow-right-start-on-rectangle class="size-4" /> Выйти
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary">Войти</a>
                    <a href="{{ route('register') }}" class="btn-primary hidden sm:inline-flex">Регистрация</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1">
        @if (session('status'))
            <div class="mx-auto mt-6 max-w-6xl px-4">
                <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                    <x-heroicon-o-check-circle class="mt-0.5 size-5 shrink-0" /> {{ session('status') }}
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="mx-auto mt-6 max-w-6xl px-4">
                <div class="flex items-start gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800" role="alert">
                    <x-heroicon-o-exclamation-triangle class="mt-0.5 size-5 shrink-0" /> {{ session('error') }}
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <span><span class="font-bold text-ink">IdeaJob</span> — заказчик называет цену, исполнители предлагают свою.</span>
            <span>© {{ now()->year }}</span>
        </div>
    </footer>

    {{-- Всплывающие уведомления: $this->dispatch('toast', message: ..., type: 'success'|'error') --}}
    <div x-data="{ toasts: [] }"
         @toast.window="const id = Date.now() + Math.random(); toasts.push({ id, ...$event.detail }); setTimeout(() => toasts = toasts.filter(t => t.id !== id), 4000)"
         class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-6 sm:bottom-6 sm:items-end"
         aria-live="polite">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition class="pointer-events-auto max-w-sm rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-lg"
                 :class="toast.type === 'error' ? 'bg-brand-700' : 'bg-slate-900'"
                 x-text="toast.message"></div>
        </template>
    </div>

    @livewireScripts
</body>
</html>
