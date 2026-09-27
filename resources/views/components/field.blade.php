@props(['label', 'for', 'error' => null, 'hint' => null])

<div {{ $attributes }}>
    <label for="{{ $for }}" class="block text-sm font-bold text-slate-700">{{ $label }}</label>
    <div class="mt-1.5">{{ $slot }}</div>
    @error($error ?? $for)
        <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @enderror
</div>
