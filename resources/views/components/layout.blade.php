@props(['title' => null])

@php
    $user = auth()->user();
    $hasTabs = $user && ! $user->isAdmin();
    $navLink = fn (bool $active) => $active
        ? 'relative px-1 py-5 text-sm font-bold text-ink after:absolute after:inset-x-0 after:-bottom-px after:h-0.5 after:rounded-full after:bg-brand-600'
        : 'px-1 py-5 text-sm font-semibold text-slate-500 hover:text-ink';
    $tabLink = fn (bool $active) => $active
        ? 'flex flex-1 flex-col items-center gap-0.5 py-2 text-[11px] font-bold text-brand-600'
        : 'flex flex-1 flex-col items-center gap-0.5 py-2 text-[11px] font-semibold text-slate-500';
@endphp

<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name') }} — биржа заказов</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body @class(['flex min-h-full flex-col bg-canvas font-sans text-ink antialiased', 'pb-16 md:pb-0' => $hasTabs])>
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur" x-data="{ menu: false }">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4">
            <a href="{{ $user ? $user->homeUrl() : route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="{{ config('app.name') }} — на главную">
                <span class="grid size-8 place-items-center rounded-lg bg-brand-600 text-white">
                    <x-heroicon-s-light-bulb class="size-5" />
                </span>
                <span class="text-lg font-extrabold tracking-tight">Idea<span class="text-brand-600">Job</span></span>
            </a>

            <nav class="hidden items-center gap-6 self-stretch md:flex" aria-label="Основное меню">
                @if ($user?->isCustomer())
                    <a href="{{ route('customer.orders.index') }}" class="{{ $navLink(request()->routeIs('customer.orders.index', 'customer.orders.show')) }}">Мои заказы</a>
                    <livewire:chat-nav-link :link-class="$navLink(request()->routeIs('chats.*'))" />
                @elseif ($user?->isExecutor())
                    <a href="{{ route('executor.feed') }}" class="{{ $navLink(request()->routeIs('executor.feed')) }}">Лента заказов</a>
                    <a href="{{ route('executor.bids') }}" class="{{ $navLink(request()->routeIs('executor.bids')) }}">Мои предложения</a>
                    <livewire:chat-nav-link :link-class="$navLink(request()->routeIs('chats.*'))" />
                @endif
            </nav>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                @auth
                    @if ($user->isCustomer())
                        <a href="{{ route('customer.orders.create') }}" class="btn-primary mr-2 hidden md:inline-flex">
                            <x-heroicon-m-plus class="size-4" /> Разместить заказ
                        </a>
                    @elseif ($user->isAdmin())
                        <a href="{{ url('/admin') }}" class="btn-secondary">Админка</a>
                    @endif

                    @unless ($user->isAdmin())
                        <livewire:notification-bell />
                    @endunless

                    <div class="relative" @click.outside="menu = false" @keydown.escape.window="menu = false">
                        <button type="button" @click="menu = !menu" class="flex items-center gap-2 rounded-xl p-1 hover:bg-slate-100" :aria-expanded="menu" aria-label="Меню пользователя">
                            <x-avatar :name="$user->name" size="sm" />
                            <x-heroicon-m-chevron-down class="hidden size-4 text-slate-400 sm:block" />
                        </button>
                        <div x-show="menu" x-cloak x-transition.origin.top.right class="absolute right-0 top-full z-40 mt-2 w-60 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                            <div class="border-b border-slate-100 px-3 pt-2 pb-3">
                                <div class="truncate text-sm font-bold">{{ $user->name }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $user->role->getLabel() }} · {{ $user->email }}</div>
                            </div>
                            @if ($user->isExecutor())
                                <a href="{{ route('executor.profile') }}" class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-50">
                                    <x-heroicon-o-user class="size-4 text-slate-400" /> Профиль и теги
                                </a>
                            @endif
                            <form method="post" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                    <x-heroicon-o-arrow-right-start-on-rectangle class="size-4 text-slate-400" /> Выйти
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
            <div class="mx-auto mt-4 max-w-6xl px-4 sm:mt-6">
                <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                    <x-heroicon-o-check-circle class="mt-0.5 size-5 shrink-0" /> {{ session('status') }}
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="mx-auto mt-4 max-w-6xl px-4 sm:mt-6">
                <div class="flex items-start gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800" role="alert">
                    <x-heroicon-o-exclamation-triangle class="mt-0.5 size-5 shrink-0" /> {{ session('error') }}
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer @class(['border-t border-slate-200 bg-white', 'hidden md:block' => $hasTabs])>
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-5 text-sm text-slate-500">
            <span>© {{ now()->year }} IdeaJob</span>
            @guest
                <span class="hidden sm:inline">Заказчик называет цену — исполнители предлагают свою</span>
            @endguest
        </div>
    </footer>

    {{-- Нижние вкладки на телефоне: главные разделы всегда под пальцем. --}}
    @if ($hasTabs)
        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden" aria-label="Разделы">
            <div class="mx-auto flex max-w-md items-stretch">
                @if ($user->isCustomer())
                    <a href="{{ route('customer.orders.index') }}" class="{{ $tabLink(request()->routeIs('customer.orders.index', 'customer.orders.show')) }}">
                        <x-heroicon-o-clipboard-document-list class="size-6" /> Заказы
                    </a>
                    <a href="{{ route('customer.orders.create') }}" class="flex flex-1 flex-col items-center justify-center" aria-label="Разместить заказ">
                        <span class="grid size-11 place-items-center rounded-full bg-brand-600 text-white shadow-md shadow-brand-600/30">
                            <x-heroicon-m-plus class="size-6" />
                        </span>
                    </a>
                    <livewire:chat-nav-link variant="tab" :link-class="$tabLink(request()->routeIs('chats.*'))" />
                @else
                    <a href="{{ route('executor.feed') }}" class="{{ $tabLink(request()->routeIs('executor.feed')) }}">
                        <x-heroicon-o-squares-2x2 class="size-6" /> Лента
                    </a>
                    <a href="{{ route('executor.bids') }}" class="{{ $tabLink(request()->routeIs('executor.bids')) }}">
                        <x-heroicon-o-paper-airplane class="size-6" /> Предложения
                    </a>
                    <livewire:chat-nav-link variant="tab" :link-class="$tabLink(request()->routeIs('chats.*'))" />
                    <a href="{{ route('executor.profile') }}" class="{{ $tabLink(request()->routeIs('executor.profile')) }}">
                        <x-heroicon-o-user class="size-6" /> Профиль
                    </a>
                @endif
            </div>
        </nav>
    @endif

    {{-- Всплывающие уведомления: $this->dispatch('toast', message: ..., type: 'success'|'error') --}}
    <div x-data="{ toasts: [] }"
         @toast.window="const id = Date.now() + Math.random(); toasts.push({ id, ...$event.detail }); setTimeout(() => toasts = toasts.filter(t => t.id !== id), 4000)"
         @class(['pointer-events-none fixed inset-x-0 z-[60] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-6 sm:bottom-6 sm:items-end', 'bottom-20' => $hasTabs, 'bottom-4' => ! $hasTabs])
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
