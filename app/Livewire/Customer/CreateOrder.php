<?php

namespace App\Livewire\Customer;

use App\Actions\CreateOrder as CreateOrderAction;
use App\Models\Category;
use App\Models\City;
use App\Support\Ai\SemanticIndex;
use App\Support\TagRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Новый заказ')]
class CreateOrder extends Component
{
    public string $title = '';

    public string $description = '';

    public ?int $startingPrice = null;

    public ?int $cityId = null;

    /** @var list<int> */
    public array $categoryIds = [];

    /**
     * Город подставляется из прошлого заказа — обычно заказчик живёт там же.
     * Если заказ начат в ИИ-помощнике, форма заполняется его черновиком.
     */
    public function mount(): void
    {
        $this->cityId = auth()->user()->customerOrders()->latest('id')->value('city_id');

        /** @var array{title?: string, description?: string, city_id?: ?int, category_ids?: list<int>}|null $draft */
        $draft = session()->pull('order_draft');

        if ($draft !== null) {
            $this->title = $draft['title'] ?? '';
            $this->description = $draft['description'] ?? '';
            $this->cityId = $draft['city_id'] ?? $this->cityId;
            $this->categoryIds = array_slice($draft['category_ids'] ?? [], 0, (int) config('ideajob.order_tags.max'));
        }
    }

    /**
     * Теги, близкие по смыслу к названию и описанию, — кроме уже выбранных.
     * Сбой ИИ не мешает заполнить форму: подсказок просто нет.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function suggestedTags(): Collection
    {
        $index = app(SemanticIndex::class);
        $text = trim($this->title."\n".$this->description);

        if (! $index->isEnabled() || mb_strlen($text) < 10) {
            return new Collection;
        }

        $scores = rescue(fn () => $index->rank(
            $text,
            Category::class,
            minSimilarity: (float) config('ideajob.ai.tag_min_similarity'),
            limit: (int) config('ideajob.ai.tag_suggestions'),
        ), []);

        return Category::query()
            ->tags()
            ->with('parent')
            ->whereKey(array_keys($scores))
            ->whereKeyNot($this->categoryIds)
            ->get()
            ->sortByDesc(fn (Category $tag) => $scores[$tag->id])
            ->values();
    }

    public function addTag(int $categoryId): void
    {
        // Чекбоксы присылают id строками.
        $selected = array_map(intval(...), $this->categoryIds);

        if (in_array($categoryId, $selected, true) || count($selected) >= (int) config('ideajob.order_tags.max')) {
            return;
        }

        if (Category::query()->tags()->whereKey($categoryId)->exists()) {
            $this->categoryIds[] = $categoryId;
        }
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function tree(): Collection
    {
        return Category::tree();
    }

    /** @return Collection<int, City> */
    #[Computed]
    public function cities(): Collection
    {
        return City::options();
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:30', 'max:5000'],
            'startingPrice' => ['required', 'integer', 'min:'.config('ideajob.min_price'), 'max:'.config('ideajob.max_price')],
            'cityId' => ['required', 'integer', 'exists:cities,id'],
            'categoryIds' => TagRules::order(),
            'categoryIds.*' => TagRules::each(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'cityId.required' => 'Укажите город — заказ увидят только мастера из него.',
            'categoryIds.required' => 'Выберите хотя бы один тег — по ним заказ увидят исполнители.',
            'categoryIds.max' => 'Можно выбрать не больше :max тегов.',
        ];
    }

    public function save(CreateOrderAction $createOrder): void
    {
        $data = $this->validate();

        $order = $createOrder->handle(auth()->user(), [
            'city_id' => (int) $data['cityId'],
            'title' => trim($data['title']),
            'description' => trim($data['description']),
            'starting_price' => (int) $data['startingPrice'],
            'category_ids' => array_map(intval(...), $data['categoryIds']),
        ]);

        session()->flash('status', 'Заказ опубликован. Мастера вашего города с подходящими тегами уже видят его в ленте.');

        $this->redirectRoute('customer.orders.show', $order, navigate: false);
    }

    public function render(): View
    {
        return view('livewire.customer.create-order');
    }
}
