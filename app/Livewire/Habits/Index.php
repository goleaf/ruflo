<?php

namespace App\Livewire\Habits;

use App\Actions\Habits\LinkTodoToHabit;
use App\Actions\Habits\ToggleHabitCheckIn;
use App\Data\Habits\HabitProgress;
use App\Models\Habit;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Habits\HabitListQuery;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('habits.pages.index.title')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $tab = 'habits';

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
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function availableTodos(): Collection
    {
        return app(HabitListQuery::class)->availableTodosFor($this->currentUser());
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
