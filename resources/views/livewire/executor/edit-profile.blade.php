<div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">
    <x-page-header title="Мой профиль" subtitle="Заказчик видит описание, теги и отзывы рядом с вашим предложением." />

    <div class="card mt-6 p-5">
        <x-rating :profile="auth()->user()->executorProfile" class="text-sm" />
    </div>

    <form wire:submit="save" class="card mt-4 space-y-6 p-5 sm:p-8">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Имя" for="name">
                <input id="name" wire:model="name" class="input">
            </x-field>
            <x-field label="Телефон" for="phone" hint="Необязательно">
                <input id="phone" type="tel" wire:model="phone" placeholder="+7 900 000-00-00" class="input">
            </x-field>
        </div>

        <x-field label="О себе" for="description" hint="Чем занимаетесь, опыт, примеры работ.">
            <textarea id="description" wire:model="description" rows="6" class="input"></textarea>
        </x-field>

        <div>
            <div class="text-sm font-bold text-slate-700">Специализации</div>
            <p class="mt-0.5 text-xs text-slate-500">В ленте будут только заказы с этими тегами.</p>
            <x-tag-picker :tree="$this->tree" :max="config('ideajob.executor_tags.max')" model="categoryIds" class="mt-3" />
            @error('categoryIds') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
            @error('categoryIds.*') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-6">
            <button type="submit" class="btn-primary px-6" wire:loading.attr="disabled">Сохранить</button>
        </div>
    </form>

    <section class="mt-8" aria-labelledby="reviews-title">
        <h2 id="reviews-title" class="text-xl font-extrabold tracking-tight">Отзывы заказчиков</h2>
        <x-review-list :reviews="$this->reviews" class="mt-4" />
    </section>
</div>
