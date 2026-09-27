<div class="mx-auto max-w-3xl px-4 py-10">
    <h1 class="text-3xl font-extrabold tracking-tight">Чаты</h1>
    <p class="mt-1 text-slate-500">Переписка по заказам в работе.</p>

    <div class="card mt-6 overflow-hidden">
        @include('livewire.chat.partials.conversation-list', ['conversations' => $this->conversations, 'activeId' => null])
    </div>
</div>
