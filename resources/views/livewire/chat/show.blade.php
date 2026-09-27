<div class="mx-auto max-w-6xl px-0 py-0 sm:px-4 sm:py-10">
    @php($me = auth()->user())
    @php($other = $conversation->otherParticipant($me))
    @php($order = $conversation->order)

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        {{-- Диалоги --}}
        <aside class="card hidden max-h-[calc(100vh-10rem)] overflow-y-auto lg:block" aria-label="Диалоги">
            <div class="border-b border-slate-100 px-4 py-3 font-extrabold">Чаты</div>
            @include('livewire.chat.partials.conversation-list', ['conversations' => $this->conversations, 'activeId' => $conversation->id])
        </aside>

        {{-- Переписка --}}
        <section class="flex h-[calc(100dvh-8rem)] min-h-[24rem] flex-col overflow-hidden bg-white sm:card sm:h-[calc(100dvh-10rem)] md:h-[calc(100dvh-12rem)]" aria-label="Переписка">
            <header class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
                <a href="{{ route('chats.index') }}" class="-ml-1 rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-ink lg:hidden" aria-label="Все чаты">
                    <x-heroicon-m-arrow-left class="size-5" />
                </a>
                <x-avatar :name="$other->name" />
                <div class="min-w-0 flex-1">
                    <div class="truncate font-bold">{{ $other->name }}</div>
                    <div class="truncate text-xs text-slate-500">
                        {{ $me->isCustomer() ? 'Исполнитель' : 'Заказчик' }} ·
                        @if ($me->isCustomer())
                            <a href="{{ route('customer.orders.show', $order) }}" class="font-semibold hover:text-brand-600">{{ $order->title }}</a>
                        @else
                            <span class="font-semibold">{{ $order->title }}</span>
                        @endif
                    </div>
                </div>
                <span class="badge hidden sm:inline-flex {{ $order->status->badgeClasses() }}">{{ $order->status->getLabel() }}</span>
            </header>

            <livewire:order-workflow :order="$order" :return-url="route('chats.show', $conversation)" compact :key="'workflow-'.$order->id.'-'.$order->status->value" />

            <div class="flex-1 space-y-3 overflow-y-auto bg-canvas/60 px-4 py-5 sm:px-5"
                 x-data="{ scroll() { this.$el.scrollTop = this.$el.scrollHeight } }"
                 x-init="scroll()" x-on:chat-scroll.window="$nextTick(() => scroll())"
                 data-messages>
                @forelse ($this->messages as $message)
                    @php($mine = $message->sender_id === $me->id)
                    @php($newDay = $loop->first || ! $message->created_at->isSameDay($this->messages[$loop->index - 1]->created_at))
                    @if ($newDay)
                        <div class="py-1 text-center text-xs font-semibold text-slate-400" wire:key="day-{{ $message->id }}">
                            {{ $message->created_at->isToday() ? 'Сегодня' : $message->created_at->translatedFormat('j F') }}
                        </div>
                    @endif
                    <div wire:key="message-{{ $message->id }}" @class(['flex', 'justify-end' => $mine])>
                        <div @class([
                            'max-w-[85%] rounded-2xl px-4 py-2.5 text-[15px] leading-relaxed shadow-sm sm:max-w-[70%]',
                            'rounded-br-md bg-brand-600 text-white' => $mine,
                            'rounded-bl-md bg-white text-ink ring-1 ring-slate-200' => ! $mine,
                        ])>
                            <p class="whitespace-pre-line break-words">{{ $message->body }}</p>
                            <p @class(['mt-1 flex items-center justify-end gap-1 text-[11px]', 'text-brand-100' => $mine, 'text-slate-400' => ! $mine])>
                                {{ $message->created_at->format('H:i') }}
                                @if ($mine)
                                    @if ($message->read_at)
                                        <span title="Прочитано">✓✓</span>
                                    @else
                                        <span title="Доставлено">✓</span>
                                    @endif
                                @endif
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="grid h-full place-items-center text-center">
                        <div>
                            <x-heroicon-o-chat-bubble-left-right class="mx-auto size-10 text-slate-300" />
                            <p class="mt-3 font-bold">Начните разговор</p>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $me->isCustomer() ? 'Расскажите исполнителю детали и договоритесь об оплате.' : 'Поздоровайтесь и уточните детали задачи.' }}
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>

            <form wire:submit="send" class="border-t border-slate-100 bg-white p-3 sm:p-4">
                <div class="flex items-end gap-2">
                    <label for="chat-body" class="sr-only">Сообщение</label>
                    <textarea id="chat-body" wire:model="body" rows="1" maxlength="5000" placeholder="Сообщение…"
                              class="input max-h-40 min-h-11 resize-none"
                              x-data x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                              x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit() }"
                              x-on:chat-scroll.window="$el.style.height = 'auto'"></textarea>
                    <button type="submit" class="btn-primary size-11 shrink-0 p-0" wire:loading.attr="disabled" aria-label="Отправить">
                        <x-heroicon-s-paper-airplane class="size-5" />
                    </button>
                </div>
                @error('body') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
                <p class="mt-1.5 hidden text-xs text-slate-400 sm:block">Enter — отправить, Shift+Enter — новая строка</p>
            </form>
        </section>
    </div>
</div>
