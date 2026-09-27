<div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">
    <x-page-header title="Чаты" />

    <div class="card mt-5 overflow-hidden">
        @include('livewire.chat.partials.conversation-list', ['conversations' => $this->conversations, 'activeId' => null])
    </div>
</div>
