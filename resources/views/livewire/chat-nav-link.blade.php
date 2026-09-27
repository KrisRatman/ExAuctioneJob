<a href="{{ route('chats.index') }}" class="{{ $linkClass }} inline-flex items-center gap-1.5">
    Чаты
    @if ($this->unreadCount > 0)
        <span class="grid min-w-5 place-items-center rounded-full bg-brand-600 px-1.5 text-xs font-bold text-white" data-unread-messages="{{ $this->unreadCount }}">{{ $this->unreadCount }}</span>
    @endif
</a>
