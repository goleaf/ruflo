<?php

namespace App\Livewire\Goals;

use App\Actions\Goals\CheckInGoalMilestone;
use App\Actions\Goals\CreateGoal;
use App\Actions\Goals\CreateGoalMilestone;
use App\Actions\Goals\LinkTodoToGoal;
use App\Data\Goals\GoalData;
use App\Data\Goals\GoalMilestoneData;
use App\Data\Goals\GoalProgress;
use App\Models\Goal;
use App\Models\Project;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Goals\GoalListQuery;
use App\Queries\Projects\ProjectListQuery;
use App\Rules\Goals\GoalTitle;
use App\Rules\Goals\MilestoneTitle;
use App\Rules\Todos\OwnedActiveProject;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('goals.pages.index.title')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $title = '';

    public string $description = '';

    public ?string $projectId = null;

    public ?string $targetDate = null;

    public string $milestoneGoalId = '';

    public string $milestoneTitle = '';

    public ?string $milestoneTargetDate = null;

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

    public function createGoal(CreateGoal $createGoal): void
    {
        $goal = $createGoal->handle($this->currentUser(), GoalData::fromArray($this->validatedGoalInput()));

        $this->reset(['title', 'description', 'projectId', 'targetDate']);
        $this->refreshGoalState();

        Flux::toast(variant: 'success', text: __('goals.messages.created', ['title' => $goal->title]));
    }

    public function addMilestone(CreateGoalMilestone $createMilestone): void
    {
        $milestone = $createMilestone->handle($this->currentUser(), GoalMilestoneData::fromArray($this->validatedMilestoneInput()));

        $this->reset(['milestoneGoalId', 'milestoneTitle', 'milestoneTargetDate']);
        $this->refreshGoalState();

        Flux::toast(variant: 'success', text: __('goals.messages.milestone_created', ['title' => $milestone->title]));
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

    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return app(ProjectListQuery::class)->activeFor($this->currentUser());
    }

    /**
     * @return array{title: string, description?: string|null, project_id?: string|null, target_date?: string|null}
     */
    private function validatedGoalInput(): array
    {
        return Validator::make(
            [
                'title' => $this->title,
                'description' => $this->description,
                'project_id' => $this->projectId,
                'target_date' => $this->targetDate,
            ],
            [
                'title' => ['required', 'string', 'max:'.GoalTitle::MaxLength, new GoalTitle],
                'description' => ['nullable', 'string', 'max:2000'],
                'project_id' => ['nullable', 'integer', new OwnedActiveProject($this->currentUser())],
                'target_date' => ['nullable', 'date_format:Y-m-d'],
            ],
            [
                'title.required' => __('goals.validation.title'),
                'title.string' => __('goals.validation.title'),
                'title.max' => __('goals.validation.title'),
                'description.max' => __('goals.validation.description'),
                'target_date.date_format' => __('goals.validation.target_date'),
            ],
            [
                'title' => __('goals.fields.title'),
                'description' => __('goals.fields.description'),
                'project_id' => __('goals.fields.project'),
                'target_date' => __('goals.fields.target_date'),
            ],
        )->validate();
    }

    /**
     * @return array{goal_id: string, title: string, target_date?: string|null}
     */
    private function validatedMilestoneInput(): array
    {
        return Validator::make(
            [
                'goal_id' => $this->milestoneGoalId,
                'title' => $this->milestoneTitle,
                'target_date' => $this->milestoneTargetDate,
            ],
            [
                'goal_id' => ['required', 'integer'],
                'title' => ['required', 'string', 'max:'.MilestoneTitle::MaxLength, new MilestoneTitle],
                'target_date' => ['nullable', 'date_format:Y-m-d'],
            ],
            [
                'goal_id.required' => __('goals.validation.goal_required'),
                'goal_id.integer' => __('goals.validation.goal_required'),
                'title.required' => __('goals.validation.milestone_title'),
                'title.string' => __('goals.validation.milestone_title'),
                'title.max' => __('goals.validation.milestone_title'),
                'target_date.date_format' => __('goals.validation.target_date'),
            ],
            [
                'goal_id' => __('goals.fields.goal'),
                'title' => __('goals.fields.milestone_title'),
                'target_date' => __('goals.fields.target_date'),
            ],
        )->validate();
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
