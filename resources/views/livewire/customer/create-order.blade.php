<div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">
    <a href="{{ route('customer.orders.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-ink">
        <x-heroicon-m-arrow-left class="size-4" /> Мои заказы
    </a>
    <x-page-header title="Новый заказ" class="mt-3" />

    @if (ai_enabled())
        <a href="{{ route('customer.assistant') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
            <x-heroicon-o-sparkles class="size-4" /> Не знаете, как описать задачу? Спросите помощника
        </a>
    @endif

    <form wire:submit="save" class="card mt-5 space-y-6 p-5 sm:mt-6 sm:p-8">
        <x-field label="Что нужно сделать" for="title" hint="Коротко, например: «Уложить плитку в ванной, 6 м²»">
            <input id="title" wire:model.blur="title" maxlength="150" class="input">
        </x-field>

        <x-field label="Описание задачи" for="description" hint="Объём работ, материалы (чьи), когда удобно, район. Точный адрес не пишите — его увидит только выбранный мастер в чате.">
            <textarea id="description" wire:model.blur="description" rows="7" class="input"></textarea>
        </x-field>

        <x-field label="Город" for="cityId" hint="Заказ увидят только мастера из этого города.">
            <x-city-select id="cityId" model="cityId" :cities="$this->cities" :selected="$cityId" class="max-w-xs" />
        </x-field>

        <x-field label="Стартовая цена, ₽" for="startingPrice" hint="Исполнители смогут предложить меньше или больше — но не дороже этой цены плюс {{ rub(config('ideajob.max_bid_markup')) }}.">
            <input id="startingPrice" type="number" wire:model="startingPrice" min="{{ config('ideajob.min_price') }}" max="{{ config('ideajob.max_price') }}" step="1" inputmode="numeric" class="input no-spin max-w-xs">
        </x-field>

        <div>
            <div class="text-sm font-bold text-slate-700">Теги</div>
            <p class="mt-0.5 text-xs text-slate-500">Заказ увидят мастера хотя бы с одним из этих тегов.</p>

            @if ($this->suggestedTags->isNotEmpty())
                <div class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-brand-100 bg-brand-50/60 px-3 py-2.5" data-tag-suggestions>
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-brand-700">
                        <x-heroicon-m-sparkles class="size-4" /> По описанию подходят:
                    </span>
                    @foreach ($this->suggestedTags as $suggested)
                        <button type="button" wire:key="suggested-{{ $suggested->id }}" wire:click="addTag({{ $suggested->id }})"
                                class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1 text-sm font-semibold text-slate-700 ring-1 ring-brand-200 hover:text-brand-700 hover:ring-brand-400">
                            <x-heroicon-m-plus class="size-3.5" /> {{ $suggested->name }}
                            <span class="text-xs font-medium text-slate-400">· {{ $suggested->parent->name }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            <x-tag-picker :tree="$this->tree" :max="config('ideajob.order_tags.max')" model="categoryIds" class="mt-3" />
            @error('categoryIds') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
            @error('categoryIds.*') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-6">
            <button type="submit" class="btn-primary px-6 py-3" wire:loading.attr="disabled">Опубликовать заказ</button>
        </div>
    </form>
</div>
