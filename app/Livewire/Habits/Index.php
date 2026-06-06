<?php

namespace App\Livewire\Habits;

use App\Actions\Habits\CreateHabit;
use App\Actions\Habits\LinkTodoToHabit;
use App\Actions\Habits\ToggleHabitCheckIn;
use App\Data\Habits\HabitData;
use App\Data\Habits\HabitProgress;
use App\Enums\HabitFrequency;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Goals\GoalListQuery;
use App\Queries\Habits\HabitListQuery;
use App\Rules\Habits\HabitTargetCount;
use App\Rules\Habits\HabitTitle;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('habits.pages.index.title')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $title = '';

    public string $description = '';

    public string $frequency = 'daily';

    public string $targetCount = '1';

    public ?string $goalId = null;

    /** @var array<int, string> */
    public array $linkTodoIds = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Habit::class);
    }

    public function render(): View
    {
        return view('livewire.habits.index');
    }

    public function updatedFrequency(string $frequency): void
    {
        if ($frequency === HabitFrequency::Daily->value) {
            $this->targetCount = '1';
        }
    }

    public function createHabit(CreateHabit $createHabit): void
    {
        $habit = $createHabit->handle($this->currentUser(), HabitData::fromArray($this->validatedHabitInput()));

        $this->reset(['title', 'description', 'goalId']);
        $this->frequency = HabitFrequency::Daily->value;
        $this->targetCount = '1';
        $this->refreshHabitState();

        Flux::toast(variant: 'success', text: __('habits.messages.created', ['title' => $habit->title]));
    }

    public function toggleCheckIn(int $habitId, HabitListQuery $query, ToggleHabitCheckIn $toggleCheckIn): void
    {
        $habit = $query->findFor($this->currentUser(), $habitId);
        $checkedIn = $toggleCheckIn->handle($this->currentUser(), $habit);

        $this->refreshHabitState();

        Flux::toast(
            variant: 'success',
            text: $checkedIn ? __('habits.messages.checked_in') : __('habits.messages.unchecked'),
        );
    }

    public function linkTodo(int $habitId, HabitListQuery $query, LinkTodoToHabit $linkTodo): void
    {
        $user = $this->currentUser();
        $todoId = $this->linkTodoIds[$habitId] ?? '';

        if ($todoId === '' || ! ctype_digit((string) $todoId)) {
            $this->addError('linkTodoIds.'.$habitId, __('habits.validation.todo_required'));

            return;
        }

        $habit = $query->findFor($user, $habitId);
        $todo = $query->findTodoFor($user, (int) $todoId);

        $linkTodo->handle($user, $habit, $todo);

        unset($this->linkTodoIds[$habitId]);
        $this->refreshHabitState();

        Flux::toast(variant: 'success', text: __('habits.messages.todo_linked', ['title' => $todo->title]));
    }

    /**
     * @return array<int, array{habit: Habit, progress: HabitProgress}>
     */
    #[Computed]
    public function habitCards(): array
    {
        return app(HabitListQuery::class)
            ->for($this->currentUser())
            ->map(fn (Habit $habit): array => [
                'habit' => $habit,
                'progress' => HabitProgress::forHabit($habit),
            ])
            ->all();
    }

    #[Computed]
    public function checkedTodayCount(): int
    {
        return collect($this->habitCards)
            ->filter(fn (array $card): bool => $card['progress']->checkedInToday)
            ->count();
    }

    #[Computed]
    public function currentStreakTotal(): int
    {
        return collect($this->habitCards)
            ->sum(fn (array $card): int => $card['progress']->currentStreak);
    }

    /**
     * @return Collection<int, Goal>
     */
    #[Computed]
    public function goals(): Collection
    {
        return app(GoalListQuery::class)->for($this->currentUser());
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function availableTodos(): Collection
    {
        return app(HabitListQuery::class)->availableTodosFor($this->currentUser());
    }

    /**
     * @return array{title: string, description?: string|null, frequency: string, target_count: string, goal_id?: string|null}
     */
    private function validatedHabitInput(): array
    {
        $frequency = HabitFrequency::tryFrom($this->frequency);

        return Validator::make(
            [
                'title' => $this->title,
                'description' => $this->description,
                'frequency' => $this->frequency,
                'target_count' => $this->targetCount,
                'goal_id' => $this->goalId,
            ],
            [
                'title' => ['required', 'string', 'max:'.HabitTitle::MaxLength, new HabitTitle],
                'description' => ['nullable', 'string', 'max:2000'],
                'frequency' => ['required', Rule::enum(HabitFrequency::class)],
                'target_count' => ['required', 'integer', new HabitTargetCount($frequency)],
                'goal_id' => ['nullable', 'integer'],
            ],
            [
                'title.required' => __('habits.validation.title'),
                'title.string' => __('habits.validation.title'),
                'title.max' => __('habits.validation.title'),
                'description.max' => __('habits.validation.description'),
                'frequency.required' => __('habits.validation.frequency'),
                'frequency.enum' => __('habits.validation.frequency'),
                'target_count.required' => __('habits.validation.target_count'),
                'target_count.integer' => __('habits.validation.target_count'),
                'goal_id.integer' => __('habits.validation.goal_required'),
            ],
            [
                'title' => __('habits.fields.title'),
                'description' => __('habits.fields.description'),
                'frequency' => __('habits.fields.frequency'),
                'target_count' => __('habits.fields.target_count'),
                'goal_id' => __('habits.fields.goal'),
            ],
        )->validate();
    }

    private function refreshHabitState(): void
    {
        unset($this->habitCards, $this->availableTodos, $this->checkedTodayCount, $this->currentStreakTotal);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
