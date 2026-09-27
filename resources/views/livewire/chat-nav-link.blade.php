<a href="{{ route('chats.index') }}" @class([$linkClass, 'inline-flex items-center gap-1.5' => $variant !== 'tab'])>
    @if ($variant === 'tab')
        <span class="relative">
            <x-heroicon-o-chat-bubble-left-right class="size-6" />
            @if ($this->unreadCount > 0)
                <span class="absolute -top-1 -right-2 grid min-w-4.5 place-items-center rounded-full bg-brand-600 px-1 text-[10px] leading-4.5 font-bold text-white ring-2 ring-white">{{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}</span>
            @endif
        </span>
        Чаты
    @else
        Чаты
        @if ($this->unreadCount > 0)
            <span class="grid min-w-5 place-items-center rounded-full bg-brand-600 px-1.5 text-xs font-bold text-white" data-unread-messages="{{ $this->unreadCount }}">{{ $this->unreadCount }}</span>
        @endif
    @endif
</a>
