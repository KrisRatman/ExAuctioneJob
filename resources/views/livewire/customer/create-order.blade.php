<div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">
    <a href="{{ route('customer.orders.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-ink">
        <x-heroicon-m-arrow-left class="size-4" /> Мои заказы
    </a>
    <x-page-header title="Новый заказ" class="mt-3" />

    <form wire:submit="save" class="card mt-5 space-y-6 p-5 sm:mt-6 sm:p-8">
        <x-field label="Что нужно сделать" for="title" hint="Коротко, например: «Сверстать лендинг по макету в Figma»">
            <input id="title" wire:model="title" maxlength="150" class="input">
        </x-field>

        <x-field label="Описание задачи" for="description" hint="Подробности, требования, сроки, ссылки на примеры.">
            <textarea id="description" wire:model="description" rows="7" class="input"></textarea>
        </x-field>

        <x-field label="Стартовая цена, ₽" for="startingPrice" hint="Исполнители смогут предложить меньше или больше — но не дороже этой цены плюс {{ rub(config('ideajob.max_bid_markup')) }}.">
            <input id="startingPrice" type="number" wire:model="startingPrice" min="{{ config('ideajob.min_price') }}" max="{{ config('ideajob.max_price') }}" step="1" inputmode="numeric" class="input no-spin max-w-xs">
        </x-field>

        <div>
            <div class="text-sm font-bold text-slate-700">Теги</div>
            <p class="mt-0.5 text-xs text-slate-500">Заказ увидят исполнители хотя бы с одним из этих тегов.</p>
            <x-tag-picker :tree="$this->tree" :max="config('ideajob.order_tags.max')" model="categoryIds" class="mt-3" />
            @error('categoryIds') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
            @error('categoryIds.*') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-6">
            <button type="submit" class="btn-primary px-6 py-3" wire:loading.attr="disabled">Опубликовать заказ</button>
        </div>
    </form>
</div>
