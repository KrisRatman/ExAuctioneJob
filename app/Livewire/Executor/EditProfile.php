<?php

namespace App\Livewire\Executor;

use App\Models\Category;
use App\Models\Review;
use App\Support\TagRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Анкета исполнителя: от тегов зависит, какие заказы он видит в ленте.
 */
#[Layout('layouts.app')]
#[Title('Мой профиль')]
class EditProfile extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $description = '';

    /** @var list<int> */
    public array $categoryIds = [];

    public function mount(): void
    {
        $user = auth()->user()->load(['executorProfile', 'categories']);

        $this->name = $user->name;
        $this->phone = (string) $user->phone;
        $this->description = (string) $user->executorProfile?->description;
        $this->categoryIds = $user->categories->modelKeys();
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function tree(): Collection
    {
        return Category::tree();
    }

    /** @return Collection<int, Review> */
    #[Computed]
    public function reviews(): Collection
    {
        return auth()->user()->reviewsReceived()->with('customer')->latest()->limit(20)->get();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[\d\s()\-]{10,20}$/'],
            'description' => ['required', 'string', 'min:30', 'max:3000'],
            'categoryIds' => TagRules::executor(),
            'categoryIds.*' => TagRules::each(),
        ], [
            'phone.regex' => 'Укажите телефон в формате +7 900 000-00-00.',
            'categoryIds.required' => 'Отметьте хотя бы одну специализацию — по ним подбираются заказы.',
        ], [
            'categoryIds' => 'специализации',
            'description' => 'о себе',
        ]);

        $user = auth()->user();

        DB::transaction(function () use ($user, $data) {
            $user->update(['name' => trim($data['name']), 'phone' => $data['phone'] ?: null]);
            $user->executorProfile()->updateOrCreate([], ['description' => trim($data['description'])]);
            $user->categories()->sync(array_map(intval(...), $data['categoryIds']));
        });

        $this->dispatch('toast', message: 'Профиль сохранён.');
    }

    public function render(): View
    {
        return view('livewire.executor.edit-profile');
    }
}
