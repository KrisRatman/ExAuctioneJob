<div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">
    <x-page-header title="ИИ-помощник" subtitle="Подскажет, какой мастер нужен, что написать в заказе и какую цену поставить.">
        <x-slot:actions>
            @if ($messages)
                <button type="button" wire:click="startOver" class="btn-secondary">
                    <x-heroicon-o-arrow-path class="size-4" /> Новый вопрос
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-5 sm:mt-6">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-3 sm:px-6">
            <label for="cityId" class="text-sm font-bold text-slate-700">Ваш город</label>
            <x-city-select id="cityId" model="cityId" :cities="$this->cities" :selected="$cityId" class="max-w-56 py-1.5" />
            <span class="text-xs text-slate-500">— по нему считаются мастера и цены</span>
        </div>

        <div class="space-y-4 px-4 py-5 sm:px-6" data-assistant-messages>
            @forelse ($messages as $i => $message)
                @if ($message['role'] === 'user')
                    <div wire:key="message-{{ $i }}" class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-br-md bg-brand-600 px-4 py-2.5 text-sm leading-relaxed whitespace-pre-line text-white">{{ $message['content'] }}</div>
                    </div>
                @else
                    <div wire:key="message-{{ $i }}" class="flex items-start gap-3">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600">
                            <x-heroicon-s-sparkles class="size-4" />
                        </span>
                        <div class="max-w-[85%] min-w-0 rounded-2xl rounded-tl-md bg-slate-100 px-4 py-2.5 text-sm leading-relaxed text-ink [&_li]:mt-1 [&_ol]:mt-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p+p]:mt-2 [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:pl-5">
                            {!! Str::markdown($message['content'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                        </div>
                    </div>
                @endif
            @empty
                <div class="py-4 text-center">
                    <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                        <x-heroicon-o-sparkles class="size-6" />
                    </span>
                    <p class="mt-3 text-sm text-slate-600">Опишите задачу своими словами — например:</p>
                    <div class="mt-4 flex flex-col items-stretch gap-2 sm:items-center">
                        @foreach (\App\Livewire\Customer\Assistant::EXAMPLES as $index => $example)
                            <button type="button" wire:key="example-{{ $index }}" wire:click="askExample({{ $index }})"
                                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-left text-sm font-medium text-slate-700 hover:border-brand-300 hover:text-brand-700">
                                {{ $example }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforelse

            <div wire:loading.flex wire:target="ask, askExample" class="items-center gap-3 text-sm text-slate-500">
                <span class="grid size-8 place-items-center rounded-full bg-brand-50 text-brand-600">
                    <x-heroicon-s-sparkles class="size-4 animate-pulse" />
                </span>
                Помощник думает…
            </div>

            @if ($messages)
                <div class="rounded-xl border border-brand-100 bg-brand-50/60 p-4" wire:loading.remove wire:target="ask, askExample">
                    @if ($this->suggestedTags->isNotEmpty())
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs font-bold text-brand-700">Теги для заказа:</span>
                            @foreach ($this->suggestedTags as $suggested)
                                <span wire:key="suggested-{{ $suggested->id }}" class="tag">{{ $suggested->name }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-slate-600">Форма заказа заполнится по этому диалогу — останется проверить и указать цену.</p>
                        <button type="button" wire:click="createOrder" class="btn-primary">
                            <x-heroicon-m-plus class="size-4" /> Создать заказ
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <form wire:submit="ask" class="border-t border-slate-100 px-4 py-4 sm:px-6">
            <div class="flex items-end gap-2">
                <label for="question" class="sr-only">Ваш вопрос</label>
                <textarea id="question" wire:model="question" rows="2" maxlength="1000" class="input min-h-12 flex-1 resize-y"
                          placeholder="Например: стиральная машина не сливает воду"
                          x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $wire.ask() }"></textarea>
                <button type="submit" class="btn-primary h-12 px-4" wire:loading.attr="disabled" wire:target="ask, askExample" aria-label="Отправить">
                    <x-heroicon-m-paper-airplane class="size-5" />
                </button>
            </div>
            @error('question') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
            <p class="mt-2 text-xs text-slate-400">Ответы генерирует ИИ по данным сайта и могут быть неточными. Не пишите в вопросе адрес и телефон.</p>
        </form>
    </div>
</div>
