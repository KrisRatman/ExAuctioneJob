{{--
    Выбор города из справочника.
    Работает и в обычной форме (name="city_id"), и в Livewire (wire:model="cityId").
--}}
@props(['cities', 'id', 'name' => null, 'selected' => null, 'model' => null])

<select id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($model) wire:model="{{ $model }}" @endif
        {{ $attributes->class('input') }}>
    <option value="">Выберите город</option>
    @foreach ($cities as $city)
        <option value="{{ $city->id }}" @selected((int) $selected === $city->id)>{{ $city->name }}</option>
    @endforeach
</select>
