<?php

namespace App\Livewire\Customer;

use App\Actions\AskAssistant;
use App\Exceptions\AiException;
use App\Models\Category;
use App\Models\City;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * ИИ-помощник заказчика: подсказывает, какой мастер нужен, что написать и какую цену поставить,
 * опираясь на теги, похожие заказы и мастеров города. Из диалога одной кнопкой собирается черновик заказа.
 */
#[Layout('layouts.app')]
#[Title('ИИ-помощник')]
class Assistant extends Component
{
    public const array EXAMPLES = [
        'Холодильник перестал морозить, внизу лужа воды. Кого вызвать и сколько это стоит?',
        'Нужно уложить плитку в ванной, около 6 м². Какую цену поставить?',
        'Как здесь выбрать мастера и как ему заплатить?',
    ];

    /**
     * Диалог. Locked: историю нельзя подменить с браузера.
     *
     * @var list<array{role: 'user'|'assistant', content: string}>
     */
    #[Locked]
    public array $messages = [];

    /** @var list<int> */
    #[Locked]
    public array $suggestedCategoryIds = [];

    public string $question = '';

    public ?int $cityId = null;

    public function mount(): void
    {
        abort_unless(ai_enabled(), 404);

        $this->cityId = auth()->user()->customerOrders()->latest('id')->value('city_id');
    }

    /** @return Collection<int, City> */
    #[Computed]
    public function cities(): Collection
    {
        return City::options();
    }

    /** Теги, найденные по последнему вопросу, — пойдут в черновик заказа. */
    #[Computed]
    public function suggestedTags(): Collection
    {
        return Category::query()
            ->tags()
            ->with('parent')
            ->whereKey($this->suggestedCategoryIds)
            ->get()
            ->sortBy(fn (Category $tag) => array_search($tag->id, $this->suggestedCategoryIds, true))
            ->values();
    }

    public function askExample(int $index): void
    {
        $this->question = self::EXAMPLES[$index] ?? '';
        $this->ask(app(AskAssistant::class));
    }

    public function ask(AskAssistant $assistant): void
    {
        $data = $this->validate([
            'question' => ['required', 'string', 'min:3', 'max:1000'],
            'cityId' => ['nullable', 'integer', 'exists:cities,id'],
        ], [
            'question.required' => 'Напишите, что нужно сделать.',
        ]);

        $limiterKey = 'assistant:'.auth()->id();

        if (RateLimiter::tooManyAttempts($limiterKey, (int) config('ideajob.ai.assistant_per_hour'))) {
            $minutes = (int) ceil(RateLimiter::availableIn($limiterKey) / 60);
            $this->addError('question', "Слишком много вопросов подряд. Попробуйте через {$minutes} мин.");

            return;
        }

        RateLimiter::hit($limiterKey, 3600);

        $history = [...$this->messages, ['role' => 'user', 'content' => trim($data['question'])]];

        try {
            $reply = $assistant->handle(
                $data['cityId'] ? City::query()->find($data['cityId']) : null,
                $history,
            );
        } catch (AiException $e) {
            report($e);
            $this->addError('question', 'Помощник сейчас недоступен. Попробуйте позже или сразу разместите заказ.');

            return;
        }

        $this->messages = [...$history, ['role' => 'assistant', 'content' => $reply['answer']]];
        $this->suggestedCategoryIds = $reply['category_ids'];
        $this->question = '';
        unset($this->suggestedTags);
    }

    public function startOver(): void
    {
        $this->reset('messages', 'suggestedCategoryIds', 'question');
        $this->resetValidation();
    }

    /** Черновик заказа из диалога: вопросы заказчика — описание, найденные теги — теги. */
    public function createOrder(): void
    {
        $questions = collect($this->messages)->where('role', 'user')->pluck('content');

        if ($questions->isEmpty()) {
            return;
        }

        session()->put('order_draft', [
            'title' => Str::limit(Str::squish($questions->first()), 100),
            'description' => $questions->implode("\n\n"),
            'city_id' => $this->cityId,
            'category_ids' => $this->suggestedCategoryIds,
        ]);

        $this->redirectRoute('customer.orders.create', navigate: false);
    }

    public function render(): View
    {
        return view('livewire.customer.assistant');
    }
}
