{{-- Список диалогов: $conversations, $activeId (открытый диалог или null). --}}
@php($me = auth()->user())

<ul class="divide-y divide-slate-100" role="list">
    @forelse ($conversations as $item)
        @php($other = $item->otherParticipant($me))
        @php($isActive = $activeId === $item->id)
        <li wire:key="conversation-{{ $item->id }}">
            <a href="{{ route('chats.show', $item) }}" data-conversation="{{ $item->id }}"
               @class([
                   'flex gap-3 px-4 py-3.5 transition',
                   'bg-brand-50' => $isActive,
                   'hover:bg-slate-50' => ! $isActive,
               ])>
                <x-avatar :name="$other->name" />
                <span class="min-w-0 flex-1">
                    <span class="flex items-baseline justify-between gap-2">
                        <span class="truncate font-bold">{{ $other->name }}</span>
                        @if ($item->latestMessage)
                            <span class="shrink-0 text-xs text-slate-400">{{ $item->latestMessage->created_at->isToday() ? $item->latestMessage->created_at->format('H:i') : $item->latestMessage->created_at->translatedFormat('j M') }}</span>
                        @endif
                    </span>
                    <span class="block truncate text-xs font-semibold text-slate-500">{{ $item->order->title }}</span>
                    <span class="mt-0.5 flex items-center justify-between gap-2">
                        <span class="truncate text-sm text-slate-600">
                            @if ($item->latestMessage)
                                @if ($item->latestMessage->sender_id === $me->id)<span class="text-slate-400">Вы:</span>@endif
                                {{ $item->latestMessage->body }}
                            @else
                                <span class="text-slate-400">Сообщений пока нет</span>
                            @endif
                        </span>
                        @if ($item->unread_count > 0)
                            <span class="grid min-w-5 shrink-0 place-items-center rounded-full bg-brand-600 px-1.5 text-xs font-bold text-white" aria-label="Непрочитанных: {{ $item->unread_count }}">{{ $item->unread_count }}</span>
                        @endif
                    </span>
                </span>
            </a>
        </li>
    @empty
        <li class="px-6 py-12 text-center">
            <x-heroicon-o-chat-bubble-left-right class="mx-auto size-10 text-slate-300" />
            <p class="mt-3 font-bold">Чатов пока нет</p>
            <p class="mt-1 text-sm text-slate-500">
                @if ($me->isCustomer())
                    Чат с исполнителем откроется, когда вы примете его предложение.
                @else
                    Чат с заказчиком откроется, когда он примет ваше предложение.
                @endif
            </p>
        </li>
    @endforelse
</ul>
