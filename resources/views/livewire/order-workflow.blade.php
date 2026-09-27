@use('App\Enums\DisputeResolution')
@use('App\Enums\OrderStatus')
@php
    $status = $order->status;
    $dispute = $order->latestDispute;
    $box = $compact ? 'border-b border-slate-100 px-4 py-3 sm:px-5' : 'card p-5';
@endphp

<div data-workflow="{{ $status->value }}">
    @switch($status)
        @case(OrderStatus::InProgress)
            <div class="{{ $box }} flex flex-wrap items-center gap-3 bg-sky-50/60">
                <x-heroicon-o-wrench-screwdriver class="size-5 shrink-0 text-sky-600" />
                <div class="min-w-0 flex-1 text-sm">
                    @if ($isCustomer)
                        <span class="font-bold">Исполнитель работает над заказом.</span>
                        <span @class(['text-slate-600', 'hidden sm:inline' => $compact])>Когда работа будет сдана, вы сможете её подтвердить или открыть спор.</span>
                    @else
                        <span class="font-bold">Заказ в работе.</span>
                        <span @class(['text-slate-600', 'hidden sm:inline' => $compact])>Закончили — сдайте работу, заказчик получит уведомление.</span>
                    @endif
                    @if ($dispute?->resolution === DisputeResolution::ReturnToWork)
                        <p class="mt-1 text-slate-600">После спора заказ возвращён на доработку: {{ $dispute->resolution_comment }}</p>
                    @endif
                </div>
                @unless ($isCustomer)
                    <button type="button" wire:click="deliver" wire:confirm="Отметить работу сданной? Заказчик проверит результат." wire:loading.attr="disabled" class="btn-primary">
                        <x-heroicon-o-arrow-up-tray class="size-4" /> Сдать работу
                    </button>
                @endunless
            </div>
            @break

        @case(OrderStatus::Delivered)
            <div class="{{ $box }} bg-amber-50/70">
                <div class="flex flex-wrap items-center gap-3">
                    <x-heroicon-o-inbox-arrow-down class="size-5 shrink-0 text-amber-600" />
                    <div class="min-w-0 flex-1 text-sm">
                        @if ($isCustomer)
                            <span class="font-bold">Исполнитель сдал работу.</span>
                            <span @class(['text-slate-600', 'hidden sm:inline' => $compact])>Проверьте результат: всё устраивает — подтвердите, иначе откройте спор.</span>
                        @else
                            <span class="font-bold">Работа на проверке у заказчика.</span>
                            <span @class(['text-slate-600', 'hidden sm:inline' => $compact])>Сдано {{ $order->delivered_at?->translatedFormat('j F, H:i') }}.</span>
                        @endif
                    </div>
                    @if ($isCustomer)
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="$toggle('showDisputeForm')" class="btn-secondary">Открыть спор</button>
                            <button type="button" wire:click="confirm" wire:confirm="Подтвердить, что работа выполнена?" wire:loading.attr="disabled" class="btn-primary">
                                <x-heroicon-o-check class="size-4" stroke-width="2.5" /> Подтвердить выполнение
                            </button>
                        </div>
                    @endif
                </div>

                @if ($isCustomer && $showDisputeForm)
                    <form wire:submit="openDispute" class="mt-4 space-y-3 border-t border-amber-200 pt-4">
                        <x-field label="Что не так с результатом" for="disputeReason" hint="Администратор прочитает причину и переписку в чате, затем примет решение.">
                            <textarea id="disputeReason" wire:model="disputeReason" rows="4" class="input"></textarea>
                        </x-field>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="$set('showDisputeForm', false)" class="btn-secondary">Отмена</button>
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled">Отправить спор</button>
                        </div>
                    </form>
                @endif
            </div>
            @break

        @case(OrderStatus::Disputed)
            <div class="{{ $box }} flex gap-3 bg-amber-50/70">
                <x-heroicon-o-scale class="size-5 shrink-0 text-amber-600" />
                <div class="min-w-0 flex-1 text-sm">
                    <span class="font-bold">Спор на рассмотрении у администратора.</span>
                    @if ($dispute)
                        <p class="mt-1 text-slate-600"><span class="font-semibold">Причина:</span> {{ $dispute->reason }}</p>
                    @endif
                </div>
            </div>
            @break

        @case(OrderStatus::Resolved)
            <div class="{{ $box }} flex gap-3 bg-slate-50">
                <x-heroicon-o-scale class="size-5 shrink-0 text-slate-500" />
                <div class="min-w-0 flex-1 text-sm">
                    <span class="font-bold">Спор решён: {{ mb_strtolower($dispute?->resolution?->getLabel() ?? '') }}.</span>
                    @if ($dispute?->resolution_comment)
                        <p class="mt-1 text-slate-600">{{ $dispute->resolution_comment }}</p>
                    @endif
                </div>
            </div>
            @break

        @case(OrderStatus::Completed)
            <div class="{{ $box }} bg-emerald-50/60">
                <div class="flex items-center gap-3 text-sm">
                    <x-heroicon-o-trophy class="size-5 shrink-0 text-emerald-600" />
                    <span class="font-bold">Заказ выполнен {{ $order->completed_at?->translatedFormat('j F Y') }}.</span>
                </div>

                @if ($order->review)
                    <div class="mt-3 border-t border-emerald-200 pt-3 text-sm" data-review>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500">{{ $isCustomer ? 'Ваша оценка:' : 'Оценка заказчика:' }}</span>
                            <x-stars :rating="$order->review->rating" />
                        </div>
                        @if ($order->review->comment)
                            <p class="mt-1 text-slate-700">{{ $order->review->comment }}</p>
                        @endif
                    </div>
                @elseif ($isCustomer)
                    <form wire:submit="review" class="mt-3 space-y-3 border-t border-emerald-200 pt-3"
                          x-data="{ hover: 0, rating: $wire.entangle('rating') }">
                        <div class="text-sm font-bold">Оцените работу исполнителя {{ $order->executor->name }}</div>
                        <div class="flex items-center gap-1" role="radiogroup" aria-label="Оценка" @mouseleave="hover = 0">
                            @foreach (range(1, 5) as $star)
                                <button type="button" role="radio" :aria-checked="rating === {{ $star }}" aria-label="{{ $star }} из 5"
                                        @click="rating = {{ $star }}" @mouseenter="hover = {{ $star }}" data-star="{{ $star }}"
                                        class="rounded p-0.5 transition hover:scale-110">
                                    <x-heroicon-s-star class="size-8" ::class="(hover || rating || 0) >= {{ $star }} ? 'text-amber-400' : 'text-slate-300'" />
                                </button>
                            @endforeach
                        </div>
                        @error('rating') <p class="text-sm font-medium text-brand-700">{{ $message }}</p> @enderror
                        <x-field label="Отзыв" for="comment" hint="Необязательно. Увидят другие заказчики рядом с предложениями исполнителя.">
                            <textarea id="comment" wire:model="comment" rows="3" class="input"></textarea>
                        </x-field>
                        <div class="flex justify-end">
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled">Отправить оценку</button>
                        </div>
                    </form>
                @else
                    <p class="mt-1 text-sm text-slate-600">Заказчик ещё не оставил оценку.</p>
                @endif
            </div>
            @break
    @endswitch
</div>
