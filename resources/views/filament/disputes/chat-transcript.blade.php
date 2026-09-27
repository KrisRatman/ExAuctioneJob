{{-- Переписка заказчика и исполнителя для администратора, разбирающего спор. --}}
@php
    $order = $getRecord()->order;
    $messages = $order->conversation?->messages()->with('sender')->oldest('id')->get() ?? collect();
@endphp

<div class="space-y-3" data-transcript>
    @forelse ($messages as $message)
        @php($fromCustomer = $message->sender_id === $order->customer_id)
        <div style="display:flex; justify-content: {{ $fromCustomer ? 'flex-start' : 'flex-end' }};">
            <div style="max-width: 75%; border-radius: 0.75rem; padding: 0.5rem 0.875rem;
                        background: {{ $fromCustomer ? 'rgb(241 245 249)' : 'rgb(254 242 243)' }};">
                <div style="font-size: 0.75rem; font-weight: 700; color: rgb(100 116 139);">
                    {{ $message->sender->name }} · {{ $fromCustomer ? 'заказчик' : 'исполнитель' }} · {{ $message->created_at->format('d.m.Y H:i') }}
                </div>
                <div style="white-space: pre-line; font-size: 0.875rem; margin-top: 0.25rem;">{{ $message->body }}</div>
            </div>
        </div>
    @empty
        <p style="font-size: 0.875rem; color: rgb(100 116 139);">Стороны не переписывались в чате.</p>
    @endforelse
</div>
