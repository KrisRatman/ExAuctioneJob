<x-layout title="Регистрация">
    <div class="mx-auto max-w-2xl px-4 py-14" x-data="{ role: @js(old('role', $role->value)) }">
        <h1 class="text-center text-3xl font-extrabold tracking-tight">Регистрация</h1>
        <p class="mt-2 text-center text-slate-500">Уже есть аккаунт? <a href="{{ route('login') }}" class="font-bold text-brand-600 hover:text-brand-700">Войдите</a></p>

        <form method="post" action="{{ route('register') }}" class="card mt-8 space-y-6 p-6 sm:p-8">
            @csrf

            <fieldset>
                <legend class="text-sm font-bold text-slate-700">Я хочу</legend>
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        ['customer', 'Разместить заказ', 'Опишу задачу и выберу исполнителя', 'heroicon-o-clipboard-document-list'],
                        ['executor', 'Выполнять заказы', 'Буду предлагать цену и срок', 'heroicon-o-wrench-screwdriver'],
                    ] as [$value, $label, $text, $icon])
                        <label class="flex cursor-pointer gap-3 rounded-xl border p-4 transition"
                               :class="role === '{{ $value }}' ? 'border-brand-500 bg-brand-50 ring-4 ring-brand-100' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="role" value="{{ $value }}" x-model="role" class="sr-only">
                            <x-dynamic-component :component="$icon" class="size-6 shrink-0" ::class="role === '{{ $value }}' ? 'text-brand-600' : 'text-slate-400'" />
                            <span>
                                <span class="block font-bold">{{ $label }}</span>
                                <span class="block text-sm text-slate-500">{{ $text }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('role') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
            </fieldset>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field label="Имя" for="name" class="sm:col-span-2">
                    <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="input">
                </x-field>
                <x-field label="Email" for="email">
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="input">
                </x-field>
                <x-field label="Телефон" for="phone" hint="Необязательно">
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" placeholder="+7 900 000-00-00" class="input">
                </x-field>
                <x-field label="Пароль" for="password" hint="Не меньше 8 символов">
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="input">
                </x-field>
                <x-field label="Повторите пароль" for="password_confirmation">
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
                </x-field>
            </div>

            <template x-if="role === 'executor'">
                <div class="space-y-6 border-t border-slate-100 pt-6">
                    <x-field label="О себе" for="description" hint="Чем занимаетесь, опыт, примеры работ. Это увидит заказчик рядом с вашим предложением.">
                        <textarea id="description" name="description" rows="5" class="input">{{ old('description') }}</textarea>
                    </x-field>

                    <div>
                        <div class="text-sm font-bold text-slate-700">Специализации</div>
                        <p class="mt-0.5 text-xs text-slate-500">В ленте будут только заказы с этими тегами.</p>
                        <x-tag-picker :tree="$tree" :max="config('ideajob.executor_tags.max')" name="categories" :selected="old('categories', [])" class="mt-3" />
                        @error('categories') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
                        @error('categories.*') <p class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </template>

            <button type="submit" class="btn-primary w-full py-3">Зарегистрироваться</button>
        </form>
    </div>
</x-layout>
