{{--
    Выбор тегов из дерева категорий: разделы — заголовки групп, подкатегории — чекбоксы.
    Работает и в обычной форме (name="categories[]"), и в Livewire (wire:model="categoryIds").
    Лимит :max держит Alpine: когда выбрано максимум, остальные чекбоксы блокируются.
--}}
@props(['tree', 'max', 'name' => null, 'selected' => [], 'model' => null])

@php($selected = array_map('intval', (array) $selected))

<div x-data="{ count: 0, max: {{ (int) $max }}, sync() { this.count = this.$root.querySelectorAll('input[type=checkbox]:checked').length } }"
     x-init="$nextTick(() => sync())" @change="sync()" {{ $attributes->class('space-y-4') }}>
    <p class="text-xs font-semibold text-slate-500">Выбрано <span x-text="count"></span> из <span x-text="max"></span></p>

    @foreach ($tree as $root)
        <fieldset wire:key="tag-group-{{ $root->id }}">
            <legend class="mb-2 text-xs font-extrabold uppercase tracking-wide text-slate-400">{{ $root->name }}</legend>
            <div class="flex flex-wrap gap-2">
                @foreach ($root->children as $tag)
                    <label wire:key="tag-{{ $tag->id }}" class="group cursor-pointer has-disabled:cursor-not-allowed has-disabled:opacity-50">
                        <input type="checkbox" value="{{ $tag->id }}" class="peer sr-only"
                               @if ($model) wire:model="{{ $model }}" @else name="{{ $name }}[]" @checked(in_array($tag->id, $selected, true)) @endif
                               :disabled="!$el.checked && count >= max">
                        <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 transition group-hover:border-slate-300 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-100">
                            {{ $tag->name }}
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach
</div>
