<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" wire:poll.60s.visible="refreshBell">
    <button type="button" @click="open = !open" :aria-expanded="open" class="relative rounded-xl p-2 text-slate-500 hover:bg-slate-100 hover:text-ink" aria-label="Уведомления">
        <x-heroicon-o-bell class="size-6" />
        @if ($this->unreadCount > 0)
            <span class="absolute top-1 right-1 grid min-w-4.5 place-items-center rounded-full bg-brand-600 px-1 text-[10px] leading-4.5 font-bold text-white ring-2 ring-white" data-unread="{{ $this->unreadCount }}">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.right
         class="fixed inset-x-2 top-16 z-40 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-96">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <span class="font-extrabold">Уведомления</span>
            @if ($this->unreadCount > 0)
                <button type="button" wire:click="markAllRead" class="text-xs font-bold text-brand-600 hover:text-brand-700">Прочитать все</button>
            @endif
        </div>
        <ul class="max-h-[26rem] divide-y divide-slate-100 overflow-y-auto" role="list">
            @forelse ($this->notifications as $notification)
                <li wire:key="notification-{{ $notification->id }}">
                    <button type="button" wire:click="open('{{ $notification->id }}')"
                            @class(['flex w-full gap-3 px-4 py-3 text-left transition hover:bg-slate-50', 'bg-brand-50/50' => $notification->unread()])>
                        <span @class(['grid size-9 shrink-0 place-items-center rounded-full', 'bg-brand-100 text-brand-700' => $notification->unread(), 'bg-slate-100 text-slate-500' => $notification->read()])>
                            <x-dynamic-component :component="$notification->data['icon'] ?? 'heroicon-o-bell'" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-2">
                                <span class="text-sm font-bold">{{ $notification->data['title'] ?? '' }}</span>
                                @if ($notification->unread())
                                    <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-600" aria-label="Не прочитано"></span>
                                @endif
                            </span>
                            <span class="mt-0.5 block text-sm text-slate-600">{{ $notification->data['body'] ?? '' }}</span>
                            <span class="mt-1 block text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </li>
            @empty
                <li class="px-4 py-10 text-center text-sm text-slate-500">Уведомлений пока нет</li>
            @endforelse
        </ul>
    </div>
</div>
