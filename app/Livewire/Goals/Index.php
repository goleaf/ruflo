<?php

namespace App\Livewire\Goals;

use App\Actions\Goals\CheckInGoalMilestone;
use App\Actions\Goals\LinkTodoToGoal;
use App\Data\Goals\GoalProgress;
use App\Models\Goal;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Goals\GoalListQuery;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('goals.pages.index.title')]
class Index extends Component
{
    use AuthorizesRequests;

    /** @var array<int, string> */
    public array $linkTodoIds = [];

    /** @var array<int, string> */
    public array $linkMilestoneIds = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Goal::class);
    }

    public function render(): View
    {
        return view('livewire.goals.index');
    }

    public function checkInMilestone(int $milestoneId, GoalListQuery $query, CheckInGoalMilestone $checkIn): void
    {
        $milestone = $query->findMilestoneFor($this->currentUser(), $milestoneId);

        $updatedMilestone = $checkIn->handle($this->currentUser(), $milestone);

        $this->refreshGoalState();

        Flux::toast(
            variant: 'success',
            text: $updatedMilestone->isCompleted()
                ? __('goals.messages.milestone_checked_in')
                : __('goals.messages.milestone_reopened'),
        );
    }

    public function linkTodo(int $goalId, GoalListQuery $query, LinkTodoToGoal $linkTodo): void
    {
        $user = $this->currentUser();
        $todoId = $this->linkTodoIds[$goalId] ?? '';

        if ($todoId === '' || ! ctype_digit((string) $todoId)) {
            $this->addError('linkTodoIds.'.$goalId, __('goals.validation.todo_required'));

            return;
        }

        $milestoneId = $this->linkMilestoneIds[$goalId] ?? '';
        $goal = $query->findFor($user, $goalId);
        $todo = $query->findTodoFor($user, (int) $todoId);
        $milestone = $milestoneId === '' ? null : $query->findMilestoneFor($user, (int) $milestoneId);

        $linkTodo->handle($user, $goal, $todo, $milestone);

        unset($this->linkTodoIds[$goalId], $this->linkMilestoneIds[$goalId]);
        $this->refreshGoalState();

        Flux::toast(variant: 'success', text: __('goals.messages.todo_linked', ['title' => $todo->title]));
    }

    /**
     * @return array<int, array{goal: Goal, progress: GoalProgress}>
     */
    #[Computed]
    public function goalCards(): array
    {
        return app(GoalListQuery::class)
            ->for($this->currentUser())
            ->map(fn (Goal $goal): array => [
                'goal' => $goal,
                'progress' => GoalProgress::forGoal($goal),
            ])
            ->all();
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function availableTodos(): Collection
    {
        return app(GoalListQuery::class)->availableTodosFor($this->currentUser());
    }

    private function refreshGoalState(): void
    {
        unset($this->goalCards, $this->availableTodos);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
